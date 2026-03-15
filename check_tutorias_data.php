<?php
require_once 'conexion.php';

echo "=== DIAGNÓSTICO DE TUTORÍAS - DÓNDE ESTÁN LOS DATOS ===\n\n";

// 1. Verificar datos en tabla antigua tutorias
echo "1. DATOS EN TABLA ANTIGUA 'tutorias':\n";
$stmt_old = $pdo->query('
    SELECT t.id_tutoria, t.fecha, t.hora, t.estado, 
           ue.nombre as estudiante, up.nombre as profesor, m.nombre as materia
    FROM tutorias t
    JOIN estudiantes e ON t.id_estudiante = e.id_estudiante
    JOIN usuarios ue ON e.id_usuario = ue.id_usuario
    JOIN profesores p ON t.id_profesor = p.id_profesor
    JOIN usuarios up ON p.id_usuario = up.id_usuario
    LEFT JOIN materias m ON t.id_materia = m.id_materia
    ORDER BY t.fecha DESC
');

$old_count = $stmt_old->rowCount();
echo "Total de registros en 'tutorias': $old_count\n\n";

if ($old_count > 0) {
    echo "Detalles:\n";
    echo "ID\tFecha\t\tHora\tEstado\t\tEstudiante\t\tProfesor\t\tMateria\n";
    echo "--------------------------------------------------------------------------------\n";
    while ($row = $stmt_old->fetch()) {
        echo $row['id_tutoria'] . "\t" . 
             $row['fecha'] . "\t" . 
             substr($row['hora'], 0, 5) . "\t" . 
             $row['estado'] . "\t\t" . 
             substr($row['estudiante'], 0, 15) . "\t\t" . 
             substr($row['profesor'], 0, 15) . "\t\t" . 
             ($row['materia'] ?: 'Sin materia') . "\n";
    }
    echo "\n";
}

// 2. Verificar datos en tabla nueva tutoria_inscripciones
echo "2. DATOS EN TABLA NUEVA 'tutoria_inscripciones':\n";
$stmt_new = $pdo->query('
    SELECT ti.id_inscripcion, d.fecha, d.hora_inicio, ti.estado,
           ue.nombre as estudiante, up.nombre as profesor, m.nombre as materia
    FROM tutoria_inscripciones ti
    JOIN estudiantes e ON ti.id_estudiante = e.id_estudiante
    JOIN usuarios ue ON e.id_usuario = ue.id_usuario
    JOIN profesores p ON ti.id_profesor = p.id_profesor
    JOIN usuarios up ON p.id_usuario = up.id_usuario
    JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad
    LEFT JOIN materias m ON ti.id_materia = m.id_materia
    ORDER BY d.fecha DESC
');

$new_count = $stmt_new->rowCount();
echo "Total de registros en 'tutoria_inscripciones': $new_count\n\n";

if ($new_count > 0) {
    echo "Detalles:\n";
    echo "ID\tFecha\t\tHora\tEstado\t\tEstudiante\t\tProfesor\t\tMateria\n";
    echo "--------------------------------------------------------------------------------\n";
    while ($row = $stmt_new->fetch()) {
        echo $row['id_inscripcion'] . "\t" . 
             $row['fecha'] . "\t" . 
             substr($row['hora_inicio'], 0, 5) . "\t" . 
             $row['estado'] . "\t\t" . 
             substr($row['estudiante'], 0, 15) . "\t\t" . 
             substr($row['profesor'], 0, 15) . "\t\t" . 
             ($row['materia'] ?: 'Sin materia') . "\n";
    }
    echo "\n";
}

// 3. Verificar si hay disponibilidades
echo "3. DISPONIBILIDADES EN EL SISTEMA:\n";
$stmt_disp = $pdo->query('
    SELECT d.id_disponibilidad, d.fecha, d.hora_inicio, d.hora_fin, d.cupo_maximo, d.cupo_actual, d.estado,
           u.nombre as profesor
    FROM disponibilidad d
    JOIN profesores p ON d.id_profesor = p.id_profesor
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    ORDER BY d.fecha DESC
');

$disp_count = $stmt_disp->rowCount();
echo "Total de disponibilidades: $disp_count\n\n";

if ($disp_count > 0) {
    echo "Detalles:\n";
    echo "ID\tFecha\t\tHora\t\tCupos\tEstado\tProfesor\n";
    echo "----------------------------------------------------------------\n";
    while ($row = $stmt_disp->fetch()) {
        echo $row['id_disponibilidad'] . "\t" . 
             $row['fecha'] . "\t" . 
             substr($row['hora_inicio'], 0, 5) . "-" . substr($row['hora_fin'], 0, 5) . "\t" . 
             $row['cupo_actual'] . "/" . $row['cupo_maximo'] . "\t" . 
             $row['estado'] . "\t" . 
             substr($row['profesor'], 0, 20) . "\n";
    }
    echo "\n";
}

// 4. Verificar materias
echo "4. MATERIAS REGISTRADAS:\n";
$stmt_mat = $pdo->query('SELECT id_materia, nombre FROM materias ORDER BY nombre');
echo "Total de materias: " . $stmt_mat->rowCount() . "\n";
while ($row = $stmt_mat->fetch()) {
    echo "- " . $row['nombre'] . " (ID: " . $row['id_materia'] . ")\n";
}
echo "\n";

// 5. Diagnóstico del problema
echo "5. DIAGNÓSTICO DEL PROBLEMA:\n";
if ($old_count > 0 && $new_count == 0) {
    echo "⚠️  PROBLEMA IDENTIFICADO:\n";
    echo "   - Hay datos en la tabla antigua 'tutorias' ($old_count registros)\n";
    echo "   - NO hay datos en la tabla nueva 'tutoria_inscripciones' ($new_count registros)\n";
    echo "   - La migración no se ejecutó correctamente o falló\n\n";
    echo "   SOLUCIÓN:\n";
    echo "   1. Ejecutar o re-ejecutar el script de migración 'cambio_1.sql'\n";
    echo "   2. O ejecutar manualmente la migración de datos\n";
} elseif ($old_count > 0 && $new_count > 0) {
    echo "✅ AMBAS TABLAS TIENEN DATOS:\n";
    echo "   - Tabla antigua: $old_count registros\n";
    echo "   - Tabla nueva: $new_count registros\n";
    echo "   - Puede que haya datos duplicados o la migración fue parcial\n\n";
} elseif ($old_count == 0 && $new_count == 0) {
    echo "❌ NO HAY DATOS EN NINGUNA TABLA:\n";
    echo "   - No hay tutorías registradas en el sistema\n";
    echo "   - Debes crear disponibilidades y que los estudiantes se inscriban\n";
} else {
    echo "✅ DATOS SOLO EN TABLA NUEVA:\n";
    echo "   - La migración ya se realizó correctamente\n";
    echo "   - Los datos antiguos fueron eliminados o no existían\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
?>
