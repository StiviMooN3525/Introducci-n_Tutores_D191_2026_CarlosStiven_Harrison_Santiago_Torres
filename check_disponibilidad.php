<?php
require_once 'conexion.php';

echo "=== VERIFICANDO DISPONIBILIDAD ===\n\n";

// Verificar disponibilidad existente
$stmt = $pdo->query('SELECT d.*, u.nombre as profesor_nombre 
                     FROM disponibilidad d 
                     LEFT JOIN profesores p ON d.id_profesor = p.id_profesor 
                     LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario 
                     ORDER BY d.fecha, d.hora_inicio');

echo "DISPONIBILIDAD ACTUAL:\n";
echo "ID\tProfesor\t\tFecha\t\tHora Inicio\tHora Fin\tEstado\n";
echo "----------------------------------------------------------------\n";

$count = 0;
while ($row = $stmt->fetch()) {
    $count++;
    echo $row['id_disponibilidad'] . "\t" . 
         substr($row['profesor_nombre'] ?: 'Profesor ' . $row['id_profesor'], 0, 15) . "\t\t" . 
         $row['fecha'] . "\t" . 
         substr($row['hora_inicio'], 0, 5) . "\t\t" . 
         substr($row['hora_fin'], 0, 5) . "\t" . 
         $row['estado'] . "\n";
}

if ($count === 0) {
    echo "⚠️  NO HAY REGISTROS DE DISPONIBILIDAD\n\n";
}

echo "\n=== DISPONIBILIDAD FUTURA (HOY EN ADELANTE) ===\n";
$stmt = $pdo->query('SELECT d.*, u.nombre as profesor_nombre 
                     FROM disponibilidad d 
                     LEFT JOIN profesores p ON d.id_profesor = p.id_profesor 
                     LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario 
                     WHERE d.fecha >= CURDATE() AND d.estado = "disponible"
                     ORDER BY d.fecha, d.hora_inicio');

echo "ID\tProfesor\t\tFecha\t\tHora Inicio\tHora Fin\tEstado\n";
echo "----------------------------------------------------------------\n";

$count_futura = 0;
while ($row = $stmt->fetch()) {
    $count_futura++;
    echo $row['id_disponibilidad'] . "\t" . 
         substr($row['profesor_nombre'] ?: 'Profesor ' . $row['id_profesor'], 0, 15) . "\t\t" . 
         $row['fecha'] . "\t" . 
         substr($row['hora_inicio'], 0, 5) . "\t\t" . 
         substr($row['hora_fin'], 0, 5) . "\t" . 
         $row['estado'] . "\n";
}

if ($count_futura === 0) {
    echo "⚠️  NO HAY DISPONIBILIDAD FUTURA DISPONIBLE\n\n";
}

echo "\n=== PROFESORES REGISTRADOS ===\n";
$stmt = $pdo->query('SELECT p.id_profesor, u.nombre, u.email 
                     FROM profesores p 
                     JOIN usuarios u ON p.id_usuario = u.id_usuario 
                     WHERE u.estado = "activo"');

echo "ID\tNombre\t\t\tCorreo\n";
echo "----------------------------------------\n";
while ($row = $stmt->fetch()) {
    echo $row['id_profesor'] . "\t" . 
         substr($row['nombre'], 0, 20) . "\t\t" . 
         $row['email'] . "\n";
}

echo "\n=== RECOMENDACIONES ===\n";
if ($count === 0) {
    echo "❌ Debes crear registros de disponibilidad para los profesores\n";
    echo "   Ejemplo SQL: INSERT INTO disponibilidad (id_profesor, fecha, hora_inicio, hora_fin, estado) \n";
    echo "               VALUES (1, '2026-03-15', '09:00:00', '11:00:00', 'disponible');\n\n";
} elseif ($count_futura === 0) {
    echo "❌ No hay disponibilidad futura disponible\n";
    echo "   Debes agregar disponibilidad para fechas futuras o cambiar el estado a 'disponible'\n\n";
} else {
    echo "✅ Hay disponibilidad disponible para reservar\n";
    echo "   Si sigues recibiendo el error, verifica:\n";
    echo "   - Que estés seleccionando correctamente profesor, fecha y hora\n";
    echo "   - Que la hora seleccionada esté dentro del rango hora_inicio - hora_fin\n\n";
}

echo "=== FECHA Y HORA ACTUAL DEL SERVIDOR ===\n";
echo "Fecha: " . date('Y-m-d') . "\n";
echo "Hora: " . date('H:i:s') . "\n";
?>
