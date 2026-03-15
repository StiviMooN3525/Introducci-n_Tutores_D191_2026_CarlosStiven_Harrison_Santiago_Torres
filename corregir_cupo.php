<?php
require_once 'conexion.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $disponibilidad_id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    $cupo_correcto = filter_var($_POST['cupo'] ?? 0, FILTER_VALIDATE_INT);
    
    if (!$disponibilidad_id || $disponibilidad_id <= 0) {
        throw new Exception('ID de disponibilidad inválido');
    }
    
    // Verificar que la disponibilidad existe
    $stmt = $pdo->prepare('SELECT id_disponibilidad FROM disponibilidad WHERE id_disponibilidad = :id LIMIT 1');
    $stmt->execute(['id' => $disponibilidad_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Disponibilidad no encontrada');
    }
    
    // Actualizar el cupo
    $stmt = $pdo->prepare('UPDATE disponibilidad SET cupo_actual = :cupo WHERE id_disponibilidad = :id');
    $stmt->execute(['cupo' => $cupo_correcto, 'id' => $disponibilidad_id]);
    
    echo json_encode(['success' => true, 'message' => 'Cupo actualizado correctamente']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
