<?php
require_once 'conexion.php';

echo "<h2>🎓 Creación Completa de Datos de Prueba - Sistema de Tutorías</h2>";

try {
    // 1. Verificar si ya hay usuarios
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM usuarios');
    $result = $stmt->fetch();
    
    if ($result['total'] > 0) {
        echo "<p>✅ Ya existen " . $result['total'] . " usuarios en el sistema.</p>";
        echo "<p><a href='login.php'>Ir al Login</a> | <a href='crear_datos_completos.php?forzar=1'>Forzar recreación</a></p>";
        
        if (!isset($_GET['forzar'])) {
            exit;
        }
    }
    
    echo "<p>📝 Creando todos los datos de prueba desde cero...</p>";
    
    // Limpiar datos existentes si se fuerza
    if (isset($_GET['forzar'])) {
        echo "<p>🧹 Limpiando datos existentes...</p>";
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('DELETE FROM tutoria_inscripciones_materias');
        $pdo->exec('DELETE FROM disponibilidad_materias');
        $pdo->exec('DELETE FROM tutoria_inscripciones');
        $pdo->exec('DELETE FROM opiniones');
        $pdo->exec('DELETE FROM disponibilidad');
        $pdo->exec('DELETE FROM estudiantes');
        $pdo->exec('DELETE FROM profesores');
        $pdo->exec('DELETE FROM usuarios');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        echo "<p>✅ Datos limpiados</p>";
    }
    
    // 2. Crear Administrador
    echo "<h3>👨‍💼 Creando Administrador</h3>";
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, ?)');
    $stmt->execute(['Admin Principal', 'admin@sistema.com', 'admin123', 'administrador']);
    $admin_id = $pdo->lastInsertId();
    echo "<p>✅ Administrador creado: admin@sistema.com / admin123</p>";
    
    // 3. Crear Profesores
    echo "<h3>👨‍🏫 Creando Profesores</h3>";
    $profesores_data = [
        ['Dr. Carlos Méndez', 'carlos.mendez@sistema.com', 'profesor123', 'Matemáticas', 'Ciencias Exactas'],
        ['Dra. Ana Rodríguez', 'ana.rodriguez@sistema.com', 'profesor123', 'Física', 'Ciencias Naturales'],
        ['Dr. Luis Torres', 'luis.torres@sistema.com', 'profesor123', 'Programación', 'Ingeniería'],
        ['Dra. María González', 'maria.gonzalez@sistema.com', 'profesor123', 'Inglés', 'Lenguas']
    ];
    
    $profesores_ids = [];
    foreach ($profesores_data as $i => $prof) {
        // Crear usuario
        $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, ?)');
        $stmt->execute([$prof[0], $prof[1], $prof[2], 'profesor']);
        $user_id = $pdo->lastInsertId();
        
        // Crear perfil de profesor
        $stmt = $pdo->prepare('INSERT INTO profesores (id_usuario, especialidad, departamento) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, $prof[3], $prof[4]]);
        
        $prof_id = $pdo->lastInsertId();
        $profesores_ids[] = $prof_id;
        echo "<p>✅ Profesor creado: " . $prof[0] . " (" . $prof[1] . ")</p>";
    }
    
    // 4. Crear Estudiantes
    echo "<h3>👨‍🎓 Creando Estudiantes</h3>";
    $estudiantes_data = [
        ['Juan Pérez', 'juan.perez@sistema.com', 'estudiante123', '2021001', 'Ingeniería de Sistemas', '6'],
        ['María López', 'maria.lopez@sistema.com', 'estudiante123', '2021002', 'Ingeniería Electrónica', '4'],
        ['Carlos Sánchez', 'carlos.sanchez@sistema.com', 'estudiante123', '2021003', 'Ingeniería Civil', '8'],
        ['Ana Martínez', 'ana.martinez@sistema.com', 'estudiante123', '2021004', 'Ingeniería Química', '2'],
        ['Luis Ramírez', 'luis.ramirez@sistema.com', 'estudiante123', '2021005', 'Arquitectura', '5'],
        ['Sofía Herrera', 'sofia.herrera@sistema.com', 'estudiante123', '2021006', 'Medicina', '3']
    ];
    
    $estudiantes_ids = [];
    foreach ($estudiantes_data as $est) {
        // Crear usuario
        $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, ?)');
        $stmt->execute([$est[0], $est[1], $est[2], 'estudiante']);
        $user_id = $pdo->lastInsertId();
        
        // Crear perfil de estudiante
        $stmt = $pdo->prepare('INSERT INTO estudiantes (id_usuario, codigo_estudiantil, carrera, semestre) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user_id, $est[3], $est[4], $est[5]]);
        
        $est_id = $pdo->lastInsertId();
        $estudiantes_ids[] = $est_id;
        echo "<p>✅ Estudiante creado: " . $est[0] . " (" . $est[1] . ")</p>";
    }
    
    // 5. Crear Disponibilidades
    echo "<h3>📅 Creando Disponibilidades</h3>";
    $disponibilidades_data = [
        // Profesor 1 (Matemáticas)
        [1, 1, '2026-03-15', '09:00:00', '11:00:00'],
        [1, 1, '2026-03-15', '14:00:00', '16:00:00'],
        [1, 1, '2026-03-16', '10:00:00', '12:00:00'],
        
        // Profesor 2 (Física)
        [2, 2, '2026-03-15', '08:00:00', '10:00:00'],
        [2, 2, '2026-03-16', '13:00:00', '15:00:00'],
        [2, 2, '2026-03-17', '09:00:00', '11:00:00'],
        
        // Profesor 3 (Programación)
        [3, 4, '2026-03-15', '15:00:00', '17:00:00'],
        [3, 4, '2026-03-16', '16:00:00', '18:00:00'],
        
        // Profesor 4 (Inglés)
        [4, 5, '2026-03-15', '11:00:00', '13:00:00'],
        [4, 5, '2026-03-16', '14:00:00', '16:00:00'],
        [4, 5, '2026-03-17', '10:00:00', '12:00:00']
    ];
    
    $disponibilidades_ids = [];
    foreach ($disponibilidades_data as $disp) {
        $stmt = $pdo->prepare('INSERT INTO disponibilidad (id_profesor, id_materia, fecha, hora_inicio, hora_fin, cupo_maximo, cupo_actual) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$profesores_ids[$disp[0]-1], $disp[1], $disp[2], $disp[3], $disp[4], 8, 0]);
        $disp_id = $pdo->lastInsertId();
        $disponibilidades_ids[] = $disp_id;
        echo "<p>✅ Disponibilidad creada: ID " . $disp_id . "</p>";
    }
    
    // 6. Crear Materias Adicionales para Disponibilidad_Materias
    echo "<h3>📚 Asignando Materias Adicionales</h3>";
    $materias_adicionales = [
        // Para disponibilidad 1 (Matemáticas)
        [1, [1, 3]], // Matemáticas + Estadística
        // Para disponibilidad 2 (Física)
        [2, [2, 6]], // Física + Electrónica
        // Para disponibilidad 3 (Programación)
        [3, [4, 7]], // Programación + Mecánica
    ];
    
    foreach ($materias_adicionales as $asignacion) {
        $disp_id = $disponibilidades_ids[$asignacion[0]-1];
        foreach ($asignacion[1] as $materia_id) {
            $stmt = $pdo->prepare('INSERT INTO disponibilidad_materias (id_disponibilidad, id_materia) VALUES (?, ?)');
            $stmt->execute([$disp_id, $materia_id]);
        }
        echo "<p>✅ Materias adicionales asignadas a disponibilidad " . $disp_id . "</p>";
    }
    
    // 7. Crear Algunas Inscripciones de Prueba
    echo "<h3>📝 Creando Inscripciones de Prueba</h3>";
    $inscripciones_data = [
        // Estudiante 1 se inscribe en 2 tutorías
        [1, 1, 1, 1, 'inscrito'], // Juan en Matemáticas
        [1, 4, 3, 4, 'inscrito'], // Juan en Programación
        
        // Estudiante 2 se inscribe en 1 tutoría
        [2, 2, 2, 2, 'inscrito'], // María en Física
        
        // Estudiante 3 canceló una inscripción
        [3, 5, 4, 5, 'cancelado'], // Luis canceló Inglés
        
        // Estudiante 4 con tutorías completadas
        [4, 3, 3, 4, 'asistio'],  // Ana asistió a Programación
        [4, 6, 2, 2, 'no_asistio'], // Ana no asistió a Física
    ];
    
    foreach ($inscripciones_data as $insc) {
        $disp_id = $disponibilidades_ids[$insc[1]-1];
        $stmt = $pdo->prepare('INSERT INTO tutoria_inscripciones (id_disponibilidad, id_estudiante, id_profesor, id_materia, estado) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$disp_id, $estudiantes_ids[$insc[0]-1], $profesores_ids[$insc[2]-1], $insc[3], $insc[4]]);
        
        // Actualizar cupo_actual si está inscrito
        if ($insc[4] === 'inscrito') {
            $stmt_update = $pdo->prepare('UPDATE disponibilidad SET cupo_actual = cupo_actual + 1 WHERE id_disponibilidad = ?');
            $stmt_update->execute([$disp_id]);
        }
        
        echo "<p>✅ Inscripción creada: Estudiante " . $insc[0] . " - Estado: " . $insc[4] . "</p>";
    }
    
    // 8. Crear Opiniones de Prueba
    echo "<h3>⭐ Creando Opiniones de Prueba</h3>";
    $opiniones_data = [
        // Profesores opinando sobre estudiantes
        [1, 4, 1, 'profesor', 'estudiante', 5, 'Excelente estudiante, muy participativo'],
        [2, 4, 2, 'profesor', 'estudiante', 4, 'Buena participación, podría mejorar en la puntualidad'],
        
        // Estudiantes opinando sobre profesores
        [1, 1, 1, 'estudiante', 'profesor', 5, 'El profesor explica muy bien, muy claro'],
        [2, 2, 2, 'estudiante', 'profesor', 4, 'Buen profesor, las clases son interesantes'],
        [4, 4, 3, 'estudiante', 'profesor', 3, 'Regular, a veces va muy rápido'],
    ];
    
    foreach ($opiniones_data as $op) {
        $stmt = $pdo->prepare('INSERT INTO opiniones (id_tutoria, id_emisor, id_receptor, tipo_emisor, tipo_receptor, calificacion, comentario) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$op[0], $op[1], $op[2], $op[3], $op[4], $op[5], $op[6]]);
        echo "<p>✅ Opinión creada: " . $op[3] . " → " . $op[4] . " (⭐ " . $op[5] . ")</p>";
    }
    
    // Resumen Final
    echo "<h2>🎉 ¡Datos de Prueba Creados Exitosamente!</h2>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin: 20px 0;'>";
    
    echo "<h3>📊 Resumen de Datos Creados:</h3>";
    echo "<ul>";
    echo "<li>👨‍💼 <strong>1 Administrador</strong>: admin@sistema.com / admin123</li>";
    echo "<li>👨‍🏫 <strong>4 Profesores</strong>: Todos con contraseña 'profesor123'</li>";
    echo "<li>👨‍🎓 <strong>6 Estudiantes</strong>: Todos con contraseña 'estudiante123'</li>";
    echo "<li>📅 <strong>10 Disponibilidades</strong> de tutorías</li>";
    echo "<li>📝 <strong>6 Inscripciones</strong> (varios estados)</li>";
    echo "<li>⭐ <strong>5 Opiniones</strong> de prueba</li>";
    echo "</ul>";
    
    echo "<h3>🔑 Credenciales de Prueba:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #007bff; color: white;'><th>Rol</th><th>Correo</th><th>Contraseña</th><th>Acceso</th></tr>";
    echo "<tr><td>👨‍💼 Admin</td><td>admin@sistema.com</td><td>admin123</td><td>Panel Admin</td></tr>";
    echo "<tr><td>👨‍🏫 Profesor</td><td>carlos.mendez@sistema.com</td><td>profesor123</td><td>Dashboard Profesor</td></tr>";
    echo "<tr><td>👨‍🎓 Estudiante</td><td>juan.perez@sistema.com</td><td>estudiante123</td><td>Dashboard Estudiante</td></tr>";
    echo "</table>";
    
    echo "<h3>🚀 Para Probar el Sistema:</h3>";
    echo "<ol>";
    echo "<li><a href='login.php' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔐 Ir al Login</a></li>";
    echo "<li>Usa las credenciales de arriba para iniciar sesión</li>";
    echo "<li>Prueba cada rol (admin, profesor, estudiante)</li>";
    echo "<li>Verifica las inscripciones, cancelaciones y opiniones</li>";
    echo "</ol>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
    echo "<h4>❌ Error: " . $e->getMessage() . "</h4>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h3 { color: #555; margin-top: 20px; }
table { width: 100%; }
th { background: #007bff; color: white; }
a:hover { opacity: 0.8; }
</style>
