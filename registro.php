<?php
include_once 'header.php';
require_once 'conexion.php';
require_once 'csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Solicitud inválida. Por favor intenta nuevamente.';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $pass = $_POST['password'] ?? '';
        $codigo = trim($_POST['codigo_estudiantil'] ?? '');
        $carrera = trim($_POST['carrera'] ?? '');
        $semestre = filter_var($_POST['semestre'] ?? 0, FILTER_VALIDATE_INT);

        // Validaciones mejoradas
        if (empty($nombre) || strlen($nombre) < 2 || strlen($nombre) > 100) {
            $error = 'El nombre debe tener entre 2 y 100 caracteres.';
        } elseif (!$email) {
            $error = 'Correo electrónico inválido.';
        } elseif (empty($pass) || strlen($pass) < 4 || strlen($pass) > 100) {
            $error = 'La contraseña debe tener entre 4 y 100 caracteres.';
        } elseif ($codigo && (strlen($codigo) < 3 || strlen($codigo) > 20)) {
            $error = 'El código estudiantil debe tener entre 3 y 20 caracteres.';
        } else {
            // Comprobar correo duplicado
            $stmtCheck = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE correo = :e LIMIT 1');
            $stmtCheck->execute(['e' => $email]);
            if ($stmtCheck->fetchColumn()) {
                $error = 'El correo ya está registrado.';
            } else {
                // Insertar usuario como estudiante (contraseña en texto plano por requisito)
                $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, password, rol) VALUES (:n, :e, :c, :r)');
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
}
?>

<div class="login-wrapper">
	<div class="login-card">
		<div class="login-header">
			<h2>Registro</h2>
			<p>Crear una nueva cuenta de estudiante</p>
		</div>

		<form method="post">
			<?php echo csrf_field(); ?>
			<input type="hidden" name="role" value="estudiante">
			<?php if(!empty($error)): ?>
			<div class="error-box">
				<?php echo htmlspecialchars($error); ?>
			</div>
			<?php endif; ?>

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

			<div style="margin-top:12px">
				<button class="btn btn-primary" type="submit" style="width:100%">Crear cuenta</button>
			</div>
		</form>

		<div style="margin-top:14px;text-align:center">
			<p style="color:var(--muted)">¿Ya tienes cuenta? <a href="login.php" class="link-muted">Inicia sesión</a></p>
		</div>
	</div>
</div>

<?php include_once 'footer.php'; ?>

