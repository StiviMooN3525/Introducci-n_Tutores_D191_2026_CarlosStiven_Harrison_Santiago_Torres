<?php
session_start(); // ← AGREGADO - Iniciar sesión
include_once 'header.php';
require_once 'conexion.php';
require_once 'csrf_helper.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'profesor') {
		header('Location: login.php'); exit;
}

// Obtener id_profesor del usuario actual
$profStmt = $pdo->prepare('SELECT id_profesor FROM profesores WHERE id_usuario = :u LIMIT 1');
$profStmt->execute(['u' => $_SESSION['user_id']]);
$my_prof_id = $profStmt->fetchColumn();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Solicitud inválida. Por favor intenta nuevamente.';
    } else {
        if (($_POST['action'] ?? '') === 'add_disponibilidad') {
            $fecha = $_POST['fecha'] ?? null;
            $hora_inicio = $_POST['hora_inicio'] ?? null;
            $hora_fin = $_POST['hora_fin'] ?? null;
            $cupo_maximo = filter_var($_POST['cupo_maximo'] ?? 8, FILTER_VALIDATE_INT);
            $materias_seleccionadas = $_POST['materias'] ?? [];
            
            // Validar que se seleccione al menos una materia
            if (empty($materias_seleccionadas)) {
                $error = 'Debes seleccionar al menos una materia.';
            } elseif ($fecha && $hora_inicio && $hora_fin && $my_prof_id) {
                $stmt = $pdo->prepare('INSERT INTO disponibilidad (id_profesor, fecha, hora_inicio, hora_fin, cupo_maximo, cupo_actual, estado) VALUES (:prof, :fec, :hi, :hf, :cupo_max, 0, "disponible")');
                $result = $stmt->execute(['prof' => $my_prof_id, 'fec' => $fecha, 'hi' => $hora_inicio, 'hf' => $hora_fin, 'cupo_max' => $cupo_maximo]);
                
                if ($result) {
                    $disponibilidad_id = $pdo->lastInsertId();
                    
                    // Guardar las materias seleccionadas para esta disponibilidad (si la tabla existe)
                    try {
                        $stmt_materia = $pdo->prepare('INSERT INTO disponibilidad_materias (id_disponibilidad, id_materia) VALUES (:disp, :mat)');
                        
                        foreach ($materias_seleccionadas as $materia_id) {
                            $stmt_materia->execute(['disp' => $disponibilidad_id, 'mat' => $materia_id]);
                        }
                        error_log("Disponibilidad creada exitosamente. ID: $disponibilidad_id, Materias: " . implode(', ', $materias_seleccionadas));
                    } catch (Exception $e) {
                        // Si la tabla no existe, guardar la primera materia en el campo id_materia de disponibilidad
                        error_log("Tabla disponibilidad_materias no existe. Guardando materia principal en disponibilidad.");
                        $primera_materia = $materias_seleccionadas[0];
                        $stmt_update = $pdo->prepare('UPDATE disponibilidad SET id_materia = :mat WHERE id_disponibilidad = :disp');
                        $stmt_update->execute(['mat' => $primera_materia, 'disp' => $disponibilidad_id]);
                        error_log("Disponibilidad creada con materia principal. ID: $disponibilidad_id, Materia: $primera_materia");
                    }
                    
                    header('Location: dashboard_profesor.php?success=1'); exit;
                } else {
                    error_log("Error al crear disponibilidad. Profesor: $my_prof_id");
                    $error = 'Error al agregar disponibilidad. Intente nuevamente.';
                }
            } else {
                $error = 'Todos los campos son obligatorios.';
            }
        }

        if (($_POST['action'] ?? '') === 'cancel_disponibilidad') {
            $disp_id = filter_var($_POST['disp_id'] ?? 0, FILTER_VALIDATE_INT);
            if ($disp_id && $my_prof_id) {
                // Primero cancelar todas las inscripciones de esta disponibilidad
                $stmt_cancel_inscripciones = $pdo->prepare('UPDATE tutoria_inscripciones SET estado = "cancelado" WHERE id_disponibilidad = :disp_id AND estado = "inscrito"');
                $stmt_cancel_inscripciones->execute(['disp_id' => $disp_id]);
                
                // Actualizar cupo actual a 0 en disponibilidad
                $stmt_update_cupos = $pdo->prepare('UPDATE disponibilidad SET cupo_actual = 0 WHERE id_disponibilidad = :id AND id_profesor = :prof');
                $stmt_update_cupos->execute(['id' => $disp_id, 'prof' => $my_prof_id]);
                
                // Eliminar completamente la disponibilidad
                $stmt = $pdo->prepare("DELETE FROM disponibilidad WHERE id_disponibilidad = :id AND id_profesor = :prof");
                $stmt->execute(['id' => $disp_id, 'prof' => $my_prof_id]);
                
                header('Location: dashboard_profesor.php?success=4'); exit;
            }
        }

        if (($_POST['action'] ?? '') === 'update_inscripcion') {
            $inscripcion_id = filter_var($_POST['inscripcion_id'] ?? 0, FILTER_VALIDATE_INT);
            $nuevo_estado = $_POST['nuevo_estado'] ?? '';
            $estados_permitidos = ['inscrito', 'cancelado', 'asistio', 'no_asistio'];
            
            if ($inscripcion_id && in_array($nuevo_estado, $estados_permitidos)) {
                // Verificar que la inscripción pertenezca a una tutoría de este profesor
                $stmt_check = $pdo->prepare('
                    SELECT ti.id_inscripcion 
                    FROM tutoria_inscripciones ti 
                    JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad 
                    WHERE ti.id_inscripcion = :id AND d.id_profesor = :prof
                ');
                $stmt_check->execute(['id' => $inscripcion_id, 'prof' => $my_prof_id]);
                
                if ($stmt_check->fetchColumn()) {
                    $stmt = $pdo->prepare('UPDATE tutoria_inscripciones SET estado = :estado WHERE id_inscripcion = :id');
                    $stmt->execute(['estado' => $nuevo_estado, 'id' => $inscripcion_id]);
                    header('Location: dashboard_profesor.php?success=3'); exit;
                }
            }
        }

        if (($_POST['action'] ?? '') === 'add_opinion') {
            $id_tutoria = filter_var($_POST['id_tutoria'] ?? 0, FILTER_VALIDATE_INT);
            $id_estudiante = filter_var($_POST['id_estudiante'] ?? 0, FILTER_VALIDATE_INT);
            $calificacion = filter_var($_POST['calificacion'] ?? 0, FILTER_VALIDATE_INT);
            $comentario = trim($_POST['comentario'] ?? '');
            
            if ($id_tutoria && $id_estudiante && $calificacion >= 1 && $calificacion <= 5) {
                try {
                    // Verificar que la tutoría pertenezca a este profesor
                    $stmt_check = $pdo->prepare('
                        SELECT ti.id_inscripcion 
                        FROM tutoria_inscripciones ti 
                        JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad 
                        WHERE ti.id_inscripcion = :id AND d.id_profesor = :prof
                    ');
                    $stmt_check->execute(['id' => $id_tutoria, 'prof' => $my_prof_id]);
                    
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
                            'receptor' => $id_estudiante,
                            'tipo_emisor' => 'profesor',
                            'tipo_receptor' => 'estudiante',
                            'calif' => $calificacion,
                            'coment' => $comentario ?: null
                        ]);
                        
                        header('Location: dashboard_profesor.php?success=4'); exit;
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $error = 'Ya has opinado sobre este estudiante para esta tutoría.';
                    } else {
                        $error = 'Error al guardar la opinión. Intenta nuevamente.';
                    }
                }
            } else {
                $error = 'Datos inválidos para la opinión.';
            }
        }
    }
}

