<?php
require_once 'conexion.php';

echo "<h2>Creando Sistema de Opiniones y Calificaciones</h2>";

try {
    // Crear tabla de opiniones/calificaciones
    $sql = "
    CREATE TABLE IF NOT EXISTS opiniones (
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
        KEY fk_opiniones_tutoria (id_tutoria),
        KEY fk_opiniones_emisor (id_emisor),
        KEY fk_opiniones_receptor (id_receptor),
        CONSTRAINT fk_opiniones_tutoria FOREIGN KEY (id_tutoria) REFERENCES tutoria_inscripciones (id_inscripcion) ON DELETE CASCADE,
        CONSTRAINT fk_opiniones_emisor FOREIGN KEY (id_emisor) REFERENCES usuarios (id_usuario) ON DELETE CASCADE,
        CONSTRAINT fk_opiniones_receptor FOREIGN KEY (id_receptor) REFERENCES usuarios (id_usuario) ON DELETE CASCADE,
        UNIQUE KEY unique_opinion (id_tutoria, id_emisor, tipo_emisor)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
    ";
    
    $pdo->exec($sql);
    echo "<p>✅ Tabla 'opiniones' creada exitosamente</p>";
    
    // Verificar si hay datos
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM opiniones');
    $result = $stmt->fetch();
    echo "<p><strong>Opiniones registradas:</strong> " . $result['total'] . "</p>";
    
    echo "<hr>";
    echo "<h3>🎯 Características del Sistema:</h3>";
    echo "<ul>";
    echo "<li>✅ Profesores pueden calificar estudiantes (1-5 estrellas)</li>";
    echo "<li>✅ Estudiantes pueden calificar profesores (1-5 estrellas)</li>";
    echo "<li>✅ Comentarios detallados para cada calificación</li>";
    echo "<li>✅ Sistema anti-duplicado (una opinión por tutoría)</li>";
    echo "<li>✅ Moderación (visible/oculto)</li>";
    echo "<li>✅ Registro automático de fecha</li>";
    echo "</ul>";
    
    echo "<p><a href='dashboard_profesor.php'>Ir al Dashboard del Profesor</a></p>";
    echo "<p><a href='dashboard_estudiante.php'>Ir al Dashboard del Estudiante</a></p>";
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
