<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Sistema de Tutorías</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="css/diseño.css">
</head>
<body<?php if (basename($_SERVER['PHP_SELF']) == 'login.php') echo ' class="login-page"'; ?>>
	<?php if (basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
	<header class="site-header">
		<div class="container header-inner">
			<a class="brand" href="index.php">Sistema de Tutorías</a>
			<nav class="main-nav">
				<a href="index.php">Inicio</a>
				<?php if(!isset($_SESSION['user_id'])): ?>
					<a href="login.php">Iniciar sesión</a>
					<a href="registro.php">Registro</a>
					<a href="recuperar.php">Recuperar acceso</a>
				<?php else: ?>
					<a href="logout.php">Cerrar sesión</a>
				<?php endif; ?>
			</nav>
		</div>
	</header>
	<?php endif; ?>
	<main class="container">

