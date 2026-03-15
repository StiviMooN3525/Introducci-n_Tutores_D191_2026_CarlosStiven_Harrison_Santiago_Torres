<?php
require_once 'conexion.php';

echo "=== DIAGNÓSTICO DE DISPONIBILIDAD DE PROFESOR ===\n\n";

// Obtener información del profesor actual
session_start();
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

echo "Usuario actual: ID = $user_id, Rol = $role\n\n";

if ($role === 'profesor') {
    // Obtener ID del profesor
    $stmt = $pdo->prepare('SELECT id_profesor, nombre FROM profesores p JOIN usuarios u ON p.id_usuario = u.id_usuario WHERE p.id_usuario = :u');
    $stmt->execute(['u' => $user_id]);
    $profesor = $stmt->fetch();
    
    if ($profesor) {
        echo "Profesor encontrado: ID = {$profesor['id_profesor']}, Nombre = {$profesor['nombre']}\n\n";
        
        // Verificar todas las disponibilidades de este profesor
        $stmt = $pdo->prepare('SELECT * FROM disponibilidad WHERE id_profesor = :prof ORDER BY fecha DESC');
        $stmt->execute(['prof' => $profesor['id_profesor']]);
        $disponibilidades = $stmt->fetchAll();
        
        echo "Total de disponibilidades registradas: " . count($disponibilidades) . "\n\n";
        
        if (count($disponibilidades) > 0) {
            echo "DETALLE DE DISPONIBILIDADES:\n";
            echo "ID\tFecha\t\tInicio\tFin\t\tEstado\tCupo Max\tCupo Actual\n";
            echo "----------------------------------------------------------------\n";
            
            foreach ($disponibilidades as $disp) {
                echo $disp['id_disponibilidad'] . "\t" . 
                     $disp['fecha'] . "\t" . 
                     substr($disp['hora_inicio'], 0, 5) . "\t" . 
                     substr($disp['hora_fin'], 0, 5) . "\t" . 
                     $disp['estado'] . "\t" . 
                     ($disp['cupo_maximo'] ?? 'N/A') . "\t" . 
                     ($disp['cupo_actual'] ?? 'N/A') . "\n";
            }
        } else {
            echo "❌ No hay disponibilidades registradas para este profesor.\n";
            
            // Verificar si las columnas de cupos existen
            echo "\nVERIFICANDO ESTRUCTURA DE TABLA:\n";
            $stmt = $pdo->query('DESCRIBE disponibilidad');
            $columns = $stmt->fetchAll();
            
            $has_cupo_maximo = false;
            $has_cupo_actual = false;
            
            foreach ($columns as $col) {
                if ($col['Field'] === 'cupo_maximo') $has_cupo_maximo = true;
                if ($col['Field'] === 'cupo_actual') $has_cupo_actual = true;
            }
            
            echo "Columna cupo_maximo: " . ($has_cupo_maximo ? "✅ Existe" : "❌ No existe") . "\n";
            echo "Columna cupo_actual: " . ($has_cupo_actual ? "✅ Existe" : "❌ No existe") . "\n";
            
            if (!$has_cupo_maximo || !$has_cupo_actual) {
                echo "\n⚠️  Las columnas de cupos no existen. Ejecuta la migración cambio_1.sql\n";
            }
        }
        
        // Verificar últimas inserciones en la tabla
        echo "\nÚLTIMAS INSERCIONES EN DISPONIBILIDAD:\n";
        $stmt = $pdo->query('SELECT id_disponibilidad, id_profesor, fecha, hora_inicio, estado FROM disponibilidad ORDER BY id_disponibilidad DESC LIMIT 5');
        $ultimas = $stmt->fetchAll();
        
        if (count($ultimas) > 0) {
            echo "ID\tProfesor\tFecha\t\tInicio\tEstado\n";
            echo "------------------------------------------------\n";
            foreach ($ultimas as $ult) {
                echo $ult['id_disponibilidad'] . "\t" . 
                     $ult['id_profesor'] . "\t" . 
                     $ult['fecha'] . "\t" . 
                     substr($ult['hora_inicio'], 0, 5) . "\t" . 
                     $ult['estado'] . "\n";
            }
        } else {
            echo "No hay registros en la tabla disponibilidad\n";
        }
        
    } else {
        echo "❌ No se encontró registro de profesor para este usuario.\n";
    }
} else {
    echo "❌ El usuario actual no es un profesor.\n";
}

echo "\n=== VERIFICACIÓN DE TABLAS ===\n";
$tables = ['disponibilidad', 'tutoria_inscripciones', 'profesores', 'estudiantes'];
foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    $exists = $stmt->rowCount() > 0;
    echo "Tabla $table: " . ($exists ? "✅ Existe" : "❌ No existe") . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
?>
