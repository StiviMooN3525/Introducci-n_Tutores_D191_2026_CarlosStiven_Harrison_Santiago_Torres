<?php
// Script para depurar el proceso de login
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h3>🔍 Depuración del Login</h3>";

// Verificar si hay datos POST
if ($_POST) {
    echo "<h4>📤 Datos POST recibidos:</h4>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Verificar credenciales
    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h4>🔐 Credenciales:</h4>";
    echo "<p>Correo: " . htmlspecialchars($correo) . "</p>";
    echo "<p>Password: " . str_repeat('*', strlen($password)) . "</p>";
    
    // Intentar conexión y consulta
    try {
        include 'conexion.php';
        echo "<p style='color: green;'>✅ Conexión a BD exitosa</p>";
        
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ? AND rol != 'inactivo'");
        $stmt->execute([$correo]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            echo "<p style='color: green;'>✅ Usuario encontrado y contraseña correcta</p>";
            echo "<pre>";
            print_r($user);
            echo "</pre>";
            
            // Crear sesión
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['user_email'] = $user['correo'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['role'] = $user['rol'];
            
            echo "<p style='color: blue;'>🔄 Sesión creada</p>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
            
            // Determinar redirección
            $redirect = 'dashboard_estudiante.php';
            if ($user['rol'] === 'administrador') {
                $redirect = 'dashboard_admin.php';
            } elseif ($user['rol'] === 'profesor') {
                $redirect = 'dashboard_profesor.php';
            }
            
            echo "<h4>🎯 Redirección programada a:</h4>";
            echo "<p><strong>$redirect</strong></p>";
            
            // Intentar redirección
            echo "<h4>🔄 Intentando redirección...</h4>";
            header("Location: $redirect");
            exit;
            
        } else {
            echo "<p style='color: red;'>❌ Usuario no encontrado o contraseña incorrecta</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>No hay datos POST. Por favor haz login desde el formulario normal.</p>";
}

// Mostrar información de sesión actual
echo "<h4>🍪 Sesión Actual:</h4>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Mostrar información del servidor
echo "<h4>🌐 Información del Servidor:</h4>";
echo "<p>Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'No disponible') . "</p>";
echo "<p>HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'No disponible') . "</p>";
echo "<p>Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'No disponible') . "</p>";
echo "<p>HTTPS: " . (isset($_SERVER['HTTPS']) ? 'Sí' : 'No') . "</p>";

?>
