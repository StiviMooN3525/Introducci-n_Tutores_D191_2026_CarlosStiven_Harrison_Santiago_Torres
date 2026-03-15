<?php
require_once 'conexion.php';

echo "<h2>Verificando Campo id_materia en tabla disponibilidad</h2>";

try {
    // Verificar si el campo id_materia existe en la tabla disponibilidad
    $stmt = $pdo->query("SHOW COLUMNS FROM disponibilidad LIKE 'id_materia'");
    $campo_existe = $stmt->fetch();
    
    if ($campo_existe) {
        echo "<p style='color: green;'>✅ El campo 'id_materia' ya existe en la tabla disponibilidad</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ El campo 'id_materia' no existe. Agregándolo...</p>";
        
        // Agregar el campo id_materia
        $pdo->exec("ALTER TABLE disponibilidad ADD COLUMN id_materia int(11) DEFAULT NULL AFTER id_profesor");
        
        echo "<p style='color: green;'>✅ Campo 'id_materia' agregado exitosamente</p>";
    }
    
    // Verificar estructura actual de la tabla
    echo "<h3>Estructura actual de la tabla disponibilidad:</h3>";
    $stmt = $pdo->query("DESCRIBE disponibilidad");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>🎯 Próximos Pasos:</h3>";
    echo "<ol>";
    echo "<li><a href='dashboard_profesor.php'>Ir al Dashboard del Profesor</a> - Ya debería funcionar</li>";
    echo "<li><a href='crear_tablas_necesarias.php'>Crear Tablas Completas</a> - Para sistema completo</li>";
    echo "</ol>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>🎉 ¡Listo!</h3>";
    echo "<p style='color: #155724;'>El sistema ahora puede crear disponibilidades con materias.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
