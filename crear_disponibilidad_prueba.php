<?php
require_once 'conexion.php';

echo "=== CREANDO DISPONIBILIDAD DE PRUEBA ===\n\n";

// Obtener profesores activos
$stmt = $pdo->query('SELECT p.id_profesor, u.nombre 
                     FROM profesores p 
                     JOIN usuarios u ON p.id_usuario = u.id_usuario 
                     WHERE u.estado = "activo"');
$profesores = $stmt->fetchAll();

if (empty($profesores)) {
    echo "❌ No hay profesores activos registrados\n";
    exit;
}

// Fechas para los próximos 7 días
$fechas = [];
for ($i = 1; $i <= 7; $i++) {
    $fechas[] = date('Y-m-d', strtotime("+$i days"));
}

// Horarios disponibles
$horarios = [
    ['inicio' => '09:00:00', 'fin' => '11:00:00'],
    ['inicio' => '11:00:00', 'fin' => '13:00:00'],
    ['inicio' => '14:00:00', 'fin' => '16:00:00'],
    ['inicio' => '16:00:00', 'fin' => '18:00:00']
];

$count_creadas = 0;
$count_existentes = 0;

foreach ($profesores as $profesor) {
    foreach ($fechas as $fecha) {
        foreach ($horarios as $horario) {
            // Verificar si ya existe
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM disponibilidad 
                                 WHERE id_profesor = :prof AND fecha = :fecha AND hora_inicio = :inicio');
            $stmt->execute([
                'prof' => $profesor['id_profesor'],
                'fecha' => $fecha,
                'inicio' => $horario['inicio']
            ]);
            
            if ($stmt->fetchColumn() == 0) {
                // Insertar nueva disponibilidad
                $stmt = $pdo->prepare('INSERT INTO disponibilidad 
                                     (id_profesor, fecha, hora_inicio, hora_fin, estado) 
                                     VALUES (:prof, :fecha, :inicio, :fin, "disponible")');
                $stmt->execute([
                    'prof' => $profesor['id_profesor'],
                    'fecha' => $fecha,
                    'inicio' => $horario['inicio'],
                    'fin' => $horario['fin']
                ]);
                $count_creadas++;
                echo "✅ Creada: Profesor " . $profesor['nombre'] . " | " . $fecha . " | " . 
                     substr($horario['inicio'], 0, 5) . "-" . substr($horario['fin'], 0, 5) . "\n";
            } else {
                $count_existentes++;
            }
        }
    }
}

echo "\n=== RESUMEN ===\n";
echo "Nuevas disponibilidades creadas: " . $count_creadas . "\n";
echo "Disponibilidades ya existentes: " . $count_existentes . "\n";
echo "Total de profesores procesados: " . count($profesores) . "\n";

echo "\n=== DISPONIBILIDAD ACTUALIZADA ===\n";
$stmt = $pdo->query('SELECT COUNT(*) as total FROM disponibilidad WHERE estado = "disponible" AND fecha >= CURDATE()');
$result = $stmt->fetch();
echo "Total de disponibilidad disponible: " . $result['total'] . "\n";

echo "\n✅ ¡Listo! Ahora puedes intentar hacer reservas en el dashboard de estudiante.\n";
?>
