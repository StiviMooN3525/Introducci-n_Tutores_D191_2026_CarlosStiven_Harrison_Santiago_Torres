<?php
require_once 'conexion.php';

echo "<h2>Creando Tabla: disponibilidad_materias</h2>";

try {
    // Crear la tabla disponibilidad_materias
    $sql = "
    CREATE TABLE IF NOT EXISTS disponibilidad_materias (
        id int(11) NOT NULL AUTO_INCREMENT,
        id_disponibilidad int(11) NOT NULL,
        id_materia int(11) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY unique_disponibilidad_materia (id_disponibilidad, id_materia),
        KEY fk_disponibilidad_materias_disponibilidad (id_disponibilidad),
        KEY fk_disponibilidad_materias_materia (id_materia),
        CONSTRAINT fk_disponibilidad_materias_disponibilidad FOREIGN KEY (id_disponibilidad) REFERENCES disponibilidad (id_disponibilidad) ON DELETE CASCADE,
        CONSTRAINT fk_disponibilidad_materias_materia FOREIGN KEY (id_materia) REFERENCES materias (id_materia) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
    ";
    
    $pdo->exec($sql);
    echo "<p>✅ Tabla 'disponibilidad_materias' creada exitosamente.</p>";
    
    // Verificar si hay datos
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM disponibilidad_materias');
    $result = $stmt->fetch();
    echo "<p><strong>Registros actuales:</strong> " . $result['total'] . "</p>";
    
    echo "<hr>";
    echo "<p><strong>🎯 Cambio implementado:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Los profesores ahora pueden elegir múltiples materias al crear disponibilidades</li>";
    echo "<li>✅ Los estudiantes podrán inscribirse a cualquiera de las materias ofrecidas</li>";
    echo "<li>✅ Mayor flexibilidad en el sistema de tutorías</li>";
    echo "</ul>";
    
    echo "<p><a href='dashboard_profesor.php'>Ir al Dashboard del Profesor</a></p>";
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
