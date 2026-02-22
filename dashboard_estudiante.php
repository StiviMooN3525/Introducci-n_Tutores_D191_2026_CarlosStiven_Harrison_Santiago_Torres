<?php
include_once 'header.php';
require_once 'conexion.php';

// Control de acceso básico
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'estudiante') {
		header('Location: login.php'); exit;
}

// Procesar reserva enviada desde este dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reservar') {
  $profesor_id = intval($_POST['profesor_id'] ?? 0); // id_profesor
  $materia_id = intval($_POST['materia_id'] ?? 0);
  $fecha = $_POST['fecha'] ?? null;
  $hora = $_POST['hora'] ?? null;

  if ($profesor_id && $materia_id && $fecha && $hora) {
    // Obtener id_estudiante correspondiente al usuario actual
    $stmtEst = $pdo->prepare('SELECT id_estudiante FROM estudiantes WHERE id_usuario = :u LIMIT 1');
    $stmtEst->execute(['u' => $_SESSION['user_id']]);
    $id_est = $stmtEst->fetchColumn();
    if ($id_est) {
      // Verificar disponibilidad en la capa de aplicación antes de insertar (evita el error del trigger)
      $chk = $pdo->prepare("SELECT id_disponibilidad FROM disponibilidad WHERE id_profesor = :prof AND fecha = :fec AND :hora >= hora_inicio AND :hora < hora_fin AND estado = 'disponible' LIMIT 1");
      $chk->execute(['prof' => $profesor_id, 'fec' => $fecha, 'hora' => $hora]);
      $disp_id = $chk->fetchColumn();
      if (!$disp_id) {
        $error = 'No existe disponibilidad para el profesor en el horario seleccionado.';
      } else {
        try {
          // Insertar la tutoría incluyendo la materia seleccionada
          $stmt = $pdo->prepare('INSERT INTO tutorias (id_estudiante, id_profesor, id_materia, fecha, hora, estado, observaciones) VALUES (:est, :prof, :mat, :fec, :hor, :estd, NULL)');
          $stmt->execute([
            'est' => $id_est,
            'prof' => $profesor_id,
            'mat' => $materia_id,
            'fec' => $fecha,
            'hor' => $hora,
            'estd' => 'pendiente'
          ]);
          header('Location: dashboard_estudiante.php'); exit;
        } catch (PDOException $e) {
          $error = 'Error al reservar: ' . $e->getMessage();
        }
      }
    }
  }
}

?>

<section class="card">
  <h2>Panel Estudiante</h2>
  <p class="muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>. Reserva tutorías y revisa tu historial.</p>
  <?php if (!empty($error)) { echo '<div class="alert error">'.htmlspecialchars($error).'</div>'; } ?>
</section>

