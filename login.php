<?php
include_once 'header.php';
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';

    // Nuevo esquema: el rol está en la tabla usuarios como campo ENUM `rol`
    $stmt = $pdo->prepare('SELECT id_usuario AS id, nombre, contrasena, rol FROM usuarios WHERE correo = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // Validación de contraseña en texto plano (según requisito del proyecto)
    if ($user && $pass === $user['contrasena']) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['role'] = $user['rol'];

        // Redirigir según rol
        if ($user['rol'] === 'estudiante') header('Location: dashboard_estudiante.php');
        elseif ($user['rol'] === 'profesor') header('Location: dashboard_profesor.php');
        else header('Location: dashboard_admin.php');
        exit;
    } else {
        $error = 'Credenciales inválidas';
    }
}
?>

<style>
    body { background: #0a0a0a; margin: 0; font-family: 'Inter', sans-serif; }
    .bg-gradient-radial { background: #0a0a0a; }
    .backdrop-blur-glass { backdrop-filter: blur(24px); }
    .shadow-deep { box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.9), 0 0 0 1px rgba(255, 255, 255, 0.08); }
    .glow-neon { box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.6), 0 0 24px rgba(34, 211, 238, 0.4); }
    .btn-glow { box-shadow: 0 0 24px rgba(34, 211, 238, 0.5); }
    .btn-glow:hover { box-shadow: 0 0 36px rgba(34, 211, 238, 0.7), 0 0 48px rgba(34, 211, 238, 0.5); }
    .animate-fade-in { animation: fadeIn 1s ease-out; }
    .animate-slide-up { animation: slideUp 0.8s ease-out 0.3s both; }
    .animate-slide-up-delay { animation: slideUp 0.8s ease-out 0.5s both; }
    .animate-slide-up-more { animation: slideUp 0.8s ease-out 0.7s both; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    .role-btn.active { transform: scale(1.05); }
</style>

<div class="min-h-screen bg-gradient-radial flex items-center justify-center p-6 text-white">
    <div class="w-full max-w-md animate-fade-in">
        <div class="backdrop-blur-glass bg-gray-900/30 border border-gray-700/50 rounded-3xl p-10 shadow-deep">
            <div class="text-center mb-10 animate-slide-up">
                <h1 class="text-4xl font-bold mb-3 tracking-tight text-white">Sistema de Tutorías</h1>
                <p class="text-gray-400 text-base">Ingresa tus credenciales para continuar</p>
            </div>

            <form method="post" class="space-y-8 animate-slide-up-delay">
                <input type="hidden" name="role" id="selectedRole" value="estudiante">
                <?php if(!empty($error)): ?>
                <div class="p-4 bg-red-900/60 border border-red-500/60 rounded-2xl text-red-200 text-sm font-medium animate-slide-up">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <div class="animate-slide-up-more">
                    <label class="block text-sm font-semibold text-gray-300 mb-4 uppercase tracking-wide">Selecciona tu rol</label>
                    <div class="grid grid-cols-3 gap-3">
                        <button type="button" class="role-btn active p-4 rounded-2xl border-2 border-cyan-400 bg-cyan-400/25 text-cyan-200 transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-cyan-400/30" data-role="estudiante">
                            <div class="text-3xl mb-2">👨‍🎓</div>
                            <div class="text-sm font-semibold">Estudiante</div>
                        </button>
                        <button type="button" class="role-btn p-4 rounded-2xl border-2 border-gray-600 bg-gray-800/60 text-gray-400 hover:border-gray-500 hover:bg-gray-700/60 transition-all duration-300 hover:scale-105" data-role="profesor">
                            <div class="text-3xl mb-2">👨‍🏫</div>
                            <div class="text-sm font-semibold">Profesor</div>
                        </button>
                        <button type="button" class="role-btn p-4 rounded-2xl border-2 border-gray-600 bg-gray-800/60 text-gray-400 hover:border-gray-500 hover:bg-gray-700/60 transition-all duration-300 hover:scale-105" data-role="administrador">
                            <div class="text-3xl mb-2">👔</div>
                            <div class="text-sm font-semibold">Admin</div>
                        </button>
                    </div>
                </div>

                <div class="space-y-6 animate-slide-up-more">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-3">Correo electrónico</label>
                        <input type="email" name="email" class="w-full px-5 py-4 bg-gray-800/60 border-2 border-gray-600 rounded-2xl text-white placeholder-gray-500 focus:outline-none focus:border-cyan-400 focus:bg-gray-700/60 transition-all duration-300 text-base" placeholder="tu@email.com" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-3">Contraseña</label>
                        <input type="password" name="password" class="w-full px-5 py-4 bg-gray-800/60 border-2 border-gray-600 rounded-2xl text-white placeholder-gray-500 focus:outline-none focus:border-cyan-400 focus:bg-gray-700/60 transition-all duration-300 text-base" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-bold rounded-2xl btn-glow transition-all duration-300 transform hover:scale-105 text-lg">
                    Iniciar Sesión
                </button>
            </form>

            <div class="mt-8 text-center animate-slide-up-more">
                <p class="text-gray-500 text-sm">
                    ¿No tienes cuenta? <a href="registro.php" class="text-cyan-400 hover:text-cyan-300 transition-colors duration-300 font-medium">Regístrate aquí</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        const roleButtons = document.querySelectorAll('.role-btn');
        const selectedRoleInput = document.getElementById('selectedRole');
        roleButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                roleButtons.forEach(b => {
                    b.classList.remove('active', 'border-cyan-400', 'bg-cyan-400/25', 'text-cyan-200', 'shadow-lg', 'shadow-cyan-400/30');
                    b.classList.add('border-gray-600', 'bg-gray-800/60', 'text-gray-400');
                });
                btn.classList.add('active', 'border-cyan-400', 'bg-cyan-400/25', 'text-cyan-200', 'shadow-lg', 'shadow-cyan-400/30');
                btn.classList.remove('border-gray-600', 'bg-gray-800/60', 'text-gray-400');
                selectedRoleInput.value = btn.getAttribute('data-role');
            });
        });
    </script>
</div>

<?php include_once 'footer.php'; ?>
