<?php
// Configuración de base de datos - Archivo de configuración manual
// Usa este archivo si necesitas cambiar manualmente entre entornos

// === CONFIGURACIÓN PARA INFINITYFREE ===
define('DB_INFINITYFREE_HOST', 'sql101.infinityfree.com'); // CORREGIDO
define('DB_INFINITYFREE_NAME', 'if0_41391897_sistema_tutorias');
define('DB_INFINITYFREE_USER', 'if0_41391897');
define('DB_INFINITYFREE_PASS', 'PZXGUEg21uzLF');

// === CONFIGURACIÓN PARA XAMPP LOCAL ===
define('DB_LOCAL_HOST', '127.0.0.1');
define('DB_LOCAL_NAME', 'sistema_tutorias');
define('DB_LOCAL_USER', 'root');
define('DB_LOCAL_PASS', '');

// === SELECCIÓN MANUAL DE ENTORNO ===
// Cambia esta variable para cambiar entre entornos:
// 'local' para XAMPP local
// 'infinityfree' para hosting InfinityFree
define('DB_ENVIRONMENT', 'local'); // <-- CAMBIA ESTO SEGÚN NECESITES

// === CONEXIÓN AUTOMÁTICA SEGÚN ENTORNO ===
if (DB_ENVIRONMENT === 'infinityfree') {
    $db_host = DB_INFINITYFREE_HOST;
    $db_name = DB_INFINITYFREE_NAME;
    $db_user = DB_INFINITYFREE_USER;
    $db_pass = DB_INFINITYFREE_PASS;
} else {
    $db_host = DB_LOCAL_HOST;
    $db_name = DB_LOCAL_NAME;
    $db_user = DB_LOCAL_USER;
    $db_pass = DB_LOCAL_PASS;
}

// === CONEXIÓN PDO ===
try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    
    // Opcional: Mostrar mensaje de éxito en desarrollo
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo '<!-- Conectado exitosamente a: ' . $db_name . ' (' . DB_ENVIRONMENT . ') -->';
    }
    
} catch (PDOException $e) {
    $error_details = "
    <div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px; font-family: Arial, sans-serif;'>
        <h3>❌ Error de Conexión a la Base de Datos</h3>
        <p><strong>Error:</strong> " . $e->getMessage() . "</p>
        <p><strong>Host:</strong> " . $db_host . "</p>
        <p><strong>Base de Datos:</strong> " . $db_name . "</p>
        <p><strong>Usuario:</strong> " . $db_user . "</p>
        <p><strong>Entorno:</strong> " . DB_ENVIRONMENT . "</p>
        <hr>
        <h4>🔧 Soluciones:</h4>
        <ul>
            <li>Verifica que el servidor de base de datos está activo</li>
            <li>Confirma que las credenciales son correctas</li>
            <li>Asegúrate que la base de datos existe</li>
            <li>Para local: Inicia XAMPP y MySQL</li>
            <li>Para InfinityFree: Verifica el host y credenciales</li>
        </ul>
    </div>";
    
    die($error_details);
}
?>
