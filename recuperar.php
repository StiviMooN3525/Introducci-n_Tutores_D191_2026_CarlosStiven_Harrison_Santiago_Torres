<?php
include_once 'header.php';
require_once 'conexion.php';
require_once 'csrf_helper.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Solicitud inválida. Por favor intenta nuevamente.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $error = 'Correo electrónico inválido.';
        } else {
            // Buscar usuario
            $stmt = $pdo->prepare('SELECT id_usuario, nombre FROM usuarios WHERE correo = :c LIMIT 1');
            $stmt->execute(['c' => $email]);
            $user = $stmt->fetch();

            // Mostrar siempre mensaje genérico para no filtrar existencia
            $message = 'Si el correo existe en nuestro sistema, recibirás instrucciones para recuperar el acceso.';

            if ($user) {
                // Generar token de simulación (no persistimos en BD aquí)
                try {
                    $token = bin2hex(random_bytes(16));
                } catch (Exception $e) {
                    $token = substr(md5(uniqid((string)time(), true)), 0, 32);
                }
                // En un sistema real guardar token en BD y enviar email.
                $simLink = sprintf('%s://%s%s/reset_password.php?token=%s',
                    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http',
                    $_SERVER['HTTP_HOST'],
                    dirname($_SERVER['PHP_SELF']),
                    $token
                );
                // Mostrar un enlace simulado para pruebas en entorno local
                $message .= "<br><br><strong>Enlace simulado:</strong> <a href=\"$simLink\">$simLink</a>";
            }
        }
    }
}
?>

<section class="card">
  <h2>Recuperar acceso</h2>
  <p class="muted">Introduce el correo asociado a tu cuenta y sigue las instrucciones enviadas.</p>
  <?php if (!empty($error)) echo '<div class="alert error">'. htmlspecialchars($error) .'</div>'; ?>
  <?php if (!empty($message)) echo '<div class="alert success">'. $message .'</div>'; ?>
  <form method="post">
    <?php echo csrf_field(); ?>
    <div class="field"><label>Correo electrónico</label><input type="email" name="email" required></div>
    <div><button class="btn primary" type="submit">Enviar instrucciones</button></div>
  </form>
</section>

<?php include_once 'footer.php'; ?>
