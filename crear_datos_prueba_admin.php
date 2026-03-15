<?php
require_once 'conexion.php';

echo "<h2>Creación de Datos de Prueba para Panel Admin</h2>";

try {
    // Verificar si ya hay inscripciones
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM tutoria_inscripciones');
    $result = $stmt->fetch();
    
    if ($result['total'] > 0) {
        echo "<p>✅ Ya existen " . $result['total'] . " inscripciones en el sistema.</p>";
        echo "<p><a href='dashboard_admin.php'>Ir al Panel Admin</a></p>";
        exit;
    }
    
    echo "<p>📝 No hay inscripciones. Creando datos de prueba...</p>";
    
    // 1. Verificar si hay disponibilidades
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM disponibilidad WHERE estado = "disponible"');
    $disp_result = $stmt->fetch();
    
    if ($disp_result['total'] == 0) {
        echo "<p>❌ No hay disponibilidades disponibles. Creando algunas...</p>";
        
        // Crear disponibilidades de prueba
        $stmt_prof = $pdo->query('SELECT p.id_profesor FROM profesores p JOIN usuarios u ON p.id_usuario = u.id_usuario WHERE u.estado = "activo" LIMIT 2');
        $profesores = $stmt_prof->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($profesores) > 0) {
            $fechas = ['2026-03-15', '2026-03-16', '2026-03-17'];
            $horas = ['09:00:00', '11:00:00', '14:00:00'];
            
            foreach ($profesores as $profesor) {
                foreach ($fechas as $fecha) {
                    foreach ($horas as $hora) {
                        $stmt = $pdo->prepare('
                            INSERT INTO disponibilidad (id_profesor, fecha, hora_inicio, hora_fin, cupo_maximo, cupo_actual, estado) 
                            VALUES (:prof, :fecha, :hora_inicio, :hora_fin, 5, 0, "disponible")
                        ');
                        $stmt->execute([
                            'prof' => $profesor,
                            'fecha' => $fecha,
                            'hora_inicio' => $hora,
                            'hora_fin' => date('H:i:s', strtotime($hora) + 3600) // +1 hora
                        ]);
                    }
                }
            }
            echo "<p>✅ Disponibilidades creadas.</p>";
        } else {
            echo "<p>❌ No hay profesores activos para crear disponibilidades.</p>";
            exit;
        }
    }
    
    // 2. Obtener estudiantes y disponibilidades para crear inscripciones
    $stmt_est = $pdo->query('
        SELECT e.id_estudiante, u.nombre as estudiante_nombre 
        FROM estudiantes e 
        JOIN usuarios u ON e.id_usuario = u.id_usuario 
        WHERE u.estado = "activo" 
        LIMIT 5
    ');
    $estudiantes = $stmt_est->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_disp = $pdo->query('
        SELECT d.id_disponibilidad, d.id_profesor, d.fecha, d.hora_inicio 
        FROM disponibilidad d 
        WHERE d.estado = "disponible" 
        ORDER BY d.fecha, d.hora_inicio 
        LIMIT 10
    ');
    $disponibilidades = $stmt_disp->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($estudiantes) == 0) {
        echo "<p>❌ No hay estudiantes activos.</p>";
        exit;
    }
    
    if (count($disponibilidades) == 0) {
        echo "<p>❌ No hay disponibilidades disponibles.</p>";
        exit;
    }
    
    // 3. Crear inscripciones de prueba
    $estados = ['inscrito', 'inscrito', 'cancelado', 'asistio', 'no_asistio'];
    $inscripciones_creadas = 0;
    
    foreach ($estudiantes as $estudiante) {
        foreach ($disponibilidades as $disp) {
            // Asignar materia aleatoria si existe
            $stmt_materia = $pdo->prepare('SELECT id_materia FROM materias WHERE id_profesor = :prof LIMIT 1');
            $stmt_materia->execute(['prof' => $disp['id_profesor']]);
            $materia = $stmt_materia->fetchColumn();
            
            $estado = $estados[array_rand($estados)];
            
            $stmt = $pdo->prepare('
                INSERT INTO tutoria_inscripciones 
                (id_disponibilidad, id_estudiante, id_profesor, id_materia, estado, fecha_inscripcion) 
                VALUES (:disp, :est, :prof, :mat, :estado, :fecha_ins)
            ');
            $stmt->execute([
                'disp' => $disp['id_disponibilidad'],
                'est' => $estudiante['id_estudiante'],
                'prof' => $disp['id_profesor'],
                'mat' => $materia ?: 1,
                'estado' => $estado,
                'fecha_ins' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
            ]);
            
            // Actualizar cupo_actual si está inscrito
            if ($estado === 'inscrito') {
                $stmt_update = $pdo->prepare('
                    UPDATE disponibilidad 
                    SET cupo_actual = cupo_actual + 1 
                    WHERE id_disponibilidad = :disp
                ');
                $stmt_update->execute(['disp' => $disp['id_disponibilidad']]);
            }
            
            $inscripciones_creadas++;
            
            if ($inscripciones_creadas >= 15) break 2; // Limitar a 15 inscripciones
        }
    }
    
    echo "<p>✅ Se crearon $inscripciones_creadas inscripciones de prueba.</p>";
    echo "<p>📊 Estados creados: " . implode(', ', array_unique($estados)) . "</p>";
    
    // 4. Verificar resultado
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM tutoria_inscripciones');
    $result = $stmt->fetch();
    echo "<p><strong>Total inscripciones ahora:</strong> " . $result['total'] . "</p>";
    
    echo "<hr>";
    echo "<p><strong>🎯 Listo!</strong> Ahora puedes:</p>";
    echo "<ul>";
    echo "<li><a href='dashboard_admin.php'>Ver el Panel Admin con datos</a></li>";
    echo "<li><a href='check_tutorias_admin.php'>Ver diagnóstico completo</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
