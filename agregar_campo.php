<?php
require_once 'conexion.php';

echo "<h2>Agregando Campo id_materia</h2>";

try {
    // Verificar si el campo ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM disponibilidad LIKE 'id_materia'");
    $campo_existe = $stmt->fetch();
    
    if ($campo_existe) {
        echo "<p style='color: green;'>✅ El campo 'id_materia' ya existe</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Agregando campo 'id_materia'...</p>";
        
        // Agregar el campo
        $pdo->exec("ALTER TABLE disponibilidad ADD COLUMN id_materia int(11) DEFAULT NULL AFTER id_profesor");
        
        echo "<p style='color: green;'>✅ Campo 'id_materia' agregado exitosamente</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎯 Resultado:</h3>";
    echo "<p>El campo 'id_materia' está disponible en la tabla 'disponibilidad'.</p>";
    echo "<p>Ahora puedes probar el dashboard del profesor.</p>";
    
    echo "<p><a href='dashboard_profesor.php'>Ir al Dashboard del Profesor</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
