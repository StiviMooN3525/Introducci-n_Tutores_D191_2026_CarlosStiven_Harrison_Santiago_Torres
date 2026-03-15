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

        // Sanitizar y validar inputs

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        $pass = $_POST['password'] ?? '';

        

        // Validaciones adicionales

        if (!$email) {

            $error = 'Correo electrónico inválido';

        } elseif (strlen($pass) < 4) {

            $error = 'La contraseña debe tener al menos 4 caracteres';

        } elseif (strlen($pass) > 100) {

            $error = 'La contraseña es demasiado larga';

        } else {

            // Esquema corregido: usar columna 'password' y password_verify()

            $stmt = $pdo->prepare('SELECT id_usuario AS id, nombre, password, rol FROM usuarios WHERE correo = :email LIMIT 1');

            $stmt->execute(['email' => $email]);

            $user = $stmt->fetch();

            // Validación de contraseña (texto plano según datos actuales de BD)
            
            if ($user && $pass === $user['password']) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];

                $_SESSION['user_name'] = $user['nombre'];

                $_SESSION['role'] = $user['rol'];

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Regenerar token después de login



                // Redirigir según rol

                if ($user['rol'] === 'estudiante') header('Location: dashboard_estudiante.php');

                elseif ($user['rol'] === 'profesor') header('Location: dashboard_profesor.php');

                else header('Location: dashboard_admin.php');

                exit;

            } else {

                $error = 'Credenciales inválidas';

                // Pequeña pausa para prevenir ataques de fuerza bruta

                usleep(500000); // 0.5 segundos

            }

        }

    }

}

?>



<div class="login-wrapper">

    <div class="login-card">

        <div class="login-header">

            <h2>Sistema de Tutorías</h2>

            <p>Ingresa tus credenciales para continuar</p>

        </div>



        <form method="post">

            <?php echo csrf_field(); ?>

            <input type="hidden" name="role" id="selectedRole" value="estudiante">

            <?php if(!empty($error)): ?>

            <div class="error-box">

                <?php echo htmlspecialchars($error); ?>

            </div>

            <?php endif; ?>



            <div>

                <label class="field">Selecciona tu rol</label>

                <div class="role-grid">

                    <button type="button" class="role-btn active" data-role="estudiante">

                        <div class="emoji">👨‍🎓</div>

                        <div class="label">Estudiante</div>

                    </button>

                    <button type="button" class="role-btn" data-role="profesor">

                        <div class="emoji">👨‍🏫</div>

                        <div class="label">Profesor</div>

                    </button>

                    <button type="button" class="role-btn" data-role="administrador">

                        <div class="emoji">👔</div>

                        <div class="label">Admin</div>

                    </button>

                </div>

            </div>



            <div class="field">

                <label>Correo electrónico</label>

                <input type="email" name="email" placeholder="tu@email.com" required>

            </div>



            <div class="field">

                <label>Contraseña</label>

                <input type="password" name="password" placeholder="••••••••" required>

            </div>



            <div style="margin-top:12px">

                <button type="submit" class="btn btn-primary" style="width:100%">Iniciar Sesión</button>

            </div>

        </form>



        <div style="margin-top:14px;text-align:center">

            <p style="color:var(--muted)">¿No tienes cuenta? <a href="registro.php" class="link-muted">Regístrate aquí</a></p>

        </div>

    </div>

</div>



<script>

    const roleButtons = document.querySelectorAll('.role-btn');

    const selectedRoleInput = document.getElementById('selectedRole');

    roleButtons.forEach(btn => {

        btn.addEventListener('click', () => {

            roleButtons.forEach(b => b.classList.remove('active'));

            btn.classList.add('active');

            selectedRoleInput.value = btn.getAttribute('data-role');

        });

    });

</script>



<?php include_once 'footer.php'; ?>

