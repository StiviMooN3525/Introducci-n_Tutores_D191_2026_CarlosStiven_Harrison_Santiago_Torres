<?php
require_once 'conexion.php';

echo "=== DIAGNÓSTICO DE ESTADÍSTICAS ===\n\n";

// 1. Verificar datos básicos
echo "1. VERIFICACIÓN DE DATOS BÁSICOS:\n";
$stmt_total = $pdo->query('SELECT COUNT(*) FROM tutoria_inscripciones');
$total_inscripciones = $stmt_total->fetchColumn();
echo "   Total inscripciones: $total_inscripciones\n";

$stmt_disp = $pdo->query('SELECT COUNT(*) FROM disponibilidad WHERE estado = "disponible"');
$total_disponibilidades = $stmt_disp->fetchColumn();
echo "   Total disponibilidades activas: $total_disponibilidades\n\n";

// 2. Test de consulta de tutorías por estado
echo "2. TUTORÍAS POR ESTADO (consulta del dashboard):\n";
$stmt_tutorias_estado = $pdo->query('
  SELECT ti.estado, COUNT(*) as cantidad 
  FROM tutoria_inscripciones ti 
  GROUP BY ti.estado
');

echo "   Resultados:\n";
while ($estado = $stmt_tutorias_estado->fetch()) {
    echo "   - {$estado['estado']}: {$estado['cantidad']}\n";
}

if ($stmt_tutorias_estado->rowCount() === 0) {
    echo "   ❌ No hay resultados\n";
}
echo "\n";

// 3. Test de consulta de profesores activos
echo "3. PROFESORES MÁS ACTIVOS (consulta del dashboard):\n";
$stmt_profesores_activos = $pdo->query('
  SELECT u.nombre, COUNT(ti.id_inscripcion) as tutorias_count
  FROM usuarios u
  JOIN profesores p ON u.id_usuario = p.id_usuario
  JOIN disponibilidad d ON p.id_profesor = d.id_profesor
  JOIN tutoria_inscripciones ti ON d.id_disponibilidad = ti.id_disponibilidad
  WHERE ti.estado = "inscrito"
  GROUP BY u.id_usuario, u.nombre
  ORDER BY tutorias_count DESC
  LIMIT 5
');

echo "   Resultados:\n";
while ($prof = $stmt_profesores_activos->fetch()) {
    echo "   - {$prof['nombre']}: {$prof['tutorias_count']} tutorías\n";
}

if ($stmt_profesores_activos->rowCount() === 0) {
    echo "   ❌ No hay resultados\n";
}
echo "\n";

// 4. Test de consulta de materias populares
echo "4. MATERIAS MÁS POPULARES (consulta del dashboard):\n";
$stmt_materias_populares = $pdo->query('
  SELECT m.nombre, COUNT(ti.id_inscripcion) as inscripciones_count
  FROM materias m
  JOIN tutoria_inscripciones ti ON m.id_materia = ti.id_materia
  WHERE ti.estado = "inscrito"
  GROUP BY m.id_materia, m.nombre
  ORDER BY inscripciones_count DESC
  LIMIT 5
');

echo "   Resultados:\n";
while ($materia = $stmt_materias_populares->fetch()) {
    echo "   - {$materia['nombre']}: {$materia['inscripciones_count']} inscripciones\n";
}

if ($stmt_materias_populares->rowCount() === 0) {
    echo "   ❌ No hay resultados\n";
}
echo "\n";

// 5. Test de consulta de estudiantes activos
echo "5. ESTUDIANTES MÁS ACTIVOS (consulta del dashboard):\n";
$stmt_estudiantes_activos = $pdo->query('
  SELECT u.nombre, COUNT(ti.id_inscripcion) as tutorias_count
  FROM usuarios u
  JOIN estudiantes e ON u.id_usuario = e.id_usuario
  JOIN tutoria_inscripciones ti ON e.id_estudiante = ti.id_estudiante
  WHERE ti.estado = "inscrito"
  GROUP BY u.id_usuario, u.nombre
  ORDER BY tutorias_count DESC
  LIMIT 5
');

echo "   Resultados:\n";
while ($est = $stmt_estudiantes_activos->fetch()) {
    echo "   - {$est['nombre']}: {$est['tutorias_count']} tutorías\n";
}

if ($stmt_estudiantes_activos->rowCount() === 0) {
    echo "   ❌ No hay resultados\n";
}
echo "\n";

// 6. Verificar datos crudos de inscripciones
echo "6. DATOS CRUDOS DE INSCRIPCIONES:\n";
$stmt_raw = $pdo->query('
  SELECT ti.id_inscripcion, ti.estado, ti.id_disponibilidad, ti.id_estudiante, ti.id_profesor, ti.id_materia
  FROM tutoria_inscripciones ti 
  LIMIT 5
');

echo "   Muestra de datos:\n";
while ($row = $stmt_raw->fetch()) {
    echo "   - ID: {$row['id_inscripcion']}, Estado: {$row['estado']}, Disp: {$row['id_disponibilidad']}, Est: {$row['id_estudiante']}, Prof: {$row['id_profesor']}, Mat: {$row['id_materia']}\n";
}

if ($stmt_raw->rowCount() === 0) {
    echo "   ❌ No hay datos en la tabla\n";
}
echo "\n";

// 7. Verificar disponibilidades relacionadas
echo "7. DISPONIBILIDADES RELACIONADAS:\n";
$stmt_disp_rel = $pdo->query('
  SELECT d.id_disponibilidad, d.id_profesor, d.fecha, d.hora_inicio, d.cupo_maximo, d.cupo_actual
  FROM disponibilidad d
  WHERE d.id_disponibilidad IN (SELECT DISTINCT id_disponibilidad FROM tutoria_inscripciones)
  LIMIT 5
');

echo "   Disponibilidades con inscripciones:\n";
while ($row = $stmt_disp_rel->fetch()) {
    echo "   - ID: {$row['id_disponibilidad']}, Prof: {$row['id_profesor']}, Fecha: {$row['fecha']}, Hora: {$row['hora_inicio']}, Cupos: {$row['cupo_actual']}/{$row['cupo_maximo']}\n";
}

if ($stmt_disp_rel->rowCount() === 0) {
    echo "   ❌ No hay disponibilidades relacionadas\n";
}
echo "\n";

// 8. Diagnóstico final
echo "8. DIAGNÓSTICO FINAL:\n";
if ($total_inscripciones == 0) {
    echo "   ❌ PROBLEMA: No hay inscripciones en el sistema\n";
    echo "   💡 SOLUCIÓN: Ejecuta el script de creación de datos de prueba\n";
} elseif ($stmt_tutorias_estado->rowCount() == 0) {
    echo "   ❌ PROBLEMA: La consulta de estados no funciona\n";
    echo "   💡 SOLUCIÓN: Revisa la estructura de la tabla tutoria_inscripciones\n";
} elseif ($stmt_profesores_activos->rowCount() == 0) {
    echo "   ❌ PROBLEMA: La consulta de profesores no funciona\n";
    echo "   💡 SOLUCIÓN: Puede que no haya relaciones correctas entre tablas\n";
} else {
    echo "   ✅ Las consultas funcionan correctamente\n";
    echo "   💡 Si no ves datos en el dashboard, revisa el archivo dashboard_admin_v2.php\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
?>
