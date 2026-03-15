<?php
require_once 'conexion.php';

echo "<h2>Creando Tabla: tutoria_inscripciones_materias</h2>";

try {
    // Crear la tabla tutoria_inscripciones_materias
    $sql = "
    CREATE TABLE IF NOT EXISTS tutoria_inscripciones_materias (
        id int(11) NOT NULL AUTO_INCREMENT,
        id_inscripcion int(11) NOT NULL,
        id_materia int(11) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY unique_inscripcion_materia (id_inscripcion, id_materia),
        KEY fk_tutoria_inscripciones_materias_inscripcion (id_inscripcion),
        KEY fk_tutoria_inscripciones_materias_materia (id_materia),
        CONSTRAINT fk_tutoria_inscripciones_materias_inscripcion FOREIGN KEY (id_inscripcion) REFERENCES tutoria_inscripciones (id_inscripcion) ON DELETE CASCADE,
        CONSTRAINT fk_tutoria_inscripciones_materias_materia FOREIGN KEY (id_materia) REFERENCES materias (id_materia) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
    ";
    
    $pdo->exec($sql);
    echo "<p>✅ Tabla 'tutoria_inscripciones_materias' creada exitosamente.</p>";
    
    // Verificar si hay datos
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM tutoria_inscripciones_materias');
    $result = $stmt->fetch();
    echo "<p><strong>Registros actuales:</strong> " . $result['total'] . "</p>";
    
    echo "<hr>";
    echo "<p><strong>🎯 Cambio implementado:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Tabla para registrar qué materia eligió cada estudiante</li>";
    echo "<li>✅ Soporte para múltiples materias por disponibilidad</li>";
    echo "<li>✅ Mayor flexibilidad en el sistema de tutorías</li>";
    echo "</ul>";
    
    echo "<p><a href='dashboard_estudiante.php'>Ir al Dashboard del Estudiante</a></p>";
    echo "<p><a href='dashboard_profesor.php'>Ir al Dashboard del Profesor</a></p>";
    echo "<p><a href='dashboard_admin.php'>Ir al Panel Admin</a></p>";
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
