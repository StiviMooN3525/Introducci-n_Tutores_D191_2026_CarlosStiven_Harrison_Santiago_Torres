<?php
// Script para probar conexión a InfinityFree
echo "<h3>🔍 Probando Conexión a InfinityFree</h3>";

// Configuración InfinityFree
$db_host = 'sql311.infinityfree.com';
$db_name = 'if0_41391897_sistema_tutorias';
$db_user = 'if0_41391897';
$db_pass = 'PZXGUEg21uzLF';

echo "<p><strong>Host:</strong> $db_host</p>";
echo "<p><strong>Base de Datos:</strong> $db_name</p>";
echo "<p><strong>Usuario:</strong> $db_user</p>";
echo "<p><strong>Contraseña:</strong> " . str_repeat('*', strlen($db_pass)) . "</p>";

echo "<h4>🔄 Intentando conectar...</h4>";

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10 // Timeout de 10 segundos
    ]);
    
    echo "<p style='color: green; font-weight: bold;'>✅ ¡Conexión exitosa a InfinityFree!</p>";
    
    // Probar consulta simple
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "<p style='color: blue;'>📊 Usuarios en la base de datos: " . $result['total'] . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error de conexión:</p>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    
    // Análisis del error
    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "<p style='color: orange;'>🔧 Solución: El servidor MySQL no está respondiendo. Verifica el hostname.</p>";
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<p style='color: orange;'>🔧 Solución: Usuario o contraseña incorrectos. Verifica credenciales.</p>";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p style='color: orange;'>🔧 Solución: La base de datos no existe. Créala desde el panel.</p>";
    }
}

echo "<hr>";
echo "<h4>🌐 Información del Servidor</h4>";
echo "<p><strong>Server Name:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'No disponible') . "</p>";
echo "<p><strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'No disponible') . "</p>";
echo "<p><strong>Server IP:</strong> " . ($_SERVER['SERVER_ADDR'] ?? 'No disponible') . "</p>";

// Detectar si estamos en InfinityFree
$is_infinityfree = strpos($_SERVER['HTTP_HOST'] ?? '', 'infinityfree') !== false;
echo "<p><strong>Entorno Detectado:</strong> " . ($is_infinityfree ? 'InfinityFree ✅' : 'Local/Other ❌') . "</p>";
?>
