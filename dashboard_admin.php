<?php
include_once 'header.php';
require_once 'conexion.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrador') {
  header('Location: login.php'); exit;
}

// Procesar acciones administrativas (crear/editar/eliminar/activar usuarios, gestionar tutorías)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // Cambiar estado (activo/inactivo)
  if ($action === 'toggle_state') {
    $u = intval($_POST['user_id'] ?? 0);
    $newState = ($_POST['state'] === 'activo') ? 'activo' : 'inactivo';
    if ($u) {
      $stmt = $pdo->prepare('UPDATE usuarios SET estado = :s WHERE id_usuario = :id');
      $stmt->execute(['s' => $newState, 'id' => $u]);
      header('Location: dashboard_admin.php'); exit;
    }
  }

  // Crear usuario (administrador puede crear profesores, administradores o estudiantes manualmente)
  if ($action === 'create_user') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rol = $_POST['rol'] ?? 'estudiante';
    $pass = $_POST['password'] ?? '';
    $codigo = trim($_POST['codigo_estudiantil'] ?? '');
    $carrera = trim($_POST['carrera'] ?? '');
    $semestre = intval($_POST['semestre'] ?? 0) ?: null;
    $especialidad = trim($_POST['especialidad'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');

    if (!$nombre || !$correo || !$pass) {
      $error = 'Nombre, correo y contraseña son obligatorios.';
    } else {
      // Verificar correo duplicado
      $chk = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE correo = :c LIMIT 1');
      $chk->execute(['c' => $correo]);
      if ($chk->fetchColumn()) {
        $error = 'El correo ya está registrado.';
      } else {
        // Insertar usuario
        $ins = $pdo->prepare('INSERT INTO usuarios (nombre, correo, contrasena, rol, estado) VALUES (:n, :c, :p, :r, "activo")');
        $ins->execute(['n' => $nombre, 'c' => $correo, 'p' => $pass, 'r' => $rol]);
        $newUserId = $pdo->lastInsertId();

        // Si es profesor, crear registro en tabla profesores
        if ($rol === 'profesor') {
          $insp = $pdo->prepare('INSERT INTO profesores (id_usuario, especialidad, departamento) VALUES (:u, :esp, :dep)');
          $insp->execute(['u' => $newUserId, 'esp' => $especialidad ?: null, 'dep' => $departamento ?: null]);
        }

        // Si es estudiante, crear registro en tabla estudiantes (si se proporcionó código)
        if ($rol === 'estudiante') {
          // Validar código si se proporciona
          if ($codigo) {
            $chkc = $pdo->prepare('SELECT id_estudiante FROM estudiantes WHERE codigo_estudiantil = :cod LIMIT 1');
            $chkc->execute(['cod' => $codigo]);
            if ($chkc->fetchColumn()) {
              // eliminar usuario creado y notificar error
              $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = :id')->execute(['id' => $newUserId]);
              $error = 'Código estudiantil ya registrado.';
            } else {
              $inse = $pdo->prepare('INSERT INTO estudiantes (id_usuario, codigo_estudiantil, carrera, semestre) VALUES (:u, :cod, :car, :sem)');
              $inse->execute(['u' => $newUserId, 'cod' => $codigo, 'car' => $carrera ?: null, 'sem' => $semestre]);
            }
          } else {
            // Crear estudiante sin código (opcional)
            $inse = $pdo->prepare('INSERT INTO estudiantes (id_usuario, codigo_estudiantil, carrera, semestre) VALUES (:u, NULL, :car, :sem)');
            $inse->execute(['u' => $newUserId, 'car' => $carrera ?: null, 'sem' => $semestre]);
          }
        }

        header('Location: dashboard_admin.php'); exit;
      }
    }
  }

  // Crear materia
  if ($action === 'create_materia') {
    $mat_name = trim($_POST['materia_nombre'] ?? '');
    $asign_prof = intval($_POST['asign_prof'] ?? 0);
    if ($mat_name) {
      try {
        // Verificar si la materia ya existe por nombre (evita excepción por UNIQUE)
        $chkMat = $pdo->prepare('SELECT id_materia FROM materias WHERE nombre = :n LIMIT 1');
        $chkMat->execute(['n' => $mat_name]);
        $newMatId = $chkMat->fetchColumn();
        if (!$newMatId) {
          // Insertar nueva materia; si se asigna profesor, podemos guardar el id_profesor también
          $ins = $pdo->prepare('INSERT INTO materias (nombre, id_profesor) VALUES (:n, :pid)');
          $ins->execute(['n' => $mat_name, 'pid' => $asign_prof ?: null]);
          $newMatId = $pdo->lastInsertId();
        } else {
          // Si existe y se pasó asign_prof, intentar actualizar relación N:M o asignar profesor via tabla puente
        }

        if ($asign_prof && $newMatId) {
          // crear relación en profesor_materia si no existe
          $chk = $pdo->prepare('SELECT COUNT(*) FROM profesor_materia WHERE profesor_id = :p AND materia_id = :m');
          $chk->execute(['p' => $asign_prof, 'm' => $newMatId]);
          if ($chk->fetchColumn() == 0) {
            $pdo->prepare('INSERT INTO profesor_materia (profesor_id, materia_id) VALUES (:p, :m)')->execute(['p' => $asign_prof, 'm' => $newMatId]);
          }
        }
        header('Location: dashboard_admin.php'); exit;
      } catch (PDOException $e) {
        // Manejar duplicados u otros errores sin exponer errores fatales
        if ($e->getCode() === '23000') {
          $error = 'La materia ya existe.';
        } else {
          $error = 'Error al crear la materia: ' . $e->getMessage();
        }
      }
    }
  }

  // Editar usuario (nombre, correo, rol, estado)
  if ($action === 'edit_user') {
    $uid = intval($_POST['user_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';
    if ($uid && $nombre && $correo) {
      // Verificar email duplicado en otro usuario
      $chk = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE correo = :c AND id_usuario != :id LIMIT 1');
      $chk->execute(['c' => $correo, 'id' => $uid]);
      if ($chk->fetchColumn()) {
        $error = 'El correo ya está en uso por otro usuario.';
      } else {
        $upd = $pdo->prepare('UPDATE usuarios SET nombre = :n, correo = :c, rol = :r, estado = :s WHERE id_usuario = :id');
        $upd->execute(['n' => $nombre, 'c' => $correo, 'r' => $rol, 's' => $estado, 'id' => $uid]);
        // Si cambiaron rol a profesor y no existe registro en profesores, crearlo
        if ($rol === 'profesor') {
          $chkp = $pdo->prepare('SELECT id_profesor FROM profesores WHERE id_usuario = :u LIMIT 1');
          $chkp->execute(['u' => $uid]);
          if (!$chkp->fetchColumn()) {
            $pdo->prepare('INSERT INTO profesores (id_usuario) VALUES (:u)')->execute(['u' => $uid]);
          }
        }
        // Si cambiaron rol a estudiante and no existe estudiantes record, create
        if ($rol === 'estudiante') {
          $chke = $pdo->prepare('SELECT id_estudiante FROM estudiantes WHERE id_usuario = :u LIMIT 1');
          $chke->execute(['u' => $uid]);
          if (!$chke->fetchColumn()) {
            $pdo->prepare('INSERT INTO estudiantes (id_usuario) VALUES (:u)')->execute(['u' => $uid]);
          }
        }
        header('Location: dashboard_admin.php'); exit;
      }
    }
  }

  // Eliminar usuario con validaciones
  if ($action === 'delete_user') {
    $u = intval($_POST['user_id'] ?? 0);
    if ($u) {
      // Obtener rol
      $stmt = $pdo->prepare('SELECT rol FROM usuarios WHERE id_usuario = :id LIMIT 1');
      $stmt->execute(['id' => $u]);
      $rol = $stmt->fetchColumn();

      // No permitir eliminar el último administrador activo
      if ($rol === 'administrador') {
        $cnt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador' AND estado = 'activo'")->fetchColumn();
        if ($cnt <= 1) {
          $error = 'No se puede eliminar el último administrador activo.';
        }
      }

      // Si es profesor, verificar tutorías activas (pendiente o aceptada)
      if (empty($error) && $rol === 'profesor') {
        $stmtp = $pdo->prepare('SELECT id_profesor FROM profesores WHERE id_usuario = :u LIMIT 1');
        $stmtp->execute(['u' => $u]);
        $idprof = $stmtp->fetchColumn();
        if ($idprof) {
          $cntt = $pdo->prepare("SELECT COUNT(*) FROM tutorias WHERE id_profesor = :p AND estado IN ('pendiente','aceptada')");
          $cntt->execute(['p' => $idprof]);
          if ($cntt->fetchColumn() > 0) {
            $error = 'No se puede eliminar profesor con tutorías activas.';
          }
        }
      }

      if (empty($error)) {
        // Eliminación: borrar registros dependientes por FK (ON DELETE CASCADE) se encargará
        $del = $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = :id');
        $del->execute(['id' => $u]);
        header('Location: dashboard_admin.php'); exit;
      }
    }
  }

  // Cancelar tutoría
  if ($action === 'cancel_tutoria') {
    $t = intval($_POST['tutoria_id'] ?? 0);
    if ($t) {
      $stmt = $pdo->prepare('UPDATE tutorias SET estado = "cancelada" WHERE id_tutoria = :id');
      $stmt->execute(['id' => $t]);
      header('Location: dashboard_admin.php'); exit;
    }
  }
}

?>

<section class="card">
  <h2>Panel Administrador</h2>
  <p class="muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>. Gestiona usuarios, supervisa tutorías y genera reportes.</p>
</section>

<div class="dashboard-grid admin-panel">
  <div class="card">
    <h3>Crear Usuario</h3>
    <p class="muted">Registra nuevos estudiantes o profesores.</p>
    <form method="post">
      <input type="hidden" name="action" value="create_user">
      <div class="field"><label>Nombre</label><input type="text" name="nombre" required></div>
      <div class="field"><label>Correo</label><input type="email" name="correo" required></div>
      <div class="field"><label>Rol</label>
        <select name="rol" required>
          <option value="estudiante">Estudiante</option>
          <option value="profesor">Profesor</option>
          <option value="administrador">Administrador</option>
        </select>
      </div>
      <div class="field"><label>Contraseña</label><input type="password" name="password" required></div>
      <div class="field"><label>Código estudiantil (opcional)</label><input type="text" name="codigo_estudiantil"></div>
      <div class="field"><label>Carrera (opcional)</label><input type="text" name="carrera"></div>
      <div class="field"><label>Semestre (opcional)</label><input type="number" name="semestre" min="1"></div>
      <div class="field"><label>Especialidad (profesor)</label><input type="text" name="especialidad"></div>
      <div class="field"><label>Departamento (profesor)</label><input type="text" name="departamento"></div>
      <button class="btn primary" type="submit">Crear Usuario</button>
    </form>
  </div>

  <div class="card">
    <h3>Crear Materia</h3>
    <p class="muted">Agrega una materia y opcionalmente asígnala a un profesor.</p>
    <form method="post">
      <input type="hidden" name="action" value="create_materia">
      <div class="field"><label>Nombre materia</label><input type="text" name="materia_nombre" required></div>
      <div class="field"><label>Asignar a profesor (opcional)</label>
        <select name="asign_prof">
          <option value="0">-- Ninguno --</option>
          <?php
          $ps = $pdo->query('SELECT p.id_profesor, u.nombre FROM profesores p JOIN usuarios u ON p.id_usuario = u.id_usuario ORDER BY u.nombre');
          while ($pp = $ps->fetch()) echo '<option value="'.intval($pp['id_profesor']).'">'.htmlspecialchars($pp['nombre']).'</option>';
          ?>
        </select>
      </div>
      <button class="btn" type="submit">Crear Materia</button>
    </form>
  </div>

  <div class="card">
    <h3>Estadísticas Generales</h3>
    <p class="muted">Resumen del sistema.</p>
    <?php
    $total_users = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $total_profesores = $pdo->query('SELECT COUNT(*) FROM profesores')->fetchColumn();
    $total_estudiantes = $pdo->query('SELECT COUNT(*) FROM estudiantes')->fetchColumn();
    $total_tutorias = $pdo->query('SELECT COUNT(*) FROM tutorias')->fetchColumn();
    $tutorias_pendientes = $pdo->query("SELECT COUNT(*) FROM tutorias WHERE estado = 'pendiente'")->fetchColumn();
    $tutorias_completadas = $pdo->query("SELECT COUNT(*) FROM tutorias WHERE estado = 'realizada'")->fetchColumn();
    ?>
    <ul>
      <li><strong>Total de usuarios:</strong> <?php echo $total_users; ?> (Profesores: <?php echo $total_profesores; ?>, Estudiantes: <?php echo $total_estudiantes; ?>)</li>
      <li><strong>Total de tutorías:</strong> <?php echo $total_tutorias; ?> (Pendientes: <?php echo $tutorias_pendientes; ?>, Completadas: <?php echo $tutorias_completadas; ?>)</li>
    </ul>
    <a class="btn primary" href="reportes.php">Ver Reportes Detallados</a>
  </div>
</div>

<section class="card">
  <h3>Usuarios</h3>
  <p class="muted">Lista de usuarios; bloquear/desbloquear, editar o eliminar.</p>
  <table class="table">
    <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php
    $stmt = $pdo->query('SELECT id_usuario, nombre, correo, rol, estado FROM usuarios ORDER BY id_usuario');
    while ($row = $stmt->fetch()) {
      echo '<tr>';
      echo '<td>'.htmlspecialchars($row['nombre']).'</td>';
      echo '<td>'.htmlspecialchars($row['correo']).'</td>';
      echo '<td>'.htmlspecialchars($row['rol']).'</td>';
      echo '<td>'.(htmlspecialchars($row['estado']) === 'inactivo' ? '<span class="chip cancelada">Inactivo</span>' : '<span class="chip aceptada">Activo</span>').'</td>';
      echo '<td>';
      if ($row['estado'] === 'inactivo') {
        echo '<form method="post" style="display:inline-block;margin-right:8px">';
        echo '<input type="hidden" name="action" value="toggle_state">';
        echo '<input type="hidden" name="user_id" value="'.intval($row['id_usuario']).'">';
        echo '<input type="hidden" name="state" value="activo">';
        echo '<button class="btn success" type="submit">Activar</button>';
        echo '</form>';
      } else {
        echo '<form method="post" style="display:inline-block;margin-right:8px">';
        echo '<input type="hidden" name="action" value="toggle_state">';
        echo '<input type="hidden" name="user_id" value="'.intval($row['id_usuario']).'">';
        echo '<input type="hidden" name="state" value="inactivo">';
        echo '<button class="btn danger" type="submit" onclick="return confirmAction(this.form, \"Cambiar estado a inactivo?\")">Desactivar</button>';
        echo '</form>';
      }
      echo '<form method="post" style="display:inline-block">';
      echo '<input type="hidden" name="action" value="delete_user">';
      echo '<input type="hidden" name="user_id" value="'.intval($row['id_usuario']).'">';
      echo '<button class="btn danger" type="submit" onclick="return confirmAction(this.form, \"Eliminar usuario?\")">Eliminar</button>';
      echo '</form>';
      echo '</td>';
      echo '</tr>';
    }
    ?>
    </tbody>
  </table>
</section>

<section class="card">
  <h3>Todas las Tutorías</h3>
  <p class="muted">Listado completo; cancelar si es necesario.</p>
  <table class="table">
    <thead><tr><th>Estudiante</th><th>Profesor</th><th>Materia</th><th>Fecha</th><th>Hora</th><th>Estado</th><th>Acción</th></tr></thead>
    <tbody>
    <?php
        $sql = 'SELECT t.id_tutoria AS id, t.fecha, t.hora, t.estado, uest.nombre AS estudiante, uprof.nombre AS profesor
            FROM tutorias t
            JOIN estudiantes est ON t.id_estudiante = est.id_estudiante
            JOIN usuarios uest ON est.id_usuario = uest.id_usuario
            JOIN profesores prof ON t.id_profesor = prof.id_profesor
            JOIN usuarios uprof ON prof.id_usuario = uprof.id_usuario
            ORDER BY t.fecha DESC, t.hora DESC';
        foreach ($pdo->query($sql) as $t) {
        echo '<tr>';
        echo '<td>'.htmlspecialchars($t['estudiante']).'</td>';
        echo '<td>'.htmlspecialchars($t['profesor']).'</td>';
          echo '<td>-</td>';
        echo '<td>'.htmlspecialchars($t['fecha']).'</td>';
        echo '<td>'.htmlspecialchars(substr($t['hora'],0,5)).'</td>';
        echo '<td><span class="chip '.htmlspecialchars($t['estado']).'">'.htmlspecialchars(ucfirst($t['estado'])).'</span></td>';
        echo '<td>';
        if ($t['estado'] !== 'cancelada' && $t['estado'] !== 'realizada') {
            echo '<form method="post">';
            echo '<input type="hidden" name="action" value="cancel_tutoria">';
            echo '<input type="hidden" name="tutoria_id" value="'.intval($t['id']).'">';
            echo '<button class="btn danger" type="submit" onclick="return confirmAction(this.form, \'Cancelar tutoría?\')">Cancelar</button>';
            echo '</form>';
        }
        echo '</td>';
        echo '</tr>';
    }
    ?>
    </tbody>
  </table>
</section>

<?php include_once 'footer.php'; ?>

