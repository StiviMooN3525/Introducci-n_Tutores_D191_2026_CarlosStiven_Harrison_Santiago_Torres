<?php
require_once 'conexion.php';

header('Content-Type: application/json');

try {
    // Obtener todas las disponibilidades y calcular cupos correctos
    $stmt = $pdo->query('
        SELECT 
            d.id_disponibilidad,
            d.cupo_actual as cupo_actual_incorrecto,
            COUNT(CASE WHEN ti.estado = "inscrito" THEN 1 END) as cupo_correcto
        FROM disponibilidad d
        LEFT JOIN tutoria_inscripciones ti ON d.id_disponibilidad = ti.id_disponibilidad
        GROUP BY d.id_disponibilidad
        HAVING d.cupo_actual != COUNT(CASE WHEN ti.estado = "inscrito" THEN 1 END)
    ');
    
    $disponibilidades_corregir = $stmt->fetchAll();
    $corregidos = 0;
    
    foreach ($disponibilidades_corregir as $disp) {
        // Actualizar cada disponibilidad con el cupo correcto
        $stmtUpdate = $pdo->prepare('UPDATE disponibilidad SET cupo_actual = :cupo WHERE id_disponibilidad = :id');
        $stmtUpdate->execute([
            'cupo' => $disp['cupo_correcto'],
            'id' => $disp['id_disponibilidad']
        ]);
        $corregidos++;
    }
    
    echo json_encode([
        'success' => true, 
        'corregidos' => $corregidos,
        'message' => "Se corrigieron $corregidos cupos incorrectamente"
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
