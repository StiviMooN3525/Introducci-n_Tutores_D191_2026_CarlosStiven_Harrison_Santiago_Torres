<?php
include_once 'header.php';
require_once 'conexion.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'profesor') {
		header('Location: login.php'); exit;
}

// Procesar acciones: agregar disponibilidad y gestionar tutorías
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_POST['action'] ?? '') === 'add_disponibilidad') {
    $fecha = $_POST['fecha'] ?? null;
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fin = $_POST['hora_fin'] ?? null;
    if ($fecha && $hora_inicio && $hora_fin) {
      // Obtener id_profesor asociado al usuario actual
      $profStmt = $pdo->prepare('SELECT id_profesor FROM profesores WHERE id_usuario = :u LIMIT 1');
      $profStmt->execute(['u' => $_SESSION['user_id']]);
      $id_prof = $profStmt->fetchColumn();
      if ($id_prof) {
        $stmt = $pdo->prepare('INSERT INTO disponibilidad (id_profesor, fecha, hora_inicio, hora_fin) VALUES (:prof, :fec, :hi, :hf)');
        $stmt->execute(['prof' => $id_prof, 'fec' => $fecha, 'hi' => $hora_inicio, 'hf' => $hora_fin]);
      }
      header('Location: dashboard_profesor.php'); exit;
    }
  }

		if (($_POST['action'] ?? '') === 'tutoria_action') {
				$t_id = intval($_POST['tutoria_id'] ?? 0);
				$result = $_POST['result'] ?? '';
        $allowed = ['aceptada','cancelada','realizada','pendiente'];
        if ($t_id && in_array($result, $allowed)) {
          // Obtener id_profesor asociado al usuario actual
          $profStmt2 = $pdo->prepare('SELECT id_profesor FROM profesores WHERE id_usuario = :u LIMIT 1');
          $profStmt2->execute(['u' => $_SESSION['user_id']]);
          $curr_prof = $profStmt2->fetchColumn();
          if ($curr_prof) {
            $stmt = $pdo->prepare('UPDATE tutorias SET estado = :est WHERE id_tutoria = :id AND id_profesor = :prof');
            $stmt->execute(['est' => $result, 'id' => $t_id, 'prof' => $curr_prof]);
            header('Location: dashboard_profesor.php'); exit;
          }
        }
		}
}

?>

<section class="card">
  <h2>Panel Profesor</h2>
  <p class="muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>. Gestiona tu disponibilidad y tutorías.</p>
</section>

<div class="dashboard-grid prof-panel">
  <div class="card">
    <h3>Mi Disponibilidad</h3>
    <p class="muted">Registra horarios para recibir solicitudes.</p>
    <form method="post" style="margin-bottom:16px">
      <input type="hidden" name="action" value="add_disponibilidad">
      <div class="field"><label>Fecha</label><input type="date" name="fecha" required></div>
      <div class="field"><label>Hora inicio</label><input type="time" name="hora_inicio" required></div>
      <div class="field"><label>Hora fin</label><input type="time" name="hora_fin" required></div>
      <button class="btn primary" type="submit">Agregar Disponibilidad</button>
    </form>

    <h4>Disponibilidades Próximas</h4>
    <table class="table">
      <thead><tr><th>Fecha</th><th>Inicio</th><th>Fin</th></tr></thead>
      <tbody>
      <?php
      // Obtener id_profesor para el usuario actual
      $profStmt = $pdo->prepare('SELECT id_profesor FROM profesores WHERE id_usuario = :u LIMIT 1');
      $profStmt->execute(['u' => $_SESSION['user_id']]);
      $my_prof_id = $profStmt->fetchColumn();
      if ($my_prof_id) {
        $stmt = $pdo->prepare('SELECT * FROM disponibilidad WHERE id_profesor = :prof AND fecha >= CURDATE() ORDER BY fecha, hora_inicio');
        $stmt->execute(['prof' => $my_prof_id]);
      } else {
        $stmt = $pdo->query('SELECT NULL AS fecha, NULL AS hora_inicio, NULL AS hora_fin LIMIT 0');
      }
      while ($d = $stmt->fetch()) {
          echo '<tr><td>'.htmlspecialchars($d['fecha']).'</td><td>'.htmlspecialchars(substr($d['hora_inicio'],0,5)).'</td><td>'.htmlspecialchars(substr($d['hora_fin'],0,5)).'</td></tr>';
      }
      ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3>Solicitudes de Tutoría</h3>
    <p class="muted">Acepta, rechaza o marca como completada.</p>
    <table class="table">
      <thead><tr><th>Estudiante</th><th>Materia</th><th>Fecha</th><th>Hora</th><th>Estado</th><th>Acción</th></tr></thead>
      <tbody>
        <?php
        // Listar tutorías para el profesor (usar id_profesor)
        $sql = 'SELECT t.id_tutoria AS id, t.fecha, t.hora, t.estado, uest.nombre AS estudiante
            FROM tutorias t
            JOIN estudiantes est ON t.id_estudiante = est.id_estudiante
            JOIN usuarios uest ON est.id_usuario = uest.id_usuario
            WHERE t.id_profesor = :prof
            ORDER BY t.fecha ASC, t.hora ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['prof' => $my_prof_id]);
        while ($row = $stmt->fetch()) {
          echo '<tr>';
          echo '<td>'.htmlspecialchars($row['estudiante']).'</td>';
          echo '<td>-</td>';
          echo '<td>'.htmlspecialchars($row['fecha']).'</td>';
          echo '<td>'.htmlspecialchars(substr($row['hora'],0,5)).'</td>';
          echo '<td><span class="chip '.htmlspecialchars($row['estado']).'">'.htmlspecialchars(ucfirst($row['estado'])).'</span></td>';
          echo '<td>';
          if ($row['estado'] === 'pendiente') {
              echo '<form method="post" style="display:inline-block;margin-right:8px">';
              echo '<input type="hidden" name="action" value="tutoria_action">';
              echo '<input type="hidden" name="tutoria_id" value="'.intval($row['id']).'">';
              echo '<input type="hidden" name="result" value="aceptada">';
              echo '<button class="btn success" type="submit" onclick="return confirmAction(this.form, \'Aceptar esta tutoría?\')">Aceptar</button>';
              echo '</form>';
              echo '<form method="post" style="display:inline-block">';
              echo '<input type="hidden" name="action" value="tutoria_action">';
              echo '<input type="hidden" name="tutoria_id" value="'.intval($row['id']).'">';
            echo '<input type="hidden" name="result" value="cancelada">';
              echo '<button class="btn danger" type="submit" onclick="return confirmAction(this.form, \'Rechazar esta tutoría?\')">Rechazar</button>';
              echo '</form>';
          } elseif ($row['estado'] === 'aceptada') {
            echo '<form method="post">';
            echo '<input type="hidden" name="action" value="tutoria_action">';
            echo '<input type="hidden" name="tutoria_id" value="'.intval($row['id']).'">';
            echo '<input type="hidden" name="result" value="realizada">';
            echo '<button class="btn primary" type="submit" onclick="return confirmAction(this.form, \"Marcar como realizada?\")">Completar</button>';
            echo '</form>';
          } else {
              echo '<span class="muted">Finalizada</span>';
          }
          echo '</td>';
          echo '</tr>';
      }
      ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once 'footer.php'; ?>