<div class="dashboard-grid student-panel">
  <div class="card">
    <h3>Profesores Disponibles</h3>
    <p class="muted">Reserva horas disponibles de profesores.</p>
    <table class="table">
      <thead><tr><th>Profesor</th><th>Materia</th><th>Fecha</th><th>Hora</th><th>Acción</th></tr></thead>
      <tbody>
      <?php
        // Consultar disponibilidades próximas
        $sql = "SELECT d.id_disponibilidad AS disp_id, prof.id_profesor AS profesor_id, uprof.nombre AS profesor, d.fecha, d.hora_inicio, d.hora_fin
            FROM disponibilidad d
            JOIN profesores prof ON d.id_profesor = prof.id_profesor
            JOIN usuarios uprof ON prof.id_usuario = uprof.id_usuario
            WHERE d.fecha >= CURDATE()
            ORDER BY d.fecha, d.hora_inicio";
        foreach ($pdo->query($sql) as $row) {
          $hora_display = substr($row['hora_inicio'],0,5);
          echo '<tr>';
          echo '<td>'.htmlspecialchars($row['profesor']).'</td>';
          // Obtener materias que dicta el profesor (soporta tablas profesor_materia o campo id_profesor en materias)
          $matOptions = [];
          $stmtMat = $pdo->prepare("SELECT m.id_materia, m.nombre FROM materias m WHERE m.id_profesor = :pid UNION SELECT m2.id_materia, m2.nombre FROM materias m2 JOIN profesor_materia pm ON m2.id_materia = pm.materia_id WHERE pm.profesor_id = :pid2 GROUP BY id_materia ORDER BY nombre");
          try {
            $stmtMat->execute(['pid' => $row['profesor_id'], 'pid2' => $row['profesor_id']]);
            while ($m = $stmtMat->fetch()) {
              $matOptions[] = $m;
            }
          } catch (Exception $e) {
            // Si no existe la tabla profesor_materia o hay error, intentar obtener por id_profesor solamente
            $stmtMat2 = $pdo->prepare("SELECT id_materia, nombre FROM materias WHERE id_profesor = :pid_only");
            $stmtMat2->execute(['pid_only' => $row['profesor_id']]);
            while ($m = $stmtMat2->fetch()) { $matOptions[] = $m; }
          }

          // Mostrar primera materia como texto o un select si hay varias
          echo '<td>';
          if (count($matOptions) === 0) {
            echo '-';
          } elseif (count($matOptions) === 1) {
            echo htmlspecialchars($matOptions[0]['nombre']);
          } else {
            // Mostrar primer materia por defecto (el formulario incluirá select para elegir)
            echo '<select form="form_'.intval($row['disp_id']).'" name="materia_id">';
            foreach ($matOptions as $opt) {
              echo '<option value="'.intval($opt['id_materia']).'">'.htmlspecialchars($opt['nombre']).'</option>';
            }
            echo '</select>';
          }
          echo '</td>';
          echo '<td>'.htmlspecialchars($row['fecha']).'</td>';
          echo '<td>'.htmlspecialchars($hora_display).'</td>';
          echo '<td>';
          echo '<form id="form_'.intval($row['disp_id']).'" method="post" style="display:inline-block">';
          echo '<input type="hidden" name="action" value="reservar">';
          echo '<input type="hidden" name="profesor_id" value="'.intval($row['profesor_id']).'">';
          // Incluir campo hidden solo si no hay opción múltiple
          if (count($matOptions) === 1) {
            echo '<input type="hidden" name="materia_id" value="'.intval($matOptions[0]['id_materia']).'">';
          } elseif (count($matOptions) === 0) {
            echo '<input type="hidden" name="materia_id" value="0">';
          }
          echo '<input type="hidden" name="fecha" value="'.htmlspecialchars($row['fecha']).'">';
          echo '<input type="hidden" name="hora" value="'.htmlspecialchars($hora_display).'">';
          echo '<button class="btn primary" type="submit" onclick="return confirmAction(this.form, \'Confirmar reserva?\')">Reservar</button>';
          echo '</form>';
          echo '</td>';
          echo '</tr>';
      }
      ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3>Historial de Tutorías</h3>
    <p class="muted">Tus tutorías pasadas y próximas.</p>
    <table class="table">
      <thead><tr><th>Profesor</th><th>Materia</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr></thead>
      <tbody>
      <?php
        // Historial: unir tutorias -> estudiantes(id_usuario) para filtrar por usuario actual
          $sqlHist = 'SELECT t.id_tutoria AS id, t.fecha, t.hora, t.estado, uprof.nombre AS profesor, COALESCE(m.nombre, "-") AS materia
            FROM tutorias t
            JOIN estudiantes est ON t.id_estudiante = est.id_estudiante
            JOIN profesores prof ON t.id_profesor = prof.id_profesor
            JOIN usuarios uprof ON prof.id_usuario = uprof.id_usuario
            LEFT JOIN materias m ON m.id_materia = t.id_materia
            WHERE est.id_usuario = :uid
            ORDER BY t.fecha DESC, t.hora DESC';
        $stmt = $pdo->prepare($sqlHist);
        $stmt->execute(['uid' => $_SESSION['user_id']]);
        while ($t = $stmt->fetch()) {
          echo '<tr>';
          echo '<td>'.htmlspecialchars($t['profesor']).'</td>';
          echo '<td>'.htmlspecialchars($t['materia']).'</td>';
          echo '<td>'.htmlspecialchars($t['fecha']).'</td>';
          echo '<td>'.htmlspecialchars(substr($t['hora'],0,5)).'</td>';
          echo '<td><span class="chip '.htmlspecialchars($t['estado']).'">'.htmlspecialchars(ucfirst($t['estado'])).'</span></td>';
          echo '</tr>';
        }
      ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once 'footer.php'; ?>
