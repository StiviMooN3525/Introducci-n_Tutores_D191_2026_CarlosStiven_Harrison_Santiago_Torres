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
	<link rel="stylesheet" href="responsive.css">
	<link rel="stylesheet" href="table-responsive.css">
	<?php if (in_array(basename($_SERVER['PHP_SELF']), ['index.php','recuperar.php'])): ?>
		<link rel="stylesheet" href="css/intro.css?v=<?php echo time(); ?>">
	<?php endif; ?>
	<?php if (in_array(basename($_SERVER['PHP_SELF']), ['login.php','registro.php'])): ?>
		<link rel="stylesheet" href="css/login.css">
	<?php endif; ?>
	<?php if (in_array(basename($_SERVER['PHP_SELF']), ['dashboard_profesor.php','dashboard_estudiante.php','dashboard_admin.php','reportes.php'])): ?>
		<link rel="stylesheet" href="css/dashboard.css">
	<?php endif; ?>
</head>
<?php
$bodyClasses = [];
if (in_array(basename($_SERVER['PHP_SELF']), ['login.php','registro.php'])) {
    $bodyClasses[] = 'login-page';
}
if (in_array(basename($_SERVER['PHP_SELF']), ['dashboard_profesor.php','dashboard_estudiante.php','dashboard_admin.php','reportes.php'])) {
    $bodyClasses[] = 'dashboard-bg';
}
if (basename($_SERVER['PHP_SELF']) == 'index.php') {
    $bodyClasses[] = 'home-bg';
}
if (basename($_SERVER['PHP_SELF']) == 'recuperar.php') {
    $bodyClasses[] = 'recover-bg';
}
?>
<body<?php if (!empty($bodyClasses)) echo ' class="' . implode(' ', $bodyClasses) . '"'; ?>>
    <?php if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php','registro.php'])): ?>
	<header class="site-header">
		<div class="container header-inner">
			<?php $isHome = basename($_SERVER['PHP_SELF']) === 'index.php'; ?>
			<a class="brand" href="index.php"<?php if ($isHome) echo ' style="color:#556b2f"'; ?>>
				<span class="brand-text">Sistema de Tutorías</span>
			</a>
			<nav class="main-nav">
					<?php
					$currPage = basename($_SERVER['PHP_SELF']);
					$hideInicio = in_array($currPage, ['index.php', 'dashboard_profesor.php', 'dashboard_estudiante.php', 'dashboard_admin.php', 'reportes.php']);
					if (!$hideInicio) {
						echo '<a href="index.php">Inicio</a>';
					}
					if(!isset($_SESSION['user_id'])): 
						// En la página principal, solo mostrar "Recuperar acceso"
						if ($currPage === 'index.php'): ?>
							<a href="recuperar.php">Recuperar acceso</a>
						<?php else: ?>
							<!-- En otras páginas mostrar todos los enlaces -->
							<a href="login.php">Iniciar sesión</a>
							<a href="registro.php">Registro</a>
							<a href="recuperar.php">Recuperar acceso</a>
						<?php endif; ?>
					<?php else: ?>
						<a href="logout.php">Cerrar sesión</a>
					<?php endif; ?>
			</nav>
		</div>
	</header>
	<?php endif; ?>
	<main class="container-main">
