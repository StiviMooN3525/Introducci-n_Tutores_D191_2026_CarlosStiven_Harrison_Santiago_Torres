<?php
require_once 'conexion.php';

echo "<h2>🔍 Diagnóstico Avanzado de Cupos</h2>";

try {
    // 1. Verificar datos exactos de la tabla disponibilidad
    echo "<h3>📊 Datos Exactos de Disponibilidad</h3>";
    $stmt = $pdo->query('SELECT * FROM disponibilidad ORDER BY id_disponibilidad');
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #007bff; color: white;'>";
    echo "<th>ID</th><th>Profesor</th><th>Materia</th><th>Fecha</th><th>Hora</th>";
    echo "<th>Cupo Máximo</th><th>Cupo Actual</th><th>Disponibles</th><th>Estado</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch()) {
        $disponibles = $row['cupo_maximo'] - $row['cupo_actual'];
        $color = ($disponibles > 0) ? '#d4edda' : '#f8d7da';
        
        echo "<tr style='background: $color;'>";
        echo "<td>" . $row['id_disponibilidad'] . "</td>";
        echo "<td>" . $row['id_profesor'] . "</td>";
        echo "<td>" . ($row['id_materia'] ?: 'NULL') . "</td>";
        echo "<td>" . $row['fecha'] . "</td>";
        echo "<td>" . substr($row['hora_inicio'], 0, 5) . "</td>";
        echo "<td>" . $row['cupo_maximo'] . "</td>";
        echo "<td><strong style='color: red;'>" . $row['cupo_actual'] . "</strong></td>";
        echo "<td>" . $disponibles . "</td>";
        echo "<td>" . $row['estado'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Verificar todas las inscripciones
    echo "<h3>📝 Todas las Inscripciones</h3>";
    $stmt = $pdo->query('
        SELECT 
            ti.id_inscripcion,
            ti.id_disponibilidad,
            ti.id_estudiante,
            ti.id_profesor,
            ti.estado,
            ti.fecha_inscripcion,
            u.nombre as estudiante_nombre
        FROM tutoria_inscripciones ti
        JOIN estudiantes e ON ti.id_estudiante = e.id_estudiante
        JOIN usuarios u ON e.id_usuario = u.id_usuario
        ORDER BY ti.id_disponibilidad, ti.estado
    ');
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #007bff; color: white;'>";
    echo "<th>ID</th><th>Disponibilidad</th><th>Estudiante</th><th>Estado</th><th>Fecha Inscripción</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch()) {
        $color = match($row['estado']) {
            'inscrito' => '#d4edda',
            'cancelado' => '#fff3cd',
            'asistio' => '#d1ecf1',
            'no_asistio' => '#f8d7da',
            default => '#f0f0f0'
        };
        
        echo "<tr style='background: $color;'>";
        echo "<td>" . $row['id_inscripcion'] . "</td>";
        echo "<td>" . $row['id_disponibilidad'] . "</td>";
        echo "<td>" . htmlspecialchars($row['estudiante_nombre']) . "</td>";
        echo "<td><strong>" . $row['estado'] . "</strong></td>";
        echo "<td>" . $row['fecha_inscripcion'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Análisis por disponibilidad
    echo "<h3>🔍 Análisis Detallado por Disponibilidad</h3>";
    $stmt = $pdo->query('
        SELECT 
            d.id_disponibilidad,
            d.cupo_maximo,
            d.cupo_actual,
            COUNT(ti.id_inscripcion) as total_inscripciones,
            COUNT(CASE WHEN ti.estado = "inscrito" THEN 1 END) as activas,
            COUNT(CASE WHEN ti.estado = "cancelado" THEN 1 END) as canceladas,
            COUNT(CASE WHEN ti.estado IN ("asistio", "no_asistio") THEN 1 END) as completadas,
            (d.cupo_maximo - d.cupo_actual) as disponibles_bd,
            (d.cupo_maximo - COUNT(CASE WHEN ti.estado = "inscrito" THEN 1 END)) as disponibles_reales
        FROM disponibilidad d
        LEFT JOIN tutoria_inscripciones ti ON d.id_disponibilidad = ti.id_disponibilidad
        GROUP BY d.id_disponibilidad
        ORDER BY d.id_disponibilidad
    ');
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #007bff; color: white;'>";
    echo "<th>Disponibilidad</th><th>Cupo Máximo</th><th>Cupo Actual</th>";
    echo "<th>Total Inscripciones</th><th>Activas</th><th>Canceladas</th><th>Completadas</th>";
    echo "<th>Disponibles BD</th><th>Disponibles Reales</th><th>¿Coinciden?</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch()) {
        $coinciden = ($row['disponibles_bd'] == $row['disponibles_reales']) ? '✅ Sí' : '❌ No';
        $color = ($row['disponibles_bd'] == $row['disponibles_reales']) ? '#d4edda' : '#f8d7da';
        
        echo "<tr style='background: $color;'>";
        echo "<td><strong>" . $row['id_disponibilidad'] . "</strong></td>";
        echo "<td>" . $row['cupo_maximo'] . "</td>";
        echo "<td><strong style='color: red;'>" . $row['cupo_actual'] . "</strong></td>";
        echo "<td>" . $row['total_inscripciones'] . "</td>";
        echo "<td style='color: green; font-weight: bold;'>" . $row['activas'] . "</td>";
        echo "<td style='color: orange;'>" . $row['canceladas'] . "</td>";
        echo "<td style='color: blue;'>" . $row['completadas'] . "</td>";
        echo "<td>" . $row['disponibles_bd'] . "</td>";
        echo "<td><strong>" . $row['disponibles_reales'] . "</strong></td>";
        echo "<td>$coinciden</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Verificar si hay datos de prueba inconsistentes
    echo "<h3>🔍 Verificación de Datos de Prueba</h3>";
    
    // Verificar si hay inscripciones sin disponibilidad correspondiente
    $stmt = $pdo->query('
        SELECT ti.id_inscripcion, ti.id_disponibilidad 
        FROM tutoria_inscripciones ti 
        LEFT JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad 
        WHERE d.id_disponibilidad IS NULL
    ');
    
    $inscripciones_huerfanas = $stmt->fetchAll();
    
    if (!empty($inscripciones_huerfanas)) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545; margin: 20px 0;'>";
        echo "<h4>⚠️ Inscripciones sin Disponibilidad Correspondiente:</h4>";
        foreach ($inscripciones_huerfanas as $inscripcion) {
            echo "<p>Inscripción ID: " . $inscripcion['id_inscripcion'] . " → Disponibilidad ID: " . $inscripcion['id_disponibilidad'] . " (NO EXISTE)</p>";
        }
        echo "</div>";
    }
    
    // Verificar disponibilidades sin inscripciones pero con cupo_actual > 0
    $stmt = $pdo->query('
        SELECT d.id_disponibilidad, d.cupo_actual 
        FROM disponibilidad d 
        LEFT JOIN tutoria_inscripciones ti ON d.id_disponibilidad = ti.id_disponibilidad 
        WHERE ti.id_disponibilidad IS NULL AND d.cupo_actual > 0
    ');
    
    $disponibilidades_con_cupo_sin_inscripciones = $stmt->fetchAll();
    
    if (!empty($disponibilidades_con_cupo_sin_inscripciones)) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
        echo "<h4>⚠️ Disponibilidades con Cupo pero sin Inscripciones:</h4>";
        foreach ($disponibilidades_con_cupo_sin_inscripciones as $disp) {
            echo "<p>Disponibilidad ID: " . $disp['id_disponibilidad'] . " → Cupo Actual: " . $disp['cupo_actual'] . " (DEBERÍA SER 0)</p>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
    echo "<h4>❌ Error: " . $e->getMessage() . "</h4>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h3 { color: #555; margin-top: 30px; }
table { width: 100%; }
th { background: #007bff; color: white; }
</style>

<p><a href="dashboard_estudiante.php">← Volver al Dashboard</a></p>
