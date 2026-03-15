<?php
// Script para verificar la estructura de la base de datos
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>🔍 Verificación de Estructura de Base de Datos</h3>";

try {
    include 'conexion.php';
    echo "<p style='color: green;'>✅ Conexión a BD exitosa</p>";
    
    // 1. Verificar tabla opiniones
    echo "<h4>📋 Estructura de tabla 'opiniones':</h4>";
    $stmt = $pdo->query("DESCRIBE opiniones");
    $columnas = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    foreach ($columnas as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Verificar datos en opiniones
    echo "<h4>📊 Datos en tabla 'opiniones':</h4>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM opiniones");
    $total = $stmt->fetch();
    echo "<p>Total de opiniones: <strong>" . $total['total'] . "</strong></p>";
    
    if ($total['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM opiniones LIMIT 5");
        $datos = $stmt->fetchAll();
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr>";
        foreach ($columnas as $col) {
            echo "<th>" . htmlspecialchars($col['Field']) . "</th>";
        }
        echo "</tr>";
        foreach ($datos as $row) {
            echo "<tr>";
            foreach ($columnas as $col) {
                $campo = $col['Field'];
                echo "<td>" . htmlspecialchars($row[$campo] ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Verificar tabla tutoria_inscripciones
    echo "<h4>📋 Estructura de tabla 'tutoria_inscripciones':</h4>";
    $stmt = $pdo->query("DESCRIBE tutoria_inscripciones");
    $columnas_ti = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    foreach ($columnas_ti as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Verificar tabla disponibilidad
    echo "<h4>📋 Estructura de tabla 'disponibilidad':</h4>";
    $stmt = $pdo->query("DESCRIBE disponibilidad");
    $columnas_d = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    foreach ($columnas_d as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Verificar relaciones
    echo "<h4>🔗 Verificación de Relaciones:</h4>";
    
    // ¿Existen inscripciones?
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tutoria_inscripciones");
    $total_ti = $stmt->fetch();
    echo "<p>Tutorías inscritas: <strong>" . $total_ti['total'] . "</strong></p>";
    
    // ¿Existen disponibilidades?
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disponibilidad");
    $total_d = $stmt->fetch();
    echo "<p>Disponidades: <strong>" . $total_d['total'] . "</strong></p>";
    
    // Estados de inscripciones
    if ($total_ti['total'] > 0) {
        $stmt = $pdo->query("SELECT estado, COUNT(*) as count FROM tutoria_inscripciones GROUP BY estado");
        $estados = $stmt->fetchAll();
        echo "<h5>Estados de inscripciones:</h5>";
        foreach ($estados as $estado) {
            echo "<p>" . htmlspecialchars($estado['estado']) . ": " . $estado['count'] . "</p>";
        }
    }
    
    // 6. Probar la consulta problemática
    echo "<h4>🧪 Probando la consulta de opiniones:</h4>";
    
    // Para estudiantes
    $stmt_test = $pdo->prepare("
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
                            AND o.tipo_emisor = 'estudiante'
        WHERE ti.id_estudiante = :estudiante_id 
            AND ti.estado IN ('asistio', 'no_asistio')
            AND d.fecha < CURDATE()
        ORDER BY d.fecha DESC
        LIMIT 5
    ");
    
    // Usar un ID de estudiante de prueba si existe
    $stmt_est = $pdo->query("SELECT id_estudiante FROM estudiantes LIMIT 1");
    $test_est = $stmt_est->fetch();
    
    if ($test_est) {
        echo "<p>Probando con estudiante ID: " . $test_est['id_estudiante'] . "</p>";
        $stmt_test->execute(['emisor_id' => $test_est['id_estudiante'], 'estudiante_id' => $test_est['id_estudiante']]);
        $resultados = $stmt_test->fetchAll();
        
        echo "<p>Resultados encontrados: <strong>" . count($resultados) . "</strong></p>";
        
        if (count($resultados) > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Profesor</th><th>Fecha</th><th>Materia</th><th>Estado</th><th>Opinión</th></tr>";
            foreach ($resultados as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['profesor_nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($row['tutoria_fecha']) . "</td>";
                echo "<td>" . htmlspecialchars($row['materia_nombre'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['estado']) . "</td>";
                echo "<td>" . ($row['id_opinion'] ? 'Sí' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No hay estudiantes en la base de datos para probar</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<hr>
<h3>📝 Conclusión:</h3>
<p>Revisa los resultados arriba. Si ves algún problema en la estructura de las tablas o tipos de datos, ese podría ser el origen de los errores SQL.</p>
