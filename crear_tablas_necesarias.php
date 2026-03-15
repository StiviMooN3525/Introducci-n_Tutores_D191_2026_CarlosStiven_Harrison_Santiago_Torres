<?php
require_once 'conexion.php';

echo "<h2>Creando Tablas Necesarias para Nuevo Sistema</h2>";

$errores = [];
$exitos = [];

try {
    // 1. Crear tabla disponibilidad_materias
    echo "<h3>1. Creando tabla: disponibilidad_materias</h3>";
    $sql1 = "
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
    
    $pdo->exec($sql1);
    $exitos[] = "✅ Tabla 'disponibilidad_materias' creada exitosamente";
    
    // Verificar si hay datos
    $stmt1 = $pdo->query('SELECT COUNT(*) as total FROM disponibilidad_materias');
    $result1 = $stmt1->fetch();
    $exitos[] = "📊 Registros en disponibilidad_materias: " . $result1['total'];
    
} catch (Exception $e) {
    $errores[] = "❌ Error creando disponibilidad_materias: " . $e->getMessage();
}

try {
    // 2. Crear tabla tutoria_inscripciones_materias
    echo "<h3>2. Creando tabla: tutoria_inscripciones_materias</h3>";
    $sql2 = "
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
    
    $pdo->exec($sql2);
    $exitos[] = "✅ Tabla 'tutoria_inscripciones_materias' creada exitosamente";
    
    // Verificar si hay datos
    $stmt2 = $pdo->query('SELECT COUNT(*) as total FROM tutoria_inscripciones_materias');
    $result2 = $stmt2->fetch();
    $exitos[] = "📊 Registros en tutoria_inscripciones_materias: " . $result2['total'];
    
} catch (Exception $e) {
    $errores[] = "❌ Error creando tutoria_inscripciones_materias: " . $e->getMessage();
}

// 3. Verificar tablas existentes
echo "<h3>3. Verificación de Tablas</h3>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE '%materias%' OR SHOW TABLES LIKE '%inscripciones%'");
    $table_list = [];
    while ($row = $tables->fetch()) {
        $table_list[] = array_values($row)[0];
    }
    
    if (in_array('disponibilidad_materias', $table_list)) {
        $exitos[] = "✅ disponibilidad_materias existe";
    } else {
        $errores[] = "❌ disponibilidad_materias no existe";
    }
    
    if (in_array('tutoria_inscripciones_materias', $table_list)) {
        $exitos[] = "✅ tutoria_inscripciones_materias existe";
    } else {
        $errores[] = "❌ tutoria_inscripciones_materias no existe";
    }
    
    if (in_array('materias', $table_list)) {
        $exitos[] = "✅ materias existe";
    } else {
        $errores[] = "❌ materias no existe";
    }
    
    if (in_array('tutoria_inscripciones', $table_list)) {
        $exitos[] = "✅ tutoria_inscripciones existe";
    } else {
        $errores[] = "❌ tutoria_inscripciones no existe";
    }
    
} catch (Exception $e) {
    $errores[] = "❌ Error verificando tablas: " . $e->getMessage();
}

// Mostrar resultados
echo "<hr>";

if (!empty($exitos)) {
    echo "<h3 style='color: green;'>✅ Exitos:</h3>";
    foreach ($exitos as $exito) {
        echo "<p style='color: green;'>$exito</p>";
    }
}

if (!empty($errores)) {
    echo "<h3 style='color: red;'>❌ Errores:</h3>";
    foreach ($errores as $error) {
        echo "<p style='color: red;'>$error</p>";
    }
}

echo "<hr>";
echo "<h3>🎯 Próximos Pasos:</h3>";
echo "<ol>";
echo "<li><a href='dashboard_admin.php'>Ir al Panel Admin</a> (ya debería funcionar)</li>";
echo "<li><a href='dashboard_profesor.php'>Ir al Dashboard del Profesor</a> para crear disponibilidades</li>";
echo "<li><a href='dashboard_estudiante.php'>Ir al Dashboard del Estudiante</a> para inscribirse</li>";
echo "</ol>";

if (empty($errores)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>🎉 ¡Sistema Listo!</h3>";
    echo "<p style='color: #155724;'>Todas las tablas necesarias han sido creadas. El nuevo sistema flexible de materias está operativo.</p>";
    echo "</div>";
}
?>
