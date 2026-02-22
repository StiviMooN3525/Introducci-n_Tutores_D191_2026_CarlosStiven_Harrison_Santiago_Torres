<?php
require_once 'conexion.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrador') {
    header('Location: login.php'); exit;
}

// Generar CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="reporte_sistema_tutorias.csv"');

$output = fopen('php://output', 'w');

// Encabezados
fputcsv($output, ['Sección', 'Detalle', 'Valor']);

// Resumen general
$total_users = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$total_profesores = $pdo->query('SELECT COUNT(*) FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE r.nombre = "profesor"')->fetchColumn();
$total_estudiantes = $pdo->query('SELECT COUNT(*) FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE r.nombre = "estudiante"')->fetchColumn();
$total_tutorias = $pdo->query('SELECT COUNT(*) FROM tutorias')->fetchColumn();

fputcsv($output, ['Resumen', 'Total Usuarios', $total_users]);
fputcsv($output, ['Resumen', 'Profesores', $total_profesores]);
fputcsv($output, ['Resumen', 'Estudiantes', $total_estudiantes]);
fputcsv($output, ['Resumen', 'Total Tutorías', $total_tutorias]);

// Profesores activos
fputcsv($output, ['', '', '']);
fputcsv($output, ['Profesores Activos', 'Profesor', 'Tutorías']);
$sql = 'SELECT u.nombre, COUNT(t.id) AS count FROM usuarios u JOIN tutorias t ON u.id = t.profesor_id GROUP BY u.id ORDER BY count DESC LIMIT 10';
foreach ($pdo->query($sql) as $p) {
    fputcsv($output, ['Profesores Activos', $p['nombre'], $p['count']]);
}

// Demanda por materia
fputcsv($output, ['', '', '']);
fputcsv($output, ['Demanda por Materia', 'Materia', 'Tutorías']);
$sql = 'SELECT m.nombre, COUNT(t.id) AS count FROM materias m JOIN tutorias t ON m.id = t.materia_id GROUP BY m.id ORDER BY count DESC';
foreach ($pdo->query($sql) as $m) {
    fputcsv($output, ['Demanda por Materia', $m['nombre'], $m['count']]);
}

fclose($output);
exit;
?>