<?php
require_once 'conexion.php';

echo "<h2>Creando Tabla de Opiniones (Versión Simplificada)</h2>";

try {
    // Primero, eliminar la tabla si existe para evitar conflictos
    $pdo->exec("DROP TABLE IF EXISTS opiniones");
    
    // Crear tabla opiniones sin claves foráneas complicadas
    $sql = "
    CREATE TABLE opiniones (
        id_opinion int(11) NOT NULL AUTO_INCREMENT,
        id_tutoria int(11) NOT NULL,
        id_emisor int(11) NOT NULL,
        id_receptor int(11) NOT NULL,
        tipo_emisor enum('profesor','estudiante') NOT NULL,
        tipo_receptor enum('profesor','estudiante') NOT NULL,
        calificacion int(1) NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
        comentario text,
        fecha_opinion timestamp DEFAULT CURRENT_TIMESTAMP,
        estado enum('visible','oculto') DEFAULT 'visible',
        PRIMARY KEY (id_opinion),
        UNIQUE KEY unique_opinion (id_tutoria, id_emisor, tipo_emisor),
        KEY idx_tutoria (id_tutoria),
        KEY idx_emisor (id_emisor),
        KEY idx_receptor (id_receptor)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
    ";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Tabla 'opiniones' creada exitosamente (versión simplificada)</p>";
    
    // Verificar si hay datos
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM opiniones');
    $result = $stmt->fetch();
    echo "<p><strong>Opiniones registradas:</strong> " . $result['total'] . "</p>";
    
    // Mostrar estructura de la tabla
    echo "<h3>Estructura de la tabla creada:</h3>";
    $stmt = $pdo->query("DESCRIBE opiniones");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>🎯 Características del Sistema:</h3>";
    echo "<ul>";
    echo "<li>✅ Profesores pueden calificar estudiantes (1-5 estrellas)</li>";
    echo "<li>✅ Estudiantes pueden calificar profesores (1-5 estrellas)</li>";
    echo "<li>✅ Comentarios detallados para cada calificación</li>";
    echo "<li>✅ Sistema anti-duplicado (una opinión por tutoría)</li>";
    echo "<li>✅ Moderación (visible/oculto)</li>";
    echo "<li>✅ Registro automático de fecha</li>";
    echo "<li>✅ Sin claves foráneas (evita errores de creación)</li>";
    echo "</ul>";
    
    echo "<p><a href='dashboard_profesor.php'>Ir al Dashboard del Profesor</a></p>";
    echo "<p><a href='dashboard_estudiante.php'>Ir al Dashboard del Estudiante</a></p>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>🎉 ¡Sistema de Opiniones Activado!</h3>";
    echo "<p style='color: #155724;'>La tabla ha sido creada exitosamente. Ahora puedes:</p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>Calificar estudiantes desde el dashboard del profesor</li>";
    echo "<li>Calificar profesores desde el dashboard del estudiante</li>";
    echo "<li>Ver estadísticas y promedios automáticos</li>";
    echo "<li>Dejar comentarios detallados</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    
    // Mostrar información de depuración
    echo "<h3>🔍 Información de Depuración:</h3>";
    echo "<p>Este error puede ocurrir por:</p>";
    echo "<ul>";
    echo "<li>La base de datos no tiene permisos para crear tablas</li>";
    echo "<li>Ya existe una tabla 'opiniones' con estructura diferente</li>";
    echo "<li>Problemas con el motor de almacenamiento InnoDB</li>";
    echo "</ul>";
    
    // Intentar mostrar tablas existentes
    try {
        echo "<h4>Tablas existentes en la base de datos:</h4>";
        $stmt = $pdo->query("SHOW TABLES");
        echo "<ul>";
        while ($row = $stmt->fetch()) {
            echo "<li>" . htmlspecialchars(array_values($row)[0]) . "</li>";
        }
        echo "</ul>";
    } catch (Exception $e2) {
        echo "<p>No se pudo listar las tablas: " . $e2->getMessage() . "</p>";
    }
}
?>
