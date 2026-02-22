<?php
include_once 'header.php';
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$nombre = trim($_POST['nombre'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$pass = $_POST['password'] ?? '';
	$codigo = trim($_POST['codigo_estudiantil'] ?? '');
	$carrera = trim($_POST['carrera'] ?? '');
	$semestre = intval($_POST['semestre'] ?? 0) ?: null;

	// Validaciones básicas
	if (!$nombre || !$email || !$pass) {
		$error = 'Completa todos los campos.';
	} else {
		// Comprobar correo duplicado
		$stmtCheck = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE correo = :e LIMIT 1');
		$stmtCheck->execute(['e' => $email]);
		if ($stmtCheck->fetchColumn()) {
			$error = 'El correo ya está registrado.';
		} else {
			// Insertar usuario como estudiante (contraseña en texto plano por requisito)
			$stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (:n, :e, :c, :r)');
			$stmt->execute([
				'n' => $nombre,
				'e' => $email,
				'c' => $pass,
				'r' => 'estudiante',
			]);
			$newUserId = $pdo->lastInsertId();

			// Si se proporcionó código estudiantil, verificar unicidad y crear registro en estudiantes
			if ($codigo) {
				$chkCod = $pdo->prepare('SELECT id_estudiante FROM estudiantes WHERE codigo_estudiantil = :cod LIMIT 1');
				$chkCod->execute(['cod' => $codigo]);
				if ($chkCod->fetchColumn()) {
					// eliminar usuario creado y notificar error
					$pdo->prepare('DELETE FROM usuarios WHERE id_usuario = :id')->execute(['id' => $newUserId]);
					$error = 'El código estudiantil ya está registrado.';
				} else {
					$insEst = $pdo->prepare('INSERT INTO estudiantes (id_usuario, codigo_estudiantil, carrera, semestre) VALUES (:u, :cod, :car, :sem)');
					$insEst->execute(['u' => $newUserId, 'cod' => $codigo, 'car' => $carrera ?: null, 'sem' => $semestre]);
					header('Location: login.php');
					exit;
				}
			} else {
				// Crear estudiante sin código (opcional)
				$insEst = $pdo->prepare('INSERT INTO estudiantes (id_usuario, codigo_estudiantil, carrera, semestre) VALUES (:u, NULL, :car, :sem)');
				$insEst->execute(['u' => $newUserId, 'car' => $carrera ?: null, 'sem' => $semestre]);
				header('Location: login.php');
				exit;
			}
		}
	}
}
?>

<div class="card" style="max-width:560px;margin:18px auto">
	<h2>Registro</h2>
	<?php if(!empty($error)): ?><p class="muted" style="color:#b91c1c"><?php echo $error; ?></p><?php endif; ?>
	<form method="post">
		<div class="field">
			<label>Nombre completo</label>
			<input type="text" name="nombre" required>
		</div>
		<div class="field">
			<label>Correo electrónico</label>
			<input type="email" name="email" required>
		</div>
		<div class="field">
			<label>Código estudiantil</label>
			<input type="text" name="codigo_estudiantil">
		</div>
		<div class="field">
			<label>Carrera</label>
			<input type="text" name="carrera">
		</div>
		<div class="field">
			<label>Semestre</label>
			<input type="number" name="semestre" min="1">
		</div>
		<div class="field">
			<label>Contraseña</label>
			<input type="password" name="password" required>
		</div>
		<div>
			<button class="btn" type="submit">Crear cuenta</button>
			<a class="btn ghost" href="login.php">Ir a iniciar sesión</a>
		</div>
	</form>
</div>

<?php include_once 'footer.php'; ?>

