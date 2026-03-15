<?php
// Script de login con depuración completa
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>🔍 Login Debug - Paso a Paso</h3>";

// Iniciar sesión al principio
echo "<h4>🍪 1. Iniciando sesión...</h4>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<p style='color: green;'>✅ Sesión iniciada</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Sesión ya estaba activa</p>";
}

// Procesar formulario si hay POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h4>📤 2. Datos POST recibidos:</h4>";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    print_r($_POST);
    echo "</pre>";
    
    // Validar datos
    $correo = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    echo "<h4>🔐 3. Validando credenciales:</h4>";
    echo "<p>Correo: '" . htmlspecialchars($correo) . "'</p>";
    echo "<p>Password: '" . str_repeat('*', strlen($password)) . "'</p>";
    
    if (empty($correo) || empty($password)) {
        echo "<p style='color: red;'>❌ Correo o contraseña vacíos</p>";
        exit;
    }
    
    // Conectar a BD
    echo "<h4>🗄️ 4. Conectando a base de datos...</h4>";
    try {
        include 'conexion.php';
        echo "<p style='color: green;'>✅ Conexión a BD exitosa</p>";
        
        // Consultar usuario
        echo "<h4>👤 5. Buscando usuario...</h4>";
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   CASE 
                       WHEN u.rol = 'administrador' THEN 'dashboard_admin.php'
                       WHEN u.rol = 'profesor' THEN 'dashboard_profesor.php' 
                       ELSE 'dashboard_estudiante.php'
                   END as redirect_page
            FROM usuarios u 
            WHERE u.correo = ? AND u.rol != 'inactivo'
        ");
        $stmt->execute([$correo]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p style='color: green;'>✅ Usuario encontrado en BD</p>";
            echo "<pre style='background: #f0f0f0; padding: 10px;'>";
            print_r($user);
            echo "</pre>";
            
            // Verificar contraseña
            echo "<h4>🔑 6. Verificando contraseña...</h4>";
            if (password_verify($password, $user['password'])) {
                echo "<p style='color: green;'>✅ Contraseña correcta</p>";
                
                // Crear variables de sesión
                echo "<h4>🍪 7. Creando sesión...</h4>";
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_email'] = $user['correo'];
                $_SESSION['user_name'] = $user['nombre'];
                $_SESSION['role'] = $user['rol'];
                
                echo "<p style='color: green;'>✅ Variables de sesión creadas</p>";
                echo "<pre style='background: #f0f0f0; padding: 10px;'>";
                print_r($_SESSION);
                echo "</pre>";
                
                // Determinar página de redirección
                $redirect_page = $user['redirect_page'];
                echo "<h4>🎯 8. Redirección programada:</h4>";
                echo "<p style='color: blue; font-weight: bold;'>Página destino: $redirect_page</p>";
                
                // Intentar redirección
                echo "<h4>🔄 9. Ejecutando redirección...</h4>";
                echo "<p>Enviando header: Location: $redirect_page</p>";
                
                // Limpiar buffer de salida
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Ejecutar redirección
                header("Location: $redirect_page");
                echo "<p style='color: orange;'>⚠️ Header enviado. Si ves esto, la redirección falló.</p>";
                exit;
                
            } else {
                echo "<p style='color: red;'>❌ Contraseña incorrecta</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Usuario no encontrado</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en consulta: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<h4>📋 Esperando datos POST...</h4>";
    echo "<p>Por favor, envía el formulario de login.</p>";
}

// Mostrar estado final de la sesión
echo "<h4>🍪 Estado Final de la Sesión:</h4>";
echo "<pre style='background: #f0f0f0; padding: 10px;'>";
print_r($_SESSION);
echo "</pre>";

// Mostrar headers enviados
if (!headers_sent()) {
    echo "<h4>📤 Headers ya enviados:</h4>";
    echo "<pre>";
    print_r(headers_list());
    echo "</pre>";
} else {
    echo "<h4>⚠️ Headers ya enviados (no se puede redirigir)</h4>";
}
?>

<hr>
<h3>🔄 Formulario de Login (para pruebas)</h3>
<form method="post" action="" style="max-width: 400px; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
    <div style="margin-bottom: 15px;">
        <label for="correo">Correo:</label><br>
        <input type="email" id="correo" name="correo" required style="width: 100%; padding: 8px; margin-top: 5px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="password">Contraseña:</label><br>
        <input type="password" id="password" name="password" required style="width: 100%; padding: 8px; margin-top: 5px;">
    </div>
    <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        Iniciar Sesión (Debug)
    </button>
</form>