// Mensajes de éxito
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = 'Disponibilidad agregada exitosamente.';
} elseif (isset($_GET['success']) && $_GET['success'] == 3) {
    $success = 'Estado de inscripción actualizado exitosamente.';
} elseif (isset($_GET['success']) && $_GET['success'] == 4) {
    $success = 'Opinión enviada exitosamente.';
} elseif (isset($_GET['success']) && $_GET['success'] == 5) {
    $success = 'Disponibilidad eliminada exitosamente.';
}

?>

<section class="card">
  <h2>Panel Profesor</h2>
  <p class="muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>. Gestiona tu disponibilidad y tutorías grupales.</p>
  <?php if (!empty($error)) { echo '<div class="alert error">'.htmlspecialchars($error).'</div>'; } ?>
  <?php if (!empty($success)) { echo '<div class="alert success">'.htmlspecialchars($success).'</div>'; } ?>
</section>

<div class="dashboard-grid prof-panel">
  <div class="card">
    <h3>Mi Disponibilidad</h3>
    <p class="muted">Registra horarios para tutorías grupales (máximo 8 estudiantes).</p>
    <form method="post" style="margin-bottom:16px">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="add_disponibilidad">
      <div class="field"><label>Fecha</label><input type="date" name="fecha" required></div>
      <div class="field"><label>Hora inicio</label><input type="time" name="hora_inicio" required></div>
      <div class="field"><label>Hora fin</label><input type="time" name="hora_fin" required></div>
      
      <!-- Selector de materias dinámicas -->
      <div class="field">
        <label>Materias que puedes dictar</label>
        <div style="margin-bottom: 10px;">
          <small style="color: #666;">Selecciona las materias que ofrecerás en esta disponibilidad:</small>
        </div>
        <div id="materias-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 8px; background: #f9f9f9;">
          <?php
          // Obtener todas las materias disponibles
          $stmt_materias = $pdo->query('SELECT id_materia, nombre FROM materias ORDER BY nombre');
          $materias = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);
          
          foreach ($materias as $materia) {
            echo '<div style="margin-bottom: 5px;">';
            echo '<label style="display: flex; align-items: center; cursor: pointer;">';
            echo '<input type="checkbox" name="materias[]" value="' . $materia['id_materia'] . '" style="margin-right: 8px;">';
            echo '<span>' . htmlspecialchars($materia['nombre']) . '</span>';
            echo '</label>';
            echo '</div>';
          }
          ?>
        </div>
        <small style="color: #666; font-style: italic;">Puedes seleccionar múltiples materias. Los estudiantes podrán inscribirse a cualquiera de ellas.</small>
      </div>
      
      <div class="field"><label>Cupo máximo (estudiantes)</label><input type="number" name="cupo_maximo" min="1" max="8" value="8" required></div>
      <button class="btn primary" type="submit">Agregar Disponibilidad</button>
    </form>

    <h4>Disponibilidades Próximas</h4>
    <table class="table">
      <thead><tr><th>Fecha</th><th>Inicio</th><th>Fin</th><th>Cupos</th><th>Acción</th></tr></thead>
      <tbody>
      <?php
      if ($my_prof_id) {
        // Consulta más amplia para ver todas las disponibilidades del profesor
        $stmt = $pdo->prepare("SELECT *, CASE 
            WHEN fecha >= CURDATE() AND estado = 'disponible' THEN 'visible'
            WHEN fecha >= CURDATE() AND estado = 'no_disponible' THEN 'cancelada'
            ELSE 'oculta'
        END as visibility 
        FROM disponibilidad 
        WHERE id_profesor = :prof 
        ORDER BY fecha DESC, hora_inicio DESC LIMIT 10");
        $stmt->execute(['prof' => $my_prof_id]);
        
        $count_total = $stmt->rowCount();
        $count_visible = 0;
        
        // Contar solo las visibles
        foreach ($stmt->fetchAll() as $row) {
            if ($row['visibility'] === 'visible') $count_visible++;
        }
        
        // Volver a ejecutar para el while
        $stmt->execute(['prof' => $my_prof_id]);
        
        if ($count_total == 0) {
            echo '<tr><td colspan="5" style="text-align: center; color: #666;">No tienes disponibilidades registradas</td></tr>';
        } elseif ($count_visible == 0) {
            echo '<tr><td colspan="5" style="text-align: center; color: #666;">No tienes disponibilidades activas (todas están canceladas o en fechas pasadas)</td></tr>';
        }
      } else {
        echo '<tr><td colspan="5" style="text-align: center; color: red;">Error: No se encontró ID de profesor</td></tr>';
        $stmt = $pdo->query('SELECT NULL AS fecha, NULL AS hora_inicio, NULL AS hora_fin, NULL AS id_disponibilidad LIMIT 0');
      }
      while ($d = $stmt->fetch()) {
          // Solo mostrar si está visible (disponible y fecha futura)
          if ($d['visibility'] !== 'visible') {
              continue; // Saltar las canceladas o pasadas
          }
          
          echo '<tr>';
          echo '<td>'.htmlspecialchars($d['fecha']).'</td>';
          echo '<td>'.htmlspecialchars(substr($d['hora_inicio'],0,5)).'</td>';
          echo '<td>'.htmlspecialchars(substr($d['hora_fin'],0,5)).'</td>';
          
          // Mostrar cupos
          $cupos_disponibles = $d['cupo_maximo'] - $d['cupo_actual'];
          $porcentaje_lleno = ($d['cupo_actual'] / $d['cupo_maximo']) * 100;
          
          echo '<td>';
          if ($cupos_disponibles <= 0) {
            echo '<span class="chip cancelada">Lleno (0/'.$d['cupo_maximo'].')</span>';
          } elseif ($porcentaje_lleno >= 75) {
            echo '<span class="chip" style="background-color: #ff9800; color: white;">'.$cupos_disponibles.'/'.$d['cupo_maximo'].'</span>';
          } else {
            echo '<span class="chip success">'.$cupos_disponibles.'/'.$d['cupo_maximo'].'</span>';
          }
          echo '</td>';
          
          echo '<td>';
          // Mostrar botón para cancelar disponibilidad
          echo '<form method="post" style="display:inline-block">';
          echo csrf_field();
          echo '<input type="hidden" name="action" value="cancel_disponibilidad">';
          echo '<input type="hidden" name="disp_id" value="'.intval($d['id_disponibilidad']).'">';
          echo '<button class="btn danger" type="submit" onclick="return confirmAction(this.form, \"Cancelar esta disponibilidad?\")">Cancelar</button>';
          echo '</form>';
          echo '</td>';
          echo '</tr>';
      }
      ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3>Estudiantes Inscritos en Tutorías</h3>
    <p class="muted">Gestiona las inscripciones de los estudiantes en tus tutorías grupales.</p>
    <table class="table">
      <thead><tr><th>Fecha</th><th>Hora</th><th>Materia</th><th>Estudiantes Inscritos</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php
      if ($my_prof_id) {
        // Obtener tutorías con estudiantes inscritos
        $stmt_tutorias = $pdo->prepare('
          SELECT DISTINCT 
            d.id_disponibilidad,
            d.fecha,
            d.hora_inicio,
            d.hora_fin,
            d.cupo_maximo,
            d.cupo_actual,
            m.nombre as materia
          FROM disponibilidad d
          LEFT JOIN tutoria_inscripciones ti ON d.id_disponibilidad = ti.id_disponibilidad AND ti.estado = "inscrito"
          LEFT JOIN materias m ON ti.id_materia = m.id_materia
          WHERE d.id_profesor = :prof 
            AND d.fecha >= CURDATE() 
            AND d.estado = "disponible"
          ORDER BY d.fecha, d.hora_inicio
        ');
        $stmt_tutorias->execute(['prof' => $my_prof_id]);
        
        while ($tutoria = $stmt_tutorias->fetch()) {
          echo '<tr>';
          echo '<td>'.htmlspecialchars($tutoria['fecha']).'</td>';
          echo '<td>'.htmlspecialchars(substr($tutoria['hora_inicio'],0,5)).' - '.htmlspecialchars(substr($tutoria['hora_fin'],0,5)).'</td>';
          echo '<td>'.htmlspecialchars($tutoria['materia'] ?: 'Sin especificar').'</td>';
          
          // Obtener lista de estudiantes inscritos en esta tutoría
          $stmt_estudiantes = $pdo->prepare('
            SELECT ti.id_inscripcion, ti.estado, ue.nombre as estudiante, ue.correo as email
            FROM tutoria_inscripciones ti
            JOIN estudiantes e ON ti.id_estudiante = e.id_estudiante
            JOIN usuarios ue ON e.id_usuario = ue.id_usuario
            WHERE ti.id_disponibilidad = :disp_id
            ORDER BY ti.fecha_inscripcion
          ');
          $stmt_estudiantes->execute(['disp_id' => $tutoria['id_disponibilidad']]);
          $estudiantes = $stmt_estudiantes->fetchAll();
          
          echo '<td>';
          if (empty($estudiantes)) {
            echo '<span class="muted">Sin estudiantes inscritos</span>';
          } else {
            echo '<div style="max-height: 120px; overflow-y: auto;">';
            foreach ($estudiantes as $est) {
              $color_estado = $est['estado'] === 'inscrito' ? 'success' : 'cancelada';
              echo '<div style="margin-bottom: 4px; padding: 4px; background: #f5f5f5; border-radius: 4px;">';
              echo '<strong>'.htmlspecialchars($est['estudiante']).'</strong> ';
              echo '<span class="chip '.$color_estado.'">'.htmlspecialchars($est['estado']).'</span>';
              echo '</div>';
            }
            echo '</div>';
            echo '<small style="color: #666;">'.$tutoria['cupo_actual'].'/'.$tutoria['cupo_maximo'].' cupos ocupados</small>';
          }
          echo '</td>';
          
          echo '<td>';
          if (!empty($estudiantes)) {
            foreach ($estudiantes as $est) {
              if ($est['estado'] === 'inscrito') {
                echo '<form method="post" style="display:inline-block; margin: 2px;">';
                echo csrf_field();
                echo '<input type="hidden" name="action" value="update_inscripcion">';
                echo '<input type="hidden" name="inscripcion_id" value="'.intval($est['id_inscripcion']).'">';
                echo '<select name="nuevo_estado" onchange="this.form.submit()" style="font-size: 12px; padding: 2px;">';
                echo '<option value="inscrito" selected>Inscrito</option>';
                echo '<option value="asistio">Asistió</option>';
                echo '<option value="no_asistio">No asistió</option>';
                echo '<option value="cancelado">Cancelar</option>';
                echo '</select>';
                echo '</form>';
              }
            }
          } else {
            echo '<span class="muted">-</span>';
          }
          echo '</td>';
          
          echo '</tr>';
        }
      } else {
        echo '<tr><td colspan="5" style="text-align: center;">No tienes disponibilidad registrada</td></tr>';
      }
      ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Sección de Opiniones sobre Estudiantes -->
<section class="card">
  <h3>Opinar sobre Estudiantes</h3>
  <p class="muted">Califica y deja comentarios sobre los estudiantes que han asistido a tus tutorías.</p>
  
  <?php
  // Obtener tutorías completadas donde el profesor puede opinar
  try {
    $stmt_opiniones = $pdo->prepare('
      SELECT 
        ti.id_inscripcion,
        ti.estado,
        u_est.id_usuario as estudiante_id,
        u_est.nombre as estudiante_nombre,
        d.fecha as tutoria_fecha,
        m.nombre as materia_nombre,
        o.id_opinion,
        o.calificacion as calificacion_existente,
        o.comentario as comentario_existente
      FROM tutoria_inscripciones ti
      JOIN estudiantes e ON ti.id_estudiante = e.id_estudiante
      JOIN usuarios u_est ON e.id_usuario = u_est.id_usuario
      JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad
      LEFT JOIN materias m ON d.id_materia = m.id_materia
      LEFT JOIN opiniones o ON o.id_tutoria = d.id_disponibilidad 
                          AND o.id_emisor = :emisor_id 
                          AND o.tipo_emisor = "profesor"
      WHERE ti.id_profesor = :profesor_id 
        AND ti.estado IN ("asistio", "no_asistio")
        AND d.fecha < CURDATE()
      ORDER BY d.fecha DESC
    ');
    $stmt_opiniones->execute(['emisor_id' => $my_prof_id, 'profesor_id' => $my_prof_id]);
    $tutorias_para_opinar = $stmt_opiniones->fetchAll();
    
    // Si no hay tutorías para opinar, mostrar mensaje apropiado
    if (empty($tutorias_para_opinar)) {
      echo '<div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #2196f3;">';
      echo '<h4 style="color: #1565c0; margin: 0 0 10px 0;">� No hay tutorías para opinar</h4>';
      echo '<p style="color: #1565c0; margin: 0;">Aún no tienes tutorías completadas donde puedas dejar tu opinión.</p>';
      echo '<p style="margin: 10px 0; color: #666;">Las tutorías completadas aparecerán aquí para que puedas calificar a tus estudiantes.</p>';
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
      echo '<h4 style="margin: 0 0 10px 0; color: #333;">' . htmlspecialchars($tutoria['estudiante_nombre']) . '</h4>';
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
        echo '<input type="hidden" name="id_estudiante" value="' . $tutoria['estudiante_id'] . '">';
        
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
        echo '<textarea name="comentario" rows="3" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd; resize: vertical;" placeholder="Ej: Excelente participación, muy atento..."></textarea>';
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
  <h3>Opiniones Recibidas de Estudiantes</h3>
  <p class="muted">Ve lo que los estudiantes opinan sobre tu desempeño como profesor.</p>
  
  <?php
  // Obtener opiniones recibidas de estudiantes
  try {
    $stmt_recibidas = $pdo->prepare('
      SELECT 
        o.calificacion,
        o.comentario,
        o.fecha_opinion,
        u_emisor.nombre as estudiante_nombre
      FROM opiniones o
      JOIN usuarios u_emisor ON o.id_emisor = u_emisor.id_usuario
      WHERE o.id_receptor = :prof_id 
        AND o.tipo_receptor = "profesor"
        AND o.estado = "visible"
      ORDER BY o.fecha_opinion DESC
      LIMIT 10
    ');
    $stmt_recibidas->execute(['prof_id' => $_SESSION['user_id']]);
    $opiniones_recibidas = $stmt_recibidas->fetchAll();
    
    // Calcular promedio
    try {
      $stmt_promedio = $pdo->prepare('
        SELECT AVG(calificacion) as promedio, COUNT(*) as total
        FROM opiniones 
        WHERE id_receptor = :prof_id 
          AND tipo_receptor = "profesor"
          AND estado = "visible"
      ');
      $stmt_promedio->execute(['prof_id' => $_SESSION['user_id']]);
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
    echo '<p style="text-align: center; color: #666;">Aún no has recibido opiniones de estudiantes.</p>';
  } else {
    foreach ($opiniones_recibidas as $opinion) {
      echo '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;">';
      echo '<div style="margin-bottom: 10px;">';
      echo '<strong>' . htmlspecialchars($opinion['estudiante_nombre']) . '</strong> - ';
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

