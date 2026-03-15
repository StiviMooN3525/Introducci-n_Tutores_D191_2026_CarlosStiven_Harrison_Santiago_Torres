<?php
// Conexión PDO a la base de datos - Configuración InfinityFree
// Forzado para InfinityFree - Comenta estas líneas si trabajas en local

// Forzar configuración InfinityFree (cambia a false para local)
$force_infinityfree = true;

// Opción 1: Forzar InfinityFree
if ($force_infinityfree) {
    // Configuración para InfinityFree
    $db_host = 'sql101.infinityfree.com';
    $db_name = 'if0_41391897_sistema_tutorias';
    $db_user = 'if0_41391897';
    $db_pass = 'PZXGUEg21uzLF';
} else {
    // Configuración para XAMPP local
    $db_host = '127.0.0.1';
    $db_name = 'if0_41391897_sistema_tutorias';
    $db_user = 'root';
    $db_pass = '';
}

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
} catch (PDOException $e) {
    // Mostrar información detallada para depuración
    die('Error de conexión a la base de datos: ' . $e->getMessage() . 
        '<br><small>Host: ' . $db_host . ' | BD: ' . $db_name . ' | Entorno: ' . 
        ($force_infinityfree ? 'InfinityFree (Forzado)' : 'Local') . '</small>');
}
