<?php
// Script de depuración para registro
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>🔍 Depuración del Registro</h3>";

// 1. Verificar conexión a BD
echo "<h4>1. Probando conexión a BD...</h4>";
try {
    include 'conexion.php';
    echo "<p style='color: green;'>✅ Conexión a BD exitosa</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 2. Verificar archivos necesarios
echo "<h4>2. Verificando archivos necesarios...</h4>";
$archivos_necesarios = ['header.php', 'footer.php', 'csrf_helper.php'];
foreach ($archivos_necesarios as $archivo) {
    if (file_exists($archivo)) {
        echo "<p style='color: green;'>✅ $archivo encontrado</p>";
    } else {
        echo "<p style='color: red;'>❌ $archivo NO encontrado</p>";
    }
}

// 3. Verificar tabla usuarios
echo "<h4>3. Verificando tabla usuarios...</h4>";
try {
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columnas = $stmt->fetchAll();
    echo "<p style='color: green;'>✅ Tabla usuarios encontrada con " . count($columnas) . " columnas</p>";
    
    // Mostrar columnas importantes
    $columnas_necesarias = ['id_usuario', 'nombre', 'correo', 'password', 'rol', 'estado'];
    foreach ($columnas_necesarias as $col) {
        $existe = false;
        foreach ($columnas as $c) {
            if ($c['Field'] === $col) {
                $existe = true;
                break;
            }
        }
        echo "<p>" . ($existe ? "✅" : "❌") . " Columna '$col': " . ($existe ? "existe" : "NO existe") . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al verificar tabla usuarios: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 4. Verificar método POST
echo "<h4>4. Verificando método POST...</h4>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p style='color: green;'>✅ Método POST detectado</p>";
    echo "<h5>Datos POST recibidos:</h5>";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    print_r($_POST);
    echo "</pre>";
    
    // Procesar registro manualmente
    if (isset($_POST['nombre']) && isset($_POST['correo']) && isset($_POST['password'])) {
        echo "<h5>Intentando registrar usuario...</h5>";
        
        try {
            $nombre = trim($_POST['nombre']);
            $correo = trim($_POST['correo']);
            $password = trim($_POST['password']);
            $rol = $_POST['rol'] ?? 'estudiante';
            
            echo "<p>Nombre: " . htmlspecialchars($nombre) . "</p>";
            echo "<p>Correo: " . htmlspecialchars($correo) . "</p>";
            echo "<p>Contraseña: " . str_repeat('*', strlen($password)) . "</p>";
            echo "<p>Rol: " . htmlspecialchars($rol) . "</p>";
            
            // Verificar si el correo ya existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo]);
            $existe = $stmt->fetchColumn();
            
            if ($existe > 0) {
                echo "<p style='color: orange;'>⚠️ El correo ya está registrado</p>";
            } else {
                // Insertar usuario
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password, rol, estado) VALUES (?, ?, ?, ?, 'activo')");
                $result = $stmt->execute([$nombre, $correo, $password, $rol]);
                
                if ($result) {
                    echo "<p style='color: green; font-weight: bold;'>✅ ¡Usuario registrado exitosamente!</p>";
                    echo "<p>ID del nuevo usuario: " . $pdo->lastInsertId() . "</p>";
                } else {
                    echo "<p style='color: red;'>❌ Error al insertar usuario</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error al registrar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Faltan datos del formulario</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Esperando método POST</p>";
}

// 5. Información del servidor
echo "<h4>5. Información del servidor:</h4>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server: " . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p>Document Root: " . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p>Request URI: " . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Unknown') . "</p>";
?>

<hr>
<h3>📋 Formulario de Registro (Prueba)</h3>
<form method="post" style="max-width: 400px; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
    <div style="margin-bottom: 15px;">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" required style="width: 100%; padding: 8px; margin-top: 5px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label>Correo:</label><br>
        <input type="email" name="correo" required style="width: 100%; padding: 8px; margin-top: 5px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label>Contraseña:</label><br>
        <input type="password" name="password" required style="width: 100%; padding: 8px; margin-top: 5px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label>Rol:</label><br>
        <select name="rol" style="width: 100%; padding: 8px; margin-top: 5px;">
            <option value="estudiante">Estudiante</option>
            <option value="profesor">Profesor</option>
        </select>
    </div>
    <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        Registrarse (Debug)
    </button>
</form>
