<?php
require_once 'conexion.php';

echo "=== CREANDO DATOS DE PRUEBA PARA TUTORÍAS GRUPALES ===\n\n";

try {
    // 1. Verificar que existan usuarios activos
    $stmt_estudiantes = $pdo->query('
        SELECT e.id_estudiante, u.nombre, u.correo 
        FROM estudiantes e 
        JOIN usuarios u ON e.id_usuario = u.id_usuario 
        WHERE u.estado = "activo" 
        LIMIT 5
    ');
    $estudiantes = $stmt_estudiantes->fetchAll();
    
    $stmt_profesores = $pdo->query('
        SELECT p.id_profesor, u.nombre, u.correo 
        FROM profesores p 
        JOIN usuarios u ON p.id_usuario = u.id_usuario 
        WHERE u.estado = "activo" 
        LIMIT 3
    ');
    $profesores = $stmt_profesores->fetchAll();
    
    $stmt_materias = $pdo->query('SELECT id_materia, nombre FROM materias LIMIT 5');
    $materias = $stmt_materias->fetchAll();
    
    echo "1. VERIFICANDO USUARIOS ACTIVOS:\n";
    echo "   Estudiantes disponibles: " . count($estudiantes) . "\n";
    echo "   Profesores disponibles: " . count($profesores) . "\n";
    echo "   Materias disponibles: " . count($materias) . "\n\n";
    
    if (count($profesores) == 0) {
        echo "❌ ERROR: No hay profesores activos. Creando un profesor de prueba...\n";
        
        // Crear usuario profesor de prueba
        $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, contrasena, rol, estado) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(['Profesor Demo', 'profesor@demo.com', '123456', 'profesor', 'activo']);
        $prof_user_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare('INSERT INTO profesores (id_usuario, especialidad, departamento) VALUES (?, ?, ?)');
        $stmt->execute([$prof_user_id, 'Matemáticas', 'Ciencias Exactas']);
        
        // Obtener el profesor creado
        $stmt_profesores = $pdo->query('
            SELECT p.id_profesor, u.nombre, u.correo 
            FROM profesores p 
            JOIN usuarios u ON p.id_usuario = u.id_usuario 
            WHERE u.estado = "activo" 
            LIMIT 1
        ');
        $profesores = $stmt_profesores->fetchAll();
    }
    
    if (count($estudiantes) == 0) {
        echo "❌ ERROR: No hay estudiantes activos. Creando estudiantes de prueba...\n";
        
        // Crear 3 estudiantes de prueba
        $nombres_estudiantes = ['Juan Pérez', 'María García', 'Carlos López'];
        $emails_estudiantes = ['juan@demo.com', 'maria@demo.com', 'carlos@demo.com'];
        
        foreach ($nombres_estudiantes as $i => $nombre) {
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, contrasena, rol, estado) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nombre, $emails_estudiantes[$i], '123456', 'estudiante', 'activo']);
            $est_user_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare('INSERT INTO estudiantes (id_usuario, codigo_estudiantil, carrera, semestre) VALUES (?, ?, ?, ?)');
            $stmt->execute([$est_user_id, 'EST' . str_pad($i+1, 4, '0', STR_PAD_LEFT), 'Ingeniería', $i+1]);
        }
        
        // Obtener los estudiantes creados
        $stmt_estudiantes = $pdo->query('
            SELECT e.id_estudiante, u.nombre, u.correo 
            FROM estudiantes e 
            JOIN usuarios u ON e.id_usuario = u.id_usuario 
            WHERE u.estado = "activo" 
            LIMIT 3
        ');
        $estudiantes = $stmt_estudiantes->fetchAll();
    }
    
    if (count($materias) == 0) {
        echo "❌ ERROR: No hay materias. Creando materias de prueba...\n";
        
        $materias_nombres = ['Matemáticas', 'Álgebra Lineal', 'Cálculo', 'Física', 'Programación'];
        foreach ($materias_nombres as $nombre) {
            $stmt = $pdo->prepare('INSERT INTO materias (nombre) VALUES (?)');
            $stmt->execute([$nombre]);
        }
        
        $stmt_materias = $pdo->query('SELECT id_materia, nombre FROM materias');
        $materias = $stmt_materias->fetchAll();
    }
    
    // 2. Crear disponibilidades (horarios de tutorías)
    echo "2. CREANDO DISPONIBILIDADES DE TUTORÍAS:\n";
    
    foreach ($profesores as $profesor) {
        // Crear 3 disponibilidades por profesor para los próximos días
        for ($i = 0; $i < 3; $i++) {
            $fecha = date('Y-m-d', strtotime('+' . $i . ' days'));
            $hora_inicio = ($i + 9) . ':00:00'; // 9:00, 10:00, 11:00
            $hora_fin = ($i + 10) . ':00:00';   // 10:00, 11:00, 12:00
            $cupo_maximo = rand(3, 8); // Cupo aleatorio entre 3 y 8
            
            $stmt = $pdo->prepare('
                INSERT INTO disponibilidad (id_profesor, fecha, hora_inicio, hora_fin, cupo_maximo, cupo_actual, estado) 
                VALUES (?, ?, ?, ?, ?, 0, "disponible")
            ');
            $stmt->execute([$profesor['id_profesor'], $fecha, $hora_inicio, $hora_fin, $cupo_maximo]);
            
            $disp_id = $pdo->lastInsertId();
            echo "   ✓ Disponibilidad creada: {$profesor['nombre']} - $fecha " . substr($hora_inicio, 0, 5) . " (cupo: $cupo_maximo)\n";
            
            // 3. Crear inscripciones de estudiantes
            if (count($estudiantes) > 0 && $i < 2) { // Solo inscribir en las primeras 2 disponibilidades
                $materia = $materias[array_rand($materias)];
                $num_inscripciones = min(rand(1, min(3, count($estudiantes))), $cupo_maximo);
                
                for ($j = 0; $j < $num_inscripciones; $j++) {
                    if (isset($estudiantes[$j])) {
                        $estudiante = $estudiantes[$j];
                        
                        $stmt = $pdo->prepare('
                            INSERT INTO tutoria_inscripciones 
                            (id_disponibilidad, id_estudiante, id_profesor, id_materia, estado) 
                            VALUES (?, ?, ?, ?, "inscrito")
                        ');
                        $stmt->execute([$disp_id, $estudiante['id_estudiante'], $profesor['id_profesor'], $materia['id_materia']]);
                        
                        echo "     ✓ Inscripción: {$estudiante['nombre']} -> {$materia['nombre']}\n";
                    }
                }
                
                // Actualizar cupo_actual
                $stmt = $pdo->prepare('UPDATE disponibilidad SET cupo_actual = ? WHERE id_disponibilidad = ?');
                $stmt->execute([$num_inscripciones, $disp_id]);
            }
        }
    }
    
    echo "\n3. VERIFICANDO DATOS CREADOS:\n";
    
    // Verificar disponibilidades
    $stmt = $pdo->query('SELECT COUNT(*) FROM disponibilidad WHERE estado = "disponible"');
    $disp_count = $stmt->fetchColumn();
    echo "   Disponibilidades creadas: $disp_count\n";
    
    // Verificar inscripciones
    $stmt = $pdo->query('SELECT COUNT(*) FROM tutoria_inscripciones WHERE estado = "inscrito"');
    $insc_count = $stmt->fetchColumn();
    echo "   Inscripciones creadas: $insc_count\n";
    
    echo "\n✅ DATOS DE PRUEBA CREADOS EXITOSAMENTE\n";
    echo "📋 Ahora puedes:\n";
    echo "   1. Ir al dashboard de administrador y ver las estadísticas\n";
    echo "   2. Ir al dashboard de estudiantes y ver las tutorías disponibles\n";
    echo "   3. Ir al dashboard de profesores y gestionar las inscripciones\n";
    echo "   4. Probar el sistema completo de tutorías grupales\n\n";
    
    echo "🔗 Enlaces útiles:\n";
    echo "   Dashboard Admin: http://localhost/Proyecto_Introduccion/dashboard_admin_v2.php\n";
    echo "   Dashboard Estudiante: http://localhost/Proyecto_Introduccion/dashboard_estudiante.php\n";
    echo "   Dashboard Profesor: http://localhost/Proyecto_Introduccion/dashboard_profesor.php\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Revisa la configuración de la base de datos y los permisos.\n";
}

echo "\n=== FIN DE LA CREACIÓN DE DATOS ===\n";
?>
