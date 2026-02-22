<?php include_once 'header.php'; ?>

<section class="card">
	<h1>Bienvenido al Sistema de Tutorías</h1>
	<p class="muted">Reserva y gestiona tutorías con profesores, revisa historial y administra usuarios según tu rol.</p>
	<div style="margin-top:12px">
		<a class="btn" href="login.php">Iniciar sesión</a>
		<a class="btn ghost" href="registro.php">Registrarse</a>
	</div>
</section>

<section class="grid">
	<div class="card">
		<h3>Estudiantes</h3>
		<p class="muted">Ver profesores disponibles, reservar y ver historial de tutorías.</p>
	</div>
	<div class="card">
		<h3>Profesores</h3>
		<p class="muted">Registrar disponibilidad, gestionar solicitudes y marcar tutorías completadas.</p>
	</div>
	<div class="card">
		<h3>Administradores</h3>
		<p class="muted">Gestionar usuarios, roles y supervisar todas las tutorías.</p>
	</div>
</section>

<?php include_once 'footer.php'; ?>
