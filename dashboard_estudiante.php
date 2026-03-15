<?php
session_start(); // ← AGREGADO - Iniciar sesión
include_once 'header.php';
require_once 'conexion.php';
require_once 'csrf_helper.php';

// Control de acceso básico
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'estudiante') {
		header('Location: login.php'); exit;
}

// Procesar reserva enviada desde este dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reservar') {
    // Verificar CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Solicitud inválida. Por favor intenta nuevamente.';
    } else {
        $disponibilidad_id = filter_var($_POST['disponibilidad_id'] ?? 0, FILTER_VALIDATE_INT);
        $materia_id = filter_var($_POST['materia_id'] ?? 0, FILTER_VALIDATE_INT);

        // Validaciones básicas
        if (!$disponibilidad_id || $disponibilidad_id <= 0) {
            $error = 'Disponibilidad inválida.';
        } else {
            // Si no se envió materia_id o es inválido, obtener la materia de la disponibilidad
            if (!$materia_id || $materia_id <= 0) {
                $stmtMateria = $pdo->prepare('SELECT id_materia FROM disponibilidad WHERE id_disponibilidad = :disp_id LIMIT 1');
                $stmtMateria->execute(['disp_id' => $disponibilidad_id]);
                $materia_id = $stmtMateria->fetchColumn();
                
                // Si aún no hay materia, permitir continuar (puede ser NULL)
                if (!$materia_id) {
                    $materia_id = null;
                }
            }
            // Obtener id_estudiante correspondiente al usuario actual
            $stmtEst = $pdo->prepare('SELECT id_estudiante FROM estudiantes WHERE id_usuario = :u LIMIT 1');
            $stmtEst->execute(['u' => $_SESSION['user_id']]);
            $id_est = $stmtEst->fetchColumn();
            
            if (!$id_est) {
                $error = 'No se encontró tu registro de estudiante. Contacta al administrador.';
            } else {
                // Verificar disponibilidad y cupos
                $stmtDisp = $pdo->prepare('
                    SELECT d.*, p.id_profesor, 
                           (d.cupo_maximo - d.cupo_actual) as cupos_disponibles
                    FROM disponibilidad d 
                    JOIN profesores p ON d.id_profesor = p.id_profesor
                    WHERE d.id_disponibilidad = :disp_id 
                      AND d.fecha >= CURDATE()
                      AND d.estado = "disponible"
                    LIMIT 1
                ');
                $stmtDisp->execute(['disp_id' => $disponibilidad_id]);
                $disp = $stmtDisp->fetch();

                if (!$disp) {
                    $error = 'La disponibilidad seleccionada no existe o ya no está disponible.';
                } elseif ($disp['cupos_disponibles'] <= 0) {
                    $error = 'No hay cupos disponibles para esta tutoría. Todos los cupos han sido ocupados.';
                } else {
                    // Verificar si el estudiante ya está inscrito
                    $stmtInscrito = $pdo->prepare('
                        SELECT COUNT(*) FROM tutoria_inscripciones 
                        WHERE id_disponibilidad = :disp_id 
                          AND id_estudiante = :est 
                          AND estado = "inscrito"
                    ');
                    $stmtInscrito->execute([
                        'disp_id' => $disponibilidad_id,
                        'est' => $id_est
                    ]);
                    
                    if ($stmtInscrito->fetchColumn() > 0) {
                        $error = 'Ya estás inscrito en esta tutoría.';
                    } else {
                        // Intentar inscribir al estudiante
                        try {
                            // Primero verificar si hay un registro cancelado para reactivarlo
                            $stmtReactivar = $pdo->prepare('
                                SELECT id_inscripcion FROM tutoria_inscripciones 
                                WHERE id_disponibilidad = :disp_id 
                                  AND id_estudiante = :est 
                                  AND estado = "cancelado"
                                LIMIT 1
                            ');
                            $stmtReactivar->execute([
                                'disp_id' => $disponibilidad_id,
                                'est' => $id_est
                            ]);
                            $registro_cancelado = $stmtReactivar->fetch();
                            
                            if ($registro_cancelado) {
                                // Reactivar el registro cancelado
                                $stmt = $pdo->prepare('
                                    UPDATE tutoria_inscripciones 
                                    SET estado = "inscrito", id_materia = :mat, id_profesor = :prof
                                    WHERE id_inscripcion = :id_ins
                                ');
                                $stmt->execute([
                                    'id_ins' => $registro_cancelado['id_inscripcion'],
                                    'mat' => $materia_id,
                                    'prof' => $disp['id_profesor']
                                ]);
                                
                                // Actualizar la materia en la tabla de inscripciones_materias (solo si hay materia válida)
                                if ($materia_id) {
                                    $stmt_del = $pdo->prepare('DELETE FROM tutoria_inscripciones_materias WHERE id_inscripcion = :id_ins');
                                    $stmt_del->execute(['id_ins' => $registro_cancelado['id_inscripcion']]);
                                    
                                    $stmt_ins = $pdo->prepare('INSERT INTO tutoria_inscripciones_materias (id_inscripcion, id_materia) VALUES (:id_ins, :mat)');
                                    $stmt_ins->execute(['id_ins' => $registro_cancelado['id_inscripcion'], 'mat' => $materia_id]);
                                }
                            } else {
                                // Crear nuevo registro si no existe cancelado
                                $stmt = $pdo->prepare('
                                    INSERT INTO tutoria_inscripciones 
                                    (id_disponibilidad, id_estudiante, id_profesor, id_materia, estado) 
                                    VALUES (:disp, :est, :prof, :mat, "inscrito")
                                ');
                                $stmt->execute([
                                    'disp' => $disponibilidad_id,
                                    'est' => $id_est,
                                    'prof' => $disp['id_profesor'],
                                    'mat' => $materia_id
                                ]);
                                
                                // Guardar la materia seleccionada (solo si hay materia válida)
                                $inscripcion_id = $pdo->lastInsertId();
                                if ($materia_id) {
                                    try {
                                        $stmt_mat = $pdo->prepare('INSERT INTO tutoria_inscripciones_materias (id_inscripcion, id_materia) VALUES (:ins, :mat)');
                                        $stmt_mat->execute(['ins' => $inscripcion_id, 'mat' => $materia_id]);
                                    } catch (Exception $e) {
                                        // Si la tabla no existe, la materia ya se guardó en el campo id_materia de la inscripción principal
                                        error_log("Tabla tutoria_inscripciones_materias no existe, usando materia principal: " . $e->getMessage());
                                    }
                                }
                            }
                            
                            // Actualizar cupo_actual en disponibilidad
                            $stmtUpdateCupo = $pdo->prepare('
                                UPDATE disponibilidad 
                                SET cupo_actual = cupo_actual + 1 
                                WHERE id_disponibilidad = :disp_id
                            ');
                            $stmtUpdateCupo->execute(['disp_id' => $disponibilidad_id]);
                            
                            header('Location: dashboard_estudiante.php?success=1'); exit;
                        } catch (PDOException $e) {
                            error_log("Error en inscripción: " . $e->getMessage());
                            $msg = $e->getMessage();
                            if (stripos($msg, 'cupo') !== false || $e->getCode() === '45000') {
                                $error = 'No hay cupos disponibles para esta tutoría. Intenta con otro horario.';
                            } elseif (stripos($msg, 'ya está inscrito') !== false) {
                                $error = 'Ya estás inscrito en esta tutoría.';
                            } else {
                                $error = 'Error al inscribirte. Intenta nuevamente más tarde o contacta al soporte.';
                            }
                        }
                    }
                }
            }
        }
    }
}

// Procesar cancelación de inscripción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancelar_inscripcion') {
    // Verificar CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Solicitud inválida. Por favor intenta nuevamente.';
    } else {
        $disponibilidad_id = filter_var($_POST['disponibilidad_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if (!$disponibilidad_id || $disponibilidad_id <= 0) {
            $error = 'Disponibilidad inválida.';
        } else {
            // Obtener id_estudiante correspondiente al usuario actual
            $stmtEst = $pdo->prepare('SELECT id_estudiante FROM estudiantes WHERE id_usuario = :u LIMIT 1');
            $stmtEst->execute(['u' => $_SESSION['user_id']]);
            $id_est = $stmtEst->fetchColumn();
            
            if (!$id_est) {
                $error = 'No se encontró tu registro de estudiante. Contacta al administrador.';
            } else {
                // Cancelar la inscripción
                try {
                    $stmt = $pdo->prepare('
                        UPDATE tutoria_inscripciones 
                        SET estado = "cancelado" 
                        WHERE id_disponibilidad = :disp_id 
                          AND id_estudiante = :est 
                          AND estado = "inscrito"
                    ');
                    $stmt->execute([
                        'disp_id' => $disponibilidad_id,
                        'est' => $id_est
                    ]);
                    
                    // Actualizar cupo_actual en disponibilidad
                    $stmtUpdateCupo = $pdo->prepare('
                        UPDATE disponibilidad 
                        SET cupo_actual = cupo_actual - 1 
                        WHERE id_disponibilidad = :disp_id 
                          AND cupo_actual > 0
                    ');
                    $stmtUpdateCupo->execute(['disp_id' => $disponibilidad_id]);
                    
                    header('Location: dashboard_estudiante.php?success=2'); exit;
                } catch (PDOException $e) {
                    error_log("Error al cancelar inscripción: " . $e->getMessage());
                    $error = 'Error al cancelar la inscripción. Intenta nuevamente más tarde.';
                }
            }
        }
    }
}

if (($_POST['action'] ?? '') === 'add_opinion') {
    $id_tutoria = filter_var($_POST['id_tutoria'] ?? 0, FILTER_VALIDATE_INT);
    $id_profesor = filter_var($_POST['id_profesor'] ?? 0, FILTER_VALIDATE_INT);
    $calificacion = filter_var($_POST['calificacion'] ?? 0, FILTER_VALIDATE_INT);
    $comentario = trim($_POST['comentario'] ?? '');
    
    if ($id_tutoria && $id_profesor && $calificacion >= 1 && $calificacion <= 5) {
        try {
            // Verificar que la tutoría pertenezca a este estudiante
            $stmt_check = $pdo->prepare('
                SELECT ti.id_inscripcion 
                FROM tutoria_inscripciones ti 
                WHERE ti.id_inscripcion = :id AND ti.id_estudiante = :est
            ');
            $stmt_check->execute(['id' => $id_tutoria, 'est' => $id_est]);
            
            if ($stmt_check->fetchColumn()) {
                // Insertar opinión
                $stmt = $pdo->prepare('
                    INSERT INTO opiniones 
                    (id_tutoria, id_emisor, id_receptor, tipo_emisor, tipo_receptor, calificacion, comentario) 
                    VALUES (:tutoria, :emisor, :receptor, :tipo_emisor, :tipo_receptor, :calif, :coment)
                ');
                $stmt->execute([
                    'tutoria' => $id_tutoria,
                    'emisor' => $_SESSION['user_id'],
                    'receptor' => $id_profesor,
                    'tipo_emisor' => 'estudiante',
                    'tipo_receptor' => 'profesor',
                    'calif' => $calificacion,
                    'coment' => $comentario ?: null
                ]);
                
                header('Location: dashboard_estudiante.php?success=3'); exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Ya has opinado sobre este profesor para esta tutoría.';
            } else {
                $error = 'Error al guardar la opinión. Intenta nuevamente.';
            }
        }
    } else {
        $error = 'Datos inválidos para la opinión.';
    }
}

// Mostrar mensaje de éxito si existe
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = '¡Inscripción realizada exitosamente!';
} elseif (isset($_GET['success']) && $_GET['success'] == 2) {
    $success = '¡Inscripción cancelada exitosamente!';
} elseif (isset($_GET['success']) && $_GET['success'] == 3) {
    $success = '¡Opinión enviada exitosamente!';
}

?>

<section class="card">
  <h2>Panel Estudiante</h2>
  <p class="muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>. Reserva tutorías y revisa tu historial.</p>
  <?php if (!empty($error)) { echo '<div class="alert error">'.htmlspecialchars($error).'</div>'; } ?>
  <?php if (!empty($success)) { echo '<div class="alert success">'.htmlspecialchars($success).'</div>'; } ?>
</section>

<div class="dashboard-grid student-panel">
  <div class="card">
    <h3>Tutorías Disponibles</h3>
    <p class="muted">Inscríbete en tutorías con cupos disponibles.</p>
    <table class="table">
      <thead><tr><th>Profesor</th><th>Materia</th><th>Fecha</th><th>Hora</th><th>Cupos</th><th>Acción</th></tr></thead>
      <tbody>
      <?php
        // Obtener ID del estudiante actual
        $stmtEst = $pdo->prepare('SELECT id_estudiante FROM estudiantes WHERE id_usuario = :uid LIMIT 1');
        $stmtEst->execute(['uid' => $_SESSION['user_id']]);
        $id_estudiante = $stmtEst->fetchColumn();
        
        // Consultar disponibilidades próximas con cupos y estado de inscripción
        $sql = "SELECT d.id_disponibilidad, d.id_profesor, d.id_materia, uprof.nombre AS profesor, 
                       d.fecha, d.hora_inicio, d.hora_fin, d.cupo_maximo, d.cupo_actual,
                       (d.cupo_maximo - d.cupo_actual) as cupos_disponibles,
                       CASE 
                         WHEN ti.id_inscripcion IS NOT NULL AND ti.estado = 'inscrito' THEN 'inscrito'
                         WHEN ti.id_inscripcion IS NOT NULL AND ti.estado = 'cancelado' THEN 'cancelado'
                         ELSE 'disponible' 
                       END as estado_inscripcion,
                       ti.id_inscripcion as inscripcion_id,
                       ti.estado as inscripcion_estado
                FROM disponibilidad d
                JOIN profesores prof ON d.id_profesor = prof.id_profesor
                JOIN usuarios uprof ON prof.id_usuario = uprof.id_usuario
                LEFT JOIN tutoria_inscripciones ti ON d.id_disponibilidad = ti.id_disponibilidad AND ti.id_estudiante = :id_est 
                WHERE d.fecha >= CURDATE() AND d.estado = 'disponible'
                ORDER BY d.fecha, d.hora_inicio";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_est' => $id_estudiante]);
        
        foreach ($stmt as $row) {
          $hora_display = substr($row['hora_inicio'],0,5);
          echo '<tr>';
          echo '<td>'.htmlspecialchars($row['profesor']).'</td>';
          
          // Obtener materias disponibles para esta disponibilidad
          $matOptions = [];
          try {
            $stmtMat = $pdo->prepare("
              SELECT m.id_materia, m.nombre 
              FROM materias m 
              JOIN disponibilidad_materias dm ON m.id_materia = dm.id_materia 
              WHERE dm.id_disponibilidad = :disp_id 
              ORDER BY m.nombre
            ");
            $stmtMat->execute(['disp_id' => $row['id_disponibilidad']]);
            while ($m = $stmtMat->fetch()) {
              $matOptions[] = $m;
            }
          } catch (Exception $e) {
            // Si la tabla no existe, obtener materia desde disponibilidad.id_materia
            if ($row['id_materia']) {
              $stmtMat = $pdo->prepare("SELECT id_materia, nombre FROM materias WHERE id_materia = :mat_id");
              $stmtMat->execute(['mat_id' => $row['id_materia']]);
              $m = $stmtMat->fetch();
              if ($m) {
                $matOptions[] = $m;
              }
            }
          }

          // Mostrar materias
          echo '<td>';
          if (count($matOptions) === 0) {
            echo '-';
          } elseif (count($matOptions) === 1) {
            echo htmlspecialchars($matOptions[0]['nombre']);
          } else {
            echo '<select form="form_'.intval($row['id_disponibilidad']).'" name="materia_id">';
            foreach ($matOptions as $opt) {
              echo '<option value="'.intval($opt['id_materia']).'">'.htmlspecialchars($opt['nombre']).'</option>';
            }
            echo '</select>';
          }
          echo '</td>';
          
          echo '<td>'.htmlspecialchars($row['fecha']).'</td>';
          echo '<td>'.htmlspecialchars($hora_display).'</td>';
          
          // Mostrar cupos con indicador visual
          echo '<td>';
          $cupos_disponibles = $row['cupos_disponibles'];
          $cupo_maximo = $row['cupo_maximo'];
          $porcentaje_lleno = ($row['cupo_actual'] / $cupo_maximo) * 100;
          
          if ($cupos_disponibles <= 0) {
            echo '<span class="chip cancelada">Lleno (0/'.$cupo_maximo.')</span>';
          } elseif ($porcentaje_lleno >= 75) {
            echo '<span class="chip" style="background-color: #ff9800; color: white;">'.$cupos_disponibles.'/'.$cupo_maximo.' cupos</span>';
          } else {
            echo '<span class="chip success">'.$cupos_disponibles.'/'.$cupo_maximo.' cupos</span>';
          }
          echo '</td>';
          
          echo '<td>';
          // Verificar estado de inscripción y mostrar botones apropiados
          if ($row['estado_inscripcion'] === 'inscrito') {
            echo '<span class="chip success">✓ Ya inscrito</span>';
            echo '<br><br>';
            echo '<form method="post" style="display:inline-block;">';
            echo csrf_field();
            echo '<input type="hidden" name="action" value="cancelar_inscripcion">';
            echo '<input type="hidden" name="disponibilidad_id" value="'.intval($row['id_disponibilidad']).'">';
            echo '<button class="btn danger" type="submit" onclick="return confirm(\'¿Estás seguro de cancelar esta inscripción?\')">Cancelar Inscripción</button>';
            echo '</form>';
          } elseif ($row['estado_inscripcion'] === 'cancelado') {
            echo '<span class="chip" style="background-color: #ff9800; color: white;">⚠ Inscripción cancelada</span>';
            echo '<br><br>';
            echo '<form id="form_'.intval($row['id_disponibilidad']).'" method="post" style="display:inline-block">';
            echo csrf_field();
            echo '<input type="hidden" name="action" value="reservar">';
            echo '<input type="hidden" name="disponibilidad_id" value="'.intval($row['id_disponibilidad']).'">';
            
            if (count($matOptions) === 1) {
              echo '<input type="hidden" name="materia_id" value="'.intval($matOptions[0]['id_materia']).'">';
            } elseif (count($matOptions) === 0) {
              echo '<input type="hidden" name="materia_id" value="0">';
            }
            
            echo '<button class="btn primary" type="submit" onclick="return confirmAction(this.form, \'¿Confirmar reinscripción en esta tutoría?\')">Reinscribirse</button>';
            echo '</form>';
          } elseif ($cupos_disponibles <= 0) {
            echo '<span class="muted">No disponible</span>';
          } else {
            echo '<form id="form_'.intval($row['id_disponibilidad']).'" method="post" style="display:inline-block">';
            echo csrf_field();
            echo '<input type="hidden" name="action" value="reservar">';
            echo '<input type="hidden" name="disponibilidad_id" value="'.intval($row['id_disponibilidad']).'">';
            
            if (count($matOptions) === 1) {
              echo '<input type="hidden" name="materia_id" value="'.intval($matOptions[0]['id_materia']).'">';
            } elseif (count($matOptions) === 0) {
              echo '<input type="hidden" name="materia_id" value="0">';
            }
            
            echo '<button class="btn primary" type="submit" onclick="return confirmAction(this.form, \'Confirmar inscripción en esta tutoría?\')">Inscribirse</button>';
            echo '</form>';
          }
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
        // Historial: usar la nueva tabla tutoria_inscripciones
        $sqlHist = 'SELECT 
            ti.id_inscripcion AS id, 
            d.fecha, 
            d.hora_inicio as hora, 
            ti.estado, 
            uprof.nombre AS profesor, 
            COALESCE(m.nombre, "-") AS materia,
            ti.fecha_inscripcion
            FROM tutoria_inscripciones ti
            JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad
            JOIN estudiantes est ON ti.id_estudiante = est.id_estudiante
            JOIN profesores prof ON ti.id_profesor = prof.id_profesor
            JOIN usuarios uprof ON prof.id_usuario = uprof.id_usuario
            LEFT JOIN materias m ON m.id_materia = ti.id_materia
            WHERE est.id_usuario = :uid
            ORDER BY d.fecha DESC, d.hora_inicio DESC';
        $stmt = $pdo->prepare($sqlHist);
        $stmt->execute(['uid' => $_SESSION['user_id']]);
        while ($t = $stmt->fetch()) {
          echo '<tr>';
          echo '<td>'.htmlspecialchars($t['profesor']).'</td>';
          echo '<td>'.htmlspecialchars($t['materia']).'</td>';
          echo '<td>'.htmlspecialchars($t['fecha']).'</td>';
          echo '<td>'.htmlspecialchars(substr($t['hora'],0,5)).'</td>';
          
          // Mapear estados de inscripción a estados visuales
          $estado_visual = $t['estado'];
          $color_estado = '';
          switch($t['estado']) {
            case 'inscrito':
              $estado_visual = 'Inscrito';
              $color_estado = 'success';
              break;
            case 'cancelado':
              $estado_visual = 'Cancelado';
              $color_estado = 'cancelada';
              break;
            case 'asistio':
              $estado_visual = 'Asistió';
              $color_estado = 'success';
              break;
            case 'no_asistio':
              $estado_visual = 'No asistió';
              $color_estado = 'cancelada';
              break;
            default:
              $estado_visual = ucfirst($t['estado']);
              $color_estado = $t['estado'];
          }
          
          echo '<td><span class="chip '.htmlspecialchars($color_estado).'">'.htmlspecialchars($estado_visual).'</span></td>';
          echo '</tr>';
        }
        if ($stmt->rowCount() === 0) {
          echo '<tr><td colspan="5" style="text-align: center; color: #666;">No tienes inscripciones registradas</td></tr>';
        }
      ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Sección de Opiniones sobre Profesores -->
<section class="card">
  <h3>Opinar sobre Profesores</h3>
  <p class="muted">Califica y deja comentarios sobre los profesores de tus tutorías completadas.</p>
  
  <?php
  // Obtener tutorías completadas donde el estudiante puede opinar
  try {
    $stmt_opiniones = $pdo->prepare('
      SELECT 
        ti.id_inscripcion,
        ti.estado,
        u_prof.id_usuario as profesor_id,
        u_prof.nombre as profesor_nombre,
        d.fecha as tutoria_fecha,
        m.nombre as materia_nombre,
        o.id_opinion,
        o.calificacion as calificacion_existente,
        o.comentario as comentario_existente
      FROM tutoria_inscripciones ti
      JOIN profesores p ON ti.id_profesor = p.id_profesor
      JOIN usuarios u_prof ON p.id_usuario = u_prof.id_usuario
      JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad
      LEFT JOIN materias m ON d.id_materia = m.id_materia
      LEFT JOIN opiniones o ON o.id_tutoria = d.id_disponibilidad 
                          AND o.id_emisor = :emisor_id 
                          AND o.tipo_emisor = "estudiante"
      WHERE ti.id_estudiante = :estudiante_id 
        AND ti.estado IN ("asistio", "no_asistio")
        AND d.fecha < CURDATE()
      ORDER BY d.fecha DESC
    ');
    $stmt_opiniones->execute(['emisor_id' => $id_est, 'estudiante_id' => $id_est]);
    $tutorias_para_opinar = $stmt_opiniones->fetchAll();
    
    // Si no hay tutorías para opinar, mostrar mensaje apropiado
    if (empty($tutorias_para_opinar)) {
      echo '<div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #2196f3;">';
      echo '<h4 style="color: #1565c0; margin: 0 0 10px 0;">� No hay tutorías para opinar</h4>';
      echo '<p style="color: #1565c0; margin: 0;">Aún no has completado tutorías donde puedas dejar tu opinión.</p>';
      echo '<p style="margin: 10px 0; color: #666;">Las tutorías completadas aparecerán aquí para que puedas calificar a tus profesores.</p>';
      echo '</div>';
    }
    
  } catch (Exception $e) {
    echo '<div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107;">';
    echo '<h4 style="color: #856404; margin: 0 0 10px 0;">� Error en el Sistema de Opiniones</h4>';
    echo '<p style="color: #856404; margin: 0;">Error al cargar las tutorías para opinar: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    $tutorias_para_opinar = [];
  }
  
  if (empty($tutorias_para_opinar)) {
    echo '<p style="text-align: center; color: #666;">No tienes tutorías completadas para opinar.</p>';
  } else {
    foreach ($tutorias_para_opinar as $tutoria) {
      echo '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; background: #f9f9f9;">';
      echo '<h4 style="margin: 0 0 10px 0; color: #333;">' . htmlspecialchars($tutoria['profesor_nombre']) . '</h4>';
      echo '<p style="margin: 5px 0; color: #666; font-size: 14px;">';
      echo 'Materia: ' . htmlspecialchars($tutoria['materia_nombre'] ?: 'Sin materia') . ' | ';
      echo 'Fecha: ' . htmlspecialchars($tutoria['tutoria_fecha']) . ' | ';
      echo 'Estado: <span class="chip ' . ($tutoria['estado'] === 'asistio' ? 'success' : 'cancelada') . '">' . 
           ucfirst($tutoria['estado']) . '</span>';
      echo '</p>';
      
      if ($tutoria['id_opinion']) {
        // Ya existe una opinión
        echo '<div style="margin-top: 10px; padding: 10px; background: #e8f5e8; border-radius: 5px;">';
        echo '<div style="margin-bottom: 5px;">';
        echo '<strong>Tu calificación:</strong> ';
        for ($i = 1; $i <= 5; $i++) {
          echo $i <= $tutoria['calificacion_existente'] ? '⭐' : '☆';
        }
        echo ' (' . $tutoria['calificacion_existente'] . '/5)';
        echo '</div>';
        if ($tutoria['comentario_existente']) {
          echo '<div><strong>Tu comentario:</strong> ' . htmlspecialchars($tutoria['comentario_existente']) . '</div>';
        }
        echo '</div>';
      } else {
        // Formulario para nueva opinión
        echo '<form method="post" style="margin-top: 10px;">';
        echo csrf_field();
        echo '<input type="hidden" name="action" value="add_opinion">';
        echo '<input type="hidden" name="id_tutoria" value="' . $tutoria['id_inscripcion'] . '">';
        echo '<input type="hidden" name="id_profesor" value="' . $tutoria['profesor_id'] . '">';
        
        echo '<div style="margin-bottom: 10px;">';
        echo '<label style="display: block; margin-bottom: 5px; font-weight: bold;">Calificación:</label>';
        echo '<select name="calificacion" required style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">';
        echo '<option value="">Selecciona una calificación</option>';
        for ($i = 1; $i <= 5; $i++) {
          echo '<option value="' . $i . '">' . $i . ' estrella' . ($i > 1 ? 's' : '') . '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        echo '<div style="margin-bottom: 10px;">';
        echo '<label style="display: block; margin-bottom: 5px; font-weight: bold;">Comentario (opcional):</label>';
        echo '<textarea name="comentario" rows="3" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd; resize: vertical;" placeholder="Ej: Excelente explicación, muy paciente, resuelve dudas..."></textarea>';
        echo '</div>';
        
        echo '<button type="submit" class="btn primary" style="padding: 8px 16px;">Enviar Opinión</button>';
        echo '</form>';
      }
      
      echo '</div>';
    }
  }
  ?>
</section>

<!-- Sección de Opiniones Recibidas -->
<section class="card">
  <h3>Opiniones Recibidas de Profesores</h3>
  <p class="muted">Ve lo que los profesores opinan sobre tu desempeño como estudiante.</p>
  
  <?php
  // Obtener opiniones recibidas de profesores
  try {
    $stmt_recibidas = $pdo->prepare('
      SELECT 
        o.calificacion,
        o.comentario,
        o.fecha_opinion,
        u_emisor.nombre as profesor_nombre,
        m.nombre as materia_nombre
      FROM opiniones o
      JOIN usuarios u_emisor ON o.id_emisor = u_emisor.id_usuario
      JOIN tutoria_inscripciones ti ON o.id_tutoria = ti.id_inscripcion
      JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad
      LEFT JOIN materias m ON d.id_materia = m.id_materia
      WHERE o.id_receptor = :est_id 
        AND o.tipo_receptor = "estudiante"
        AND o.estado = "visible"
      ORDER BY o.fecha_opinion DESC
      LIMIT 10
    ');
    $stmt_recibidas->execute(['est_id' => $_SESSION['user_id']]);
    $opiniones_recibidas = $stmt_recibidas->fetchAll();
    
    // Calcular promedio
    try {
      $stmt_promedio = $pdo->prepare('
        SELECT AVG(calificacion) as promedio, COUNT(*) as total
        FROM opiniones 
        WHERE id_receptor = :est_id 
          AND tipo_receptor = "estudiante"
          AND estado = "visible"
      ');
      $stmt_promedio->execute(['est_id' => $_SESSION['user_id']]);
      $promedio_data = $stmt_promedio->fetch();
    } catch (Exception $e) {
      $promedio_data = ['promedio' => 0, 'total' => 0];
    }
  } catch (Exception $e) {
    $opiniones_recibidas = [];
    $promedio_data = ['promedio' => 0, 'total' => 0];
  }
  
  // Mostrar promedio y estadísticas si hay opiniones
  if ($promedio_data['total'] > 0) {
    echo '<div style="text-align: center; margin: 20px 0; padding: 15px; background: #f0f8ff; border-radius: 8px;">';
    echo '<div style="font-size: 24px; font-weight: bold; color: #2c5aa0;">';
    for ($i = 1; $i <= 5; $i++) {
      echo $i <= round($promedio_data['promedio']) ? '⭐' : '☆';
    }
    echo '</div>';
    echo '<div style="color: #666;">';
    echo 'Promedio: ' . number_format($promedio_data['promedio'], 1) . '/5.0 (' . $promedio_data['total'] . ' opiniones)';
    echo '</div>';
    echo '</div>';
  }
  
  if (empty($opiniones_recibidas)) {
    echo '<p style="text-align: center; color: #666;">Aún no has recibido opiniones de profesores.</p>';
  } else {
    foreach ($opiniones_recibidas as $opinion) {
      echo '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;">';
      echo '<div style="margin-bottom: 10px;">';
      echo '<strong>' . htmlspecialchars($opinion['profesor_nombre']) . '</strong> - ';
      if ($opinion['materia_nombre']) {
        echo htmlspecialchars($opinion['materia_nombre']) . ' - ';
      }
      for ($i = 1; $i <= 5; $i++) {
        echo $i <= $opinion['calificacion'] ? '⭐' : '☆';
      }
      echo ' (' . $opinion['calificacion'] . '/5)';
      echo '</div>';
      if ($opinion['comentario']) {
        echo '<div style="color: #666; font-style: italic;">"' . htmlspecialchars($opinion['comentario']) . '"</div>';
      }
      echo '<div style="font-size: 12px; color: #999; margin-top: 8px;">';
      echo date('d/m/Y H:i', strtotime($opinion['fecha_opinion']));
      echo '</div>';
      echo '</div>';
    }
  }
  ?>
</section>

<?php include_once 'footer.php'; ?>
