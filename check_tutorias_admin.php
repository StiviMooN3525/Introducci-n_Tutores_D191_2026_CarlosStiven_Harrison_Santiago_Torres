<?php
require_once 'conexion.php';

echo "<h2>Diagnóstico de Tutorías para Panel Admin</h2>";

try {
    // Verificar si hay inscripciones
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM tutoria_inscripciones');
    $result = $stmt->fetch();
    echo "<p><strong>Total inscripciones:</strong> " . $result['total'] . "</p>";
    
    if ($result['total'] > 0) {
        // Mostrar algunas inscripciones
        $stmt = $pdo->query('
            SELECT 
                ti.id_inscripcion,
                ti.estado,
                ti.fecha_inscripcion,
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
            ORDER BY ti.fecha_inscripcion DESC
            LIMIT 5
        ');
        
        echo "<h3>Últimas 5 inscripciones:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Estudiante</th><th>Profesor</th><th>Materia</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr>";
        
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . $row['id_inscripcion'] . "</td>";
            echo "<td>" . htmlspecialchars($row['estudiante_nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($row['profesor_nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($row['materia_nombre'] ?: 'Sin materia') . "</td>";
            echo "<td>" . $row['tutoria_fecha'] . "</td>";
            echo "<td>" . substr($row['tutoria_hora'], 0, 5) . "</td>";
            echo "<td>" . $row['estado'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p><strong>No hay inscripciones en el sistema.</strong></p>";
        
        // Verificar si hay disponibilidades
        $stmt = $pdo->query('SELECT COUNT(*) as total FROM disponibilidad WHERE estado = "disponible"');
        $result = $stmt->fetch();
        echo "<p><strong>Disponibilidades activas:</strong> " . $result['total'] . "</p>";
        
        // Verificar si hay estudiantes
        $stmt = $pdo->query('SELECT COUNT(*) as total FROM estudiantes e JOIN usuarios u ON e.id_usuario = u.id_usuario WHERE u.estado = "activo"');
        $result = $stmt->fetch();
        echo "<p><strong>Estudiantes activos:</strong> " . $result['total'] . "</p>";
        
        // Verificar si hay profesores
        $stmt = $pdo->query('SELECT COUNT(*) as total FROM profesores p JOIN usuarios u ON p.id_usuario = u.id_usuario WHERE u.estado = "activo"');
        $result = $stmt->fetch();
        echo "<p><strong>Profesores activos:</strong> " . $result['total'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='dashboard_admin_v2.php'>Ir al Panel Admin v2</a></p>";
echo "<p><a href='dashboard_admin.php'>Ir al Panel Admin Original</a></p>";
?>
