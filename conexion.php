<?php
// Conexión PDO a la base de datos 'sistema_tutorias'
// Ajusta las credenciales según tu entorno (XAMPP suele usar user 'root' y sin contraseña)
$db_host = '127.0.0.1';
$db_name = 'sistema_tutorias';
$db_user = 'root';
$db_pass = '';

try {
	$pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);
} catch (PDOException $e) {
	// En un entorno de producción registra el error en lugar de mostrarlo
	die('Error de conexión a la base de datos: ' . $e->getMessage());
}
