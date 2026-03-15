<?php
session_start(); // ← AGREGADO - Iniciar sesión
include_once 'header.php';
require_once 'conexion.php';
require_once 'csrf_helper.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrador') {
  header('Location: login.php'); exit;
}

// Procesar acciones administrativas (crear/editar/eliminar/activar usuarios, gestionar tutorías)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Verificar CSRF
  $csrf_token = $_POST['csrf_token'] ?? '';
  if (!verify_csrf_token($csrf_token)) {
    $error = 'Solicitud inválida. Por favor intenta nuevamente.';
  } else {
    $action = $_POST['action'] ?? '';

    // Cambiar estado (activo/inactivo)
    if ($action === 'toggle_state') {
      $u = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
      $newState = in_array($_POST['state'] ?? '', ['activo', 'inactivo']) ? $_POST['state'] : 'activo';
      if ($u && $u > 0) {
        $stmt = $pdo->prepare('UPDATE usuarios SET estado = :s WHERE id_usuario = :id');
        $stmt->execute(['s' => $newState, 'id' => $u]);
        header('Location: dashboard_admin.php'); exit;
      }
    }

    // Crear usuario (administrador puede crear profesores, administradores o estudiantes manualmente)
    if ($action === 'create_user') {
      $nombre = trim($_POST['nombre'] ?? '');
      $correo = filter_var(trim($_POST['correo'] ?? ''), FILTER_VALIDATE_EMAIL);
      $rol = in_array($_POST['rol'] ?? '', ['estudiante', 'profesor', 'administrador']) ? $_POST['rol'] : 'estudiante';
      $pass = $_POST['password'] ?? '';
      $codigo = trim($_POST['codigo_estudiantil'] ?? '');
      $carrera = trim($_POST['carrera'] ?? '');
      $semestre = filter_var($_POST['semestre'] ?? 0, FILTER_VALIDATE_INT);
      $especialidad = trim($_POST['especialidad'] ?? '');
      $departamento = trim($_POST['departamento'] ?? '');

      // Validaciones
      if (empty($nombre) || strlen($nombre) < 2 || strlen($nombre) > 100) {
        $error = 'El nombre debe tener entre 2 y 100 caracteres.';
      } elseif (!$correo) {
        $error = 'Correo electrónico inválido.';
      } elseif (empty($pass) || strlen($pass) < 4 || strlen($pass) > 100) {
        $error = 'La contraseña debe tener entre 4 y 100 caracteres.';
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
            if (strlen($codigo) < 3 || strlen($codigo) > 20) {
              $error = 'El código estudiantil debe tener entre 3 y 20 caracteres.';
            } else {
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

  // Editar usuario (nombre, correo, rol, estado)
  if ($action === 'edit_user') {
    $uid = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = filter_var(trim($_POST['correo'] ?? ''), FILTER_VALIDATE_EMAIL);
    $rol = in_array($_POST['rol'] ?? '', ['estudiante', 'profesor', 'administrador']) ? $_POST['rol'] : 'estudiante';
    $estado = in_array($_POST['estado'] ?? '', ['activo', 'inactivo']) ? $_POST['estado'] : 'activo';
    
    if ($uid && $uid > 0 && !empty($nombre) && strlen($nombre) >= 2 && strlen($nombre) <= 100 && $correo) {
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
    $u = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
    if ($u && $u > 0) {
      // No permitir autoeliminación
      if ($u == $_SESSION['user_id']) {
        $error = 'No puedes eliminar tu propio usuario.';
      } else {
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
            // Verificar si el profesor tiene inscripciones activas en sus disponibilidades
            $cntt = $pdo->prepare("SELECT COUNT(*) FROM tutoria_inscripciones ti 
                                   JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad 
                                   WHERE d.id_profesor = :p AND ti.estado IN ('inscrito','cancelado')");
            $cntt->execute(['p' => $idprof]);
            if ($cntt->fetchColumn() > 0) {
              $error = 'No se puede eliminar profesor con inscripciones activas. Primero cancele o complete las tutorías.';
            }
          }
        }

        // Si es estudiante, verificar tutorías activas
        if (empty($error) && $rol === 'estudiante') {
          $stmte = $pdo->prepare('SELECT id_estudiante FROM estudiantes WHERE id_usuario = :u LIMIT 1');
          $stmte->execute(['u' => $u]);
          $idest = $stmte->fetchColumn();
          if ($idest) {
            // Verificar si el estudiante tiene inscripciones activas
            $cntte = $pdo->prepare("SELECT COUNT(*) FROM tutoria_inscripciones WHERE id_estudiante = :e AND estado IN ('inscrito','cancelado')");
            $cntte->execute(['e' => $idest]);
            if ($cntte->fetchColumn() > 0) {
              $error = 'No se puede eliminar estudiante con inscripciones activas. Primero cancele las tutorías.';
            }
          }
        }

        if (empty($error)) {
          try {
            // Eliminación: borrar registros dependientes por FK (ON DELETE CASCADE) se encargará
            $del = $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = :id');
            $del->execute(['id' => $u]);
            header('Location: dashboard_admin.php?deleted=1'); exit;
          } catch (PDOException $e) {
            error_log("Error eliminando usuario: " . $e->getMessage());
            $error = 'Error al eliminar usuario. Puede que tenga datos relacionados.';
          }
        }
      }
    }
  }

  // Cancelar tutoría (disponibilidad)
  if ($action === 'cancel_tutoria') {
    $t = filter_var($_POST['tutoria_id'] ?? 0, FILTER_VALIDATE_INT);
    if ($t && $t > 0) {
      $stmt = $pdo->prepare('UPDATE disponibilidad SET estado = "cancelada" WHERE id_disponibilidad = :id');
      $stmt->execute(['id' => $t]);
      header('Location: dashboard_admin.php'); exit;
    }
  }

  // Acciones sobre inscripciones de tutorías
  if ($action === 'cancelar_tutoria') {
    $inscripcion_id = filter_var($_POST['inscripcion_id'] ?? 0, FILTER_VALIDATE_INT);
    if ($inscripcion_id && $inscripcion_id > 0) {
      try {
        // Obtener datos de la inscripción
        $stmt = $pdo->prepare('SELECT id_disponibilidad FROM tutoria_inscripciones WHERE id_inscripcion = :id');
        $stmt->execute(['id' => $inscripcion_id]);
        $inscripcion = $stmt->fetch();
        
        if ($inscripcion) {
          // Actualizar estado de la inscripción
          $stmt_update = $pdo->prepare('UPDATE tutoria_inscripciones SET estado = "cancelado" WHERE id_inscripcion = :id');
          $stmt_update->execute(['id' => $inscripcion_id]);
          
          // Reducir cupo_actual en disponibilidad
          $stmt_cupo = $pdo->prepare('UPDATE disponibilidad SET cupo_actual = cupo_actual - 1 WHERE id_disponibilidad = :id AND cupo_actual > 0');
          $stmt_cupo->execute(['id' => $inscripcion['id_disponibilidad']]);
          
          header('Location: dashboard_admin.php?tutoria_cancelled=1'); exit;
        }
      } catch (PDOException $e) {
        error_log("Error cancelando tutoría: " . $e->getMessage());
        $error = 'Error al cancelar la tutoría.';
      }
    }
  }
  
  // Eliminar completamente una tutoría (disponibilidad + todas sus inscripciones)
  if ($action === 'eliminar_tutoria_completa') {
    $disponibilidad_id = filter_var($_POST['disponibilidad_id'] ?? 0, FILTER_VALIDATE_INT);
    if ($disponibilidad_id && $disponibilidad_id > 0) {
      try {
        // Eliminar todas las inscripciones relacionadas
        $stmt_inscripciones = $pdo->prepare('DELETE FROM tutoria_inscripciones WHERE id_disponibilidad = :id');
        $stmt_inscripciones->execute(['id' => $disponibilidad_id]);
        
        // Eliminar relaciones de materias en disponibilidad_materias
        $stmt_materias = $pdo->prepare('DELETE FROM disponibilidad_materias WHERE id_disponibilidad = :id');
        $stmt_materias->execute(['id' => $disponibilidad_id]);
        
        // Eliminar la disponibilidad (la tutoría en sí)
        $stmt_disponibilidad = $pdo->prepare('DELETE FROM disponibilidad WHERE id_disponibilidad = :id');
        $stmt_disponibilidad->execute(['id' => $disponibilidad_id]);
        
        header('Location: dashboard_admin.php?tutoria_eliminada=1'); exit;
      } catch (PDOException $e) {
        error_log("Error eliminando tutoría completa: " . $e->getMessage());
        $error = 'Error al eliminar la tutoría completamente.';
      }
    }
  }
  
  if ($action === 'completar_tutoria') {
    $inscripcion_id = filter_var($_POST['inscripcion_id'] ?? 0, FILTER_VALIDATE_INT);
    if ($inscripcion_id && $inscripcion_id > 0) {
      try {
        $stmt = $pdo->prepare('UPDATE tutoria_inscripciones SET estado = "asistio" WHERE id_inscripcion = :id');
        $stmt->execute(['id' => $inscripcion_id]);
        header('Location: dashboard_admin.php?tutoria_completada=1'); exit;
      } catch (PDOException $e) {
        error_log("Error completando tutoría: " . $e->getMessage());
        $error = 'Error al marcar como asistió.';
      }
    }
  }
  
  if ($action === 'no_asistio_tutoria') {
    $inscripcion_id = filter_var($_POST['inscripcion_id'] ?? 0, FILTER_VALIDATE_INT);
    if ($inscripcion_id && $inscripcion_id > 0) {
      try {
        $stmt = $pdo->prepare('UPDATE tutoria_inscripciones SET estado = "no_asistio" WHERE id_inscripcion = :id');
        $stmt->execute(['id' => $inscripcion_id]);
        header('Location: dashboard_admin.php?tutoria_no_asistio=1'); exit;
      } catch (PDOException $e) {
        error_log("Error marcando no asistió: " . $e->getMessage());
        $error = 'Error al marcar como no asistió.';
      }
    }
  }
  
  if ($action === 'reactivar_tutoria') {
    $inscripcion_id = filter_var($_POST['inscripcion_id'] ?? 0, FILTER_VALIDATE_INT);
    if ($inscripcion_id && $inscripcion_id > 0) {
      try {
        // Obtener datos de la inscripción
        $stmt = $pdo->prepare('SELECT id_disponibilidad FROM tutoria_inscripciones WHERE id_inscripcion = :id');
        $stmt->execute(['id' => $inscripcion_id]);
        $inscripcion = $stmt->fetch();
        
        if ($inscripcion) {
          // Actualizar estado de la inscripción
          $stmt_update = $pdo->prepare('UPDATE tutoria_inscripciones SET estado = "inscrito" WHERE id_inscripcion = :id');
          $stmt_update->execute(['id' => $inscripcion_id]);
          
          // Aumentar cupo_actual en disponibilidad
          $stmt_cupo = $pdo->prepare('UPDATE disponibilidad SET cupo_actual = cupo_actual + 1 WHERE id_disponibilidad = :id');
          $stmt_cupo->execute(['id' => $inscripcion['id_disponibilidad']]);
          
          header('Location: dashboard_admin.php?tutoria_reactivada=1'); exit;
        }
      } catch (PDOException $e) {
        error_log("Error reactivando tutoría: " . $e->getMessage());
        $error = 'Error al reactivar la tutoría.';
      }
    }
  }
}
}

// Mostrar mensajes de éxito
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $success = 'Usuario eliminado exitosamente.';
}
if (isset($_GET['tutoria_cancelled']) && $_GET['tutoria_cancelled'] == 1) {
    $success = 'Tutoría cancelada exitosamente.';
}
if (isset($_GET['tutoria_eliminada']) && $_GET['tutoria_eliminada'] == 1) {
    $success = 'Tutoría eliminada completamente. Ya no aparecerá en el sistema.';
}
if (isset($_GET['tutoria_completada']) && $_GET['tutoria_completada'] == 1) {
    $success = 'Tutoría marcada como asistió exitosamente.';
}
if (isset($_GET['tutoria_no_asistio']) && $_GET['tutoria_no_asistio'] == 1) {
    $success = 'Tutoría marcada como no asistió exitosamente.';
}
if (isset($_GET['tutoria_reactivada']) && $_GET['tutoria_reactivada'] == 1) {
    $success = 'Tutoría reactivada exitosamente.';
}

?>

<section class="card">
  <h2>Panel Administrador</h2>
  <p class="muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>. Gestiona usuarios, supervisa tutorías y genera reportes.</p>
  <?php if (!empty($error)) { echo '<div class="alert error">'.htmlspecialchars($error).'</div>'; } ?>
  <?php if (!empty($success)) { echo '<div class="alert success">'.htmlspecialchars($success).'</div>'; } ?>
</section>

<div class="dashboard-grid admin-panel">
  <div class="card">
    <h3>Crear Profesor</h3>
    <p class="muted">Registra nuevos profesores en el sistema.</p>
    <form method="post">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="create_user">
      <input type="hidden" name="rol" value="profesor">
      <div class="field"><label>Nombre completo</label><input type="text" name="nombre" required></div>
      <div class="field"><label>Correo institucional</label><input type="email" name="correo" required></div>
      <div class="field"><label>Contraseña</label><input type="password" name="password" required></div>
      <div class="field"><label>Especialidad</label><input type="text" name="especialidad" placeholder="Ej: Matemáticas, Física, Programación" required></div>
      <div class="field"><label>Departamento</label><input type="text" name="departamento" placeholder="Ej: Ciencias Básicas, Ingeniería" required></div>
      <button class="btn primary" type="submit">Crear Profesor</button>
    </form>
  </div>

  <div class="card">
    <h3>Crear Estudiante</h3>
    <p class="muted">Registra nuevos estudiantes en el sistema.</p>
    <form method="post">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="create_user">
      <input type="hidden" name="rol" value="estudiante">
      <div class="field"><label>Nombre completo</label><input type="text" name="nombre" required></div>
      <div class="field"><label>Correo institucional</label><input type="email" name="correo" required></div>
      <div class="field"><label>Contraseña</label><input type="password" name="password" required></div>
      <div class="field"><label>Código estudiantil</label><input type="text" name="codigo_estudiantil" placeholder="Ej: 2023-1234" required></div>
      <div class="field"><label>Carrera</label><input type="text" name="carrera" placeholder="Ej: Ingeniería de Sistemas" required></div>
      <div class="field"><label>Semestre</label><input type="number" name="semestre" min="1" max="10" placeholder="Ej: 5" required></div>
      <button class="btn primary" type="submit">Crear Estudiante</button>
    </form>
  </div>

  <div class="card">
    <h3>Estadísticas del Sistema</h3>
    <p class="muted">Resumen general de usuarios y tutorías.</p>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
      <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <div style="font-size: 2rem; font-weight: bold; color: #3b82f6;"><?php echo $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(); ?></div>
        <div style="color: #666;">Usuarios Totales</div>
      </div>
      <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <div style="font-size: 2rem; font-weight: bold; color: #10b981;"><?php echo $pdo->query('SELECT COUNT(*) FROM profesores')->fetchColumn(); ?></div>
        <div style="color: #666;">Profesores</div>
      </div>
      <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <div style="font-size: 2rem; font-weight: bold; color: #f59e0b;"><?php echo $pdo->query('SELECT COUNT(*) FROM estudiantes')->fetchColumn(); ?></div>
        <div style="color: #666;">Estudiantes</div>
      </div>
      <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <div style="font-size: 2rem; font-weight: bold; color: #8b5cf6;"><?php echo $pdo->query('SELECT COUNT(*) FROM tutoria_inscripciones')->fetchColumn(); ?></div>
        <div style="color: #666;">Tutorías Totales</div>
      </div>
    </div>
    <a class="btn primary" href="reportes.php">Ver Reportes Detallados</a>
  </div>
</div>

<section class="card">
  <h3>Gestión de Tutorías</h3>
  <p class="muted">Administra inscripciones de tutorías (cancelar, marcar como completada) o elimina tutorías completas.</p>
  <table class="table">
    <thead><tr><th>Disponibilidad</th><th>Profesor</th><th>Fecha</th><th>Inscripciones</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php
    try {
      // Obtener disponibilidades con sus inscripciones
      $stmt = $pdo->query('
        SELECT 
          d.id_disponibilidad,
          d.fecha,
          d.hora_inicio,
          d.hora_fin,
          d.estado as disponibilidad_estado,
          d.cupo_maximo,
          d.cupo_actual,
          u_prof.nombre as profesor_nombre,
          COUNT(ti.id_inscripcion) as total_inscripciones,
          COUNT(CASE WHEN ti.estado = "inscrito" THEN 1 END) as inscripciones_activas,
          COUNT(CASE WHEN ti.estado = "cancelado" THEN 1 END) as inscripciones_canceladas,
          COUNT(CASE WHEN ti.estado IN ("asistio", "no_asistio") THEN 1 END) as inscripciones_completadas,
          GROUP_CONCAT(DISTINCT CONCAT(u_est.nombre, " (", ti.estado, ")") SEPARATOR ", ") as detalle_inscripciones
        FROM disponibilidad d
        JOIN profesores p ON d.id_profesor = p.id_profesor
        JOIN usuarios u_prof ON p.id_usuario = u_prof.id_usuario
        LEFT JOIN tutoria_inscripciones ti ON d.id_disponibilidad = ti.id_disponibilidad
        LEFT JOIN estudiantes e ON ti.id_estudiante = e.id_estudiante
        LEFT JOIN usuarios u_est ON e.id_usuario = u_est.id_usuario
        WHERE d.fecha >= CURDATE() - INTERVAL 30 DAY
        GROUP BY d.id_disponibilidad
        ORDER BY d.fecha DESC, d.hora_inicio DESC
        LIMIT 20
      ');
      
      while ($row = $stmt->fetch()) {
        echo '<tr>';
        echo '<td>';
        echo '<strong>ID: ' . $row['id_disponibilidad'] . '</strong><br>';
        echo substr($row['hora_inicio'], 0, 5) . ' - ' . substr($row['hora_fin'], 0, 5);
        echo '</td>';
        
        echo '<td>'.htmlspecialchars($row['profesor_nombre']).'</td>';
        echo '<td>'.htmlspecialchars($row['fecha']).'</td>';
        
        echo '<td>';
        echo '<strong>Total:</strong> ' . $row['total_inscripciones'] . '<br>';
        if ($row['inscripciones_activas'] > 0) {
          echo '<span style="color: green;">✅ Activas: ' . $row['inscripciones_activas'] . '</span><br>';
        }
        if ($row['inscripciones_canceladas'] > 0) {
          echo '<span style="color: orange;">❌ Canceladas: ' . $row['inscripciones_canceladas'] . '</span><br>';
        }
        if ($row['inscripciones_completadas'] > 0) {
          echo '<span style="color: blue;">✅ Completadas: ' . $row['inscripciones_completadas'] . '</span>';
        }
        echo '</td>';
        
        echo '<td>';
        // Botón para eliminar completamente la tutoría
        echo '<form method="post" style="display:inline-block;margin-bottom:5px">';
        echo csrf_field();
        echo '<input type="hidden" name="action" value="eliminar_tutoria_completa">';
        echo '<input type="hidden" name="disponibilidad_id" value="'.intval($row['id_disponibilidad']).'">';
        echo '<button class="btn danger" type="submit" onclick="return confirm(\'¿ELIMINAR COMPLETAMENTE esta tutoría?\\n\\n⚠️ Esta acción eliminará:\\n• Todas las inscripciones\\n• La disponibilidad\\n• No aparecerá más para nadie\\n\\n¿Estás seguro?\')">🗑️ Eliminar Tutoría</button>';
        echo '</form>';
        
        // Botón para ver detalles
        echo '<button class="btn" onclick="alert(\'Detalles:\\n\\n' . htmlspecialchars($row['detalle_inscripciones'] ?: 'Sin inscripciones') . '\')" style="font-size: 12px;">📋 Ver Detalles</button>';
        echo '</td>';
        
        echo '</tr>';
      }
      
      if ($stmt->rowCount() === 0) {
        echo '<tr><td colspan="5" style="text-align: center; color: #666;">No hay tutorías registradas</td></tr>';
      }
    } catch (Exception $e) {
      echo '<tr><td colspan="5" style="text-align: center; color: #666;">Error al cargar tutorías: '.$e->getMessage().'</td></tr>';
    }
    ?>
    </tbody>
  </table>
  
  <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #dc3545;">
    <h4 style="color: #dc3545; margin: 0 0 10px 0;">⚠️ Importante - Eliminación Completa</h4>
    <p style="margin: 0; color: #666;">
      <strong>🗑️ Eliminar Tutoría</strong>: Elimina permanentemente la tutoría completa incluyendo:<br>
      • Todas las inscripciones (activas, canceladas, completadas)<br>
      • La disponibilidad/horario<br>
      • Ya no aparecerá para profesores ni estudiantes<br>
      • <strong>Esta acción no se puede deshacer</strong>
    </p>
  </div>
</section>

<section class="card">
  <h3>Gestión Individual de Inscripciones</h3>
  <p class="muted">Cancela, reactiva o marca como completada inscripciones individuales.</p>
  <table class="table">
    <thead><tr><th>Estudiante</th><th>Profesor</th><th>Materia</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php
    try {
      $stmt = $pdo->query('
        SELECT 
          ti.id_inscripcion,
          ti.id_disponibilidad,
          ti.estado,
          u_est.nombre as estudiante_nombre,
          u_prof.nombre as profesor_nombre,
          m.nombre as materia_nombre,
          d.fecha as tutoria_fecha,
          d.hora_inicio as tutoria_hora
        FROM tutoria_inscripciones ti
        JOIN estudiantes e ON ti.id_estudiante = e.id_estudiante
        JOIN usuarios u_est ON e.id_usuario = u_est.id_usuario
        JOIN profesores p ON ti.id_profesor = p.id_profesor
        JOIN usuarios u_prof ON p.id_usuario = u_prof.id_usuario
        JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad
        LEFT JOIN materias m ON ti.id_materia = m.id_materia
        ORDER BY d.fecha ASC, d.hora_inicio ASC
        LIMIT 15
      ');
      
      while ($row = $stmt->fetch()) {
        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['estudiante_nombre']).'</td>';
        echo '<td>'.htmlspecialchars($row['profesor_nombre']).'</td>';
        echo '<td>'.htmlspecialchars($row['materia_nombre'] ?: 'Sin materia').'</td>';
        echo '<td>'.htmlspecialchars($row['tutoria_fecha']).' '.substr(htmlspecialchars($row['tutoria_hora']), 0, 5).'</td>';
        
        // Estado con color
        $color_estado = '';
        $texto_estado = ucfirst($row['estado']);
        switch($row['estado']) {
          case 'inscrito':
            $color_estado = 'success';
            $texto_estado = 'Inscrito';
            break;
          case 'cancelado':
            $color_estado = 'cancelada';
            $texto_estado = 'Cancelado';
            break;
          case 'asistio':
            $color_estado = 'success';
            $texto_estado = 'Asistió';
            break;
          case 'no_asistio':
            $color_estado = 'cancelada';
            $texto_estado = 'No asistió';
            break;
        }
        echo '<td><span class="chip '.$color_estado.'">'.$texto_estado.'</span></td>';
        
        // Acciones según estado
        echo '<td>';
        if ($row['estado'] === 'inscrito') {
          // Acciones para tutorías activas
          echo '<form method="post" style="display:inline-block;margin-right:5px">';
          echo csrf_field();
          echo '<input type="hidden" name="action" value="cancelar_tutoria">';
          echo '<input type="hidden" name="inscripcion_id" value="'.intval($row['id_inscripcion']).'">';
          echo '<button class="btn danger" type="submit" onclick="return confirm(\'¿Cancelar esta inscripción?\')">Cancelar</button>';
          echo '</form>';
          
          echo '<form method="post" style="display:inline-block;margin-right:5px">';
          echo csrf_field();
          echo '<input type="hidden" name="action" value="completar_tutoria">';
          echo '<input type="hidden" name="inscripcion_id" value="'.intval($row['id_inscripcion']).'">';
          echo '<button class="btn success" type="submit" onclick="return confirm(\'¿Marcar como asistió?\')">Asistió</button>';
          echo '</form>';
          
          echo '<form method="post" style="display:inline-block">';
          echo csrf_field();
          echo '<input type="hidden" name="action" value="no_asistio_tutoria">';
          echo '<input type="hidden" name="inscripcion_id" value="'.intval($row['id_inscripcion']).'">';
          echo '<button class="btn" type="submit" onclick="return confirm(\'¿Marcar como no asistió?\')">No Asistió</button>';
          echo '</form>';
        } elseif ($row['estado'] === 'cancelado') {
          // Opción de reactivar tutoría cancelada
          echo '<form method="post" style="display:inline-block">';
          echo csrf_field();
          echo '<input type="hidden" name="action" value="reactivar_tutoria">';
          echo '<input type="hidden" name="inscripcion_id" value="'.intval($row['id_inscripcion']).'">';
          echo '<button class="btn primary" type="submit" onclick="return confirm(\'¿Reactivar esta inscripción?\')">Reactivar</button>';
          echo '</form>';
        } else {
          // Tutorías completadas - solo mostrar
          echo '<span class="muted">Completada</span>';
        }
        echo '</td>';
        echo '</tr>';
      }
      
      if ($stmt->rowCount() === 0) {
        echo '<tr>';
        echo '<td colspan="5" style="text-align: center; padding: 40px; color: #6c757d; background: #f8f9fa;">';
        echo '<div style="font-size: 48px; margin-bottom: 10px;">👥</div>';
        echo '<h4 style="margin: 0 0 10px 0; color: #495057;">No hay usuarios registrados</h4>';
        echo '<p style="margin: 0; font-size: 14px;">Los usuarios aparecerán aquí cuando se registren en el sistema.</p>';
        echo '</td>';
        echo '</tr>';
      }
    } catch (Exception $e) {
      echo '<tr><td colspan="6" style="text-align: center; color: #666;">Error al cargar inscripciones: '.$e->getMessage().'</td></tr>';
    }
    ?>
    </tbody>
  </table>
</section>

<section class="card">
  <h3>Usuarios</h3>
  <p class="muted">Lista de usuarios; bloquear/desbloquear, editar o eliminar.</p>
  
  <!-- Desktop Table -->
  <div class="table-responsive table-card">
    <table class="table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Rol</th>
          <th>Estado</th>
          <th class="hide-mobile">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $stmt = $pdo->query('SELECT id_usuario, nombre, correo, rol, estado FROM usuarios ORDER BY id_usuario');
      while ($row = $stmt->fetch()) {
        echo '<tr>';
        echo '<td><strong>'.htmlspecialchars($row['nombre']).'</strong></td>';
        echo '<td>'.htmlspecialchars($row['correo']).'</td>';
        echo '<td><span class="chip '.htmlspecialchars($row['rol']).'">'.htmlspecialchars(ucfirst($row['rol'])).'</span></td>';
        echo '<td>'.(htmlspecialchars($row['estado']) === 'inactivo' ? '<span class="chip cancelada">Inactivo</span>' : '<span class="chip aceptada">Activo</span>').'</td>';
        echo '<td style="white-space: nowrap;">';
        if ($row['estado'] === 'inactivo') {
          echo '<form method="post" style="display:inline-block;margin-right:4px">';
          echo csrf_field();
          echo '<input type="hidden" name="action" value="toggle_state">';
          echo '<input type="hidden" name="user_id" value="'.intval($row['id_usuario']).'">';
          echo '<input type="hidden" name="state" value="activo">';
          echo '<button class="btn success" type="submit">✓ Activar</button>';
          echo '</form>';
        } else {
          echo '<form method="post" style="display:inline-block;margin-right:4px">';
          echo csrf_field();
          echo '<input type="hidden" name="action" value="toggle_state">';
          echo '<input type="hidden" name="user_id" value="'.intval($row['id_usuario']).'">';
          echo '<input type="hidden" name="state" value="inactivo">';
          echo '<button class="btn danger" type="submit" onclick="return confirm(\'¿Cambiar estado a inactivo?\')">✗ Desactivar</button>';
          echo '</form>';
        }
        echo '<form method="post" style="display:inline-block">';
        echo csrf_field();
        echo '<input type="hidden" name="action" value="delete_user">';
        echo '<input type="hidden" name="user_id" value="'.intval($row['id_usuario']).'">';
        echo '<button class="btn danger" type="submit" onclick="return confirm(\'¿Eliminar usuario permanentemente?\')">🗑️ Eliminar</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
      }
      ?>
      </tbody>
    </table>
  </div>
  
  <!-- Mobile Card Layout -->
  <div class="table-mobile">
    <?php
    $stmt = $pdo->query('SELECT id_usuario, nombre, correo, rol, estado FROM usuarios ORDER BY id_usuario');
    while ($row = $stmt->fetch()) {
      echo '<div class="item">';
      echo '<div class="item-header">'.htmlspecialchars($row['nombre']).'</div>';
      echo '<div class="item-row">';
      echo '<span class="item-label">Correo:</span>';
      echo '<span class="item-value">'.htmlspecialchars($row['correo']).'</span>';
      echo '</div>';
      echo '<div class="item-row">';
      echo '<span class="item-label">Rol:</span>';
      echo '<span class="item-value"><span class="chip '.htmlspecialchars($row['rol']).'">'.htmlspecialchars(ucfirst($row['rol'])).'</span></span>';
      echo '</div>';
      echo '<div class="item-row">';
      echo '<span class="item-label">Estado:</span>';
      echo '<span class="item-value">'.(htmlspecialchars($row['estado']) === 'inactivo' ? '<span class="chip cancelada">Inactivo</span>' : '<span class="chip aceptada">Activo</span>').'</span>';
      echo '</div>';
      echo '<div class="item-actions">';
      if ($row['estado'] === 'inactivo') {
        echo '<form method="post" style="display:inline-block">';
        echo csrf_field();
        echo '<input type="hidden" name="action" value="toggle_state">';
        echo '<input type="hidden" name="user_id" value="'.intval($row['id_usuario']).'">';
        echo '<input type="hidden" name="state" value="activo">';
        echo '<button class="btn success" type="submit">✓ Activar</button>';
        echo '</form>';
      } else {
        echo '<form method="post" style="display:inline-block">';
        echo csrf_field();
        echo '<input type="hidden" name="action" value="toggle_state">';
        echo '<input type="hidden" name="user_id" value="'.intval($row['id_usuario']).'">';
        echo '<input type="hidden" name="state" value="inactivo">';
        echo '<button class="btn danger" type="submit" onclick="return confirm(\'¿Cambiar estado a inactivo?\')">✗ Desactivar</button>';
        echo '</form>';
      }
      echo '<form method="post" style="display:inline-block">';
      echo csrf_field();
      echo '<input type="hidden" name="action" value="delete_user">';
      echo '<input type="hidden" name="user_id" value="'.intval($row['id_usuario']).'">';
      echo '<button class="btn danger" type="submit" onclick="return confirm(\'¿Eliminar usuario permanentemente?\')">🗑️ Eliminar</button>';
      echo '</form>';
      echo '</div>';
      echo '</div>';
    }
    ?>
  </div>
</section>

<?php include_once 'footer.php'; ?>

