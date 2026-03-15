<?php
include_once 'header.php';
require_once 'conexion.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrador') {
    header('Location: login.php'); exit;
}

?>

<section class="card">
  <h2>Reportes del Sistema</h2>
  <p class="muted">Estadísticas detalladas y exportables.</p>
</section>

<section class="card">
  <h3>Resumen General</h3>
  <?php
  $total_users = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
  $total_profesores = $pdo->query('SELECT COUNT(*) FROM profesores')->fetchColumn();
  $total_estudiantes = $pdo->query('SELECT COUNT(*) FROM estudiantes')->fetchColumn();
  $total_tutorias = $pdo->query('SELECT COUNT(*) FROM tutoria_inscripciones')->fetchColumn();
  $tutorias_activas = $pdo->query("SELECT COUNT(*) FROM tutoria_inscripciones WHERE estado = 'inscrito'")->fetchColumn();
  $tutorias_completadas = $pdo->query("SELECT COUNT(*) FROM tutoria_inscripciones WHERE estado IN ('asistio', 'no_asistio')")->fetchColumn();
  ?>
  <ul>
    <li>Total de usuarios: <?php echo $total_users; ?> (Profesores: <?php echo $total_profesores; ?>, Estudiantes: <?php echo $total_estudiantes; ?>)</li>
    <li>Total de tutorías: <?php echo $total_tutorias; ?> (Activas: <?php echo $tutorias_activas; ?>, Completadas: <?php echo $tutorias_completadas; ?>)</li>
  </ul>
</section>

<section class="card">
  <h3>Profesores Más Activos</h3>
  <table class="table">
    <thead><tr><th>Profesor</th><th>Tutorías Impartidas</th></tr></thead>
    <tbody>
    <?php
    $sql = 'SELECT uprof.nombre, COUNT(ti.id_inscripcion) AS count
        FROM profesores prof
        JOIN usuarios uprof ON prof.id_usuario = uprof.id_usuario
        LEFT JOIN tutoria_inscripciones ti ON ti.id_profesor = prof.id_profesor
        GROUP BY prof.id_profesor
        ORDER BY count DESC
        LIMIT 10';
    foreach ($pdo->query($sql) as $p) {
      echo '<tr><td>'.htmlspecialchars($p['nombre']).'</td><td>'.$p['count'].'</td></tr>';
    }
    ?>
    </tbody>
  </table>
</section>

<section class="card">
  <h3>Demanda por Materia</h3>
  <table class="table">
    <thead><tr><th>Materia</th><th>Tutorías Solicitadas</th></tr></thead>
    <tbody>
    <?php
    $sql = 'SELECT m.nombre, COUNT(ti.id_inscripcion) AS count
        FROM materias m
        LEFT JOIN tutoria_inscripciones ti ON ti.id_materia = m.id_materia
        GROUP BY m.id_materia
        ORDER BY count DESC';
    foreach ($pdo->query($sql) as $m) {
      echo '<tr><td>'.htmlspecialchars($m['nombre']).'</td><td>'.$m['count'].'</td></tr>';
    }
    ?>
    </tbody>
  </table>
</section>

<section class="card">
  <h3>Tutorías por Estado</h3>
  <table class="table">
    <thead><tr><th>Estado</th><th>Cantidad</th></tr></thead>
    <tbody>
    <?php
    $sql = 'SELECT estado, COUNT(*) AS count FROM tutoria_inscripciones GROUP BY estado ORDER BY count DESC';
    foreach ($pdo->query($sql) as $e) {
        $color_class = '';
        switch($e['estado']) {
            case 'inscrito':
                $color_class = 'success';
                break;
            case 'cancelado':
                $color_class = 'cancelada';
                break;
            case 'asistio':
                $color_class = 'success';
                break;
            case 'no_asistio':
                $color_class = 'cancelada';
                break;
        }
        echo '<tr><td><span class="chip '.$color_class.'">'.htmlspecialchars(ucfirst($e['estado'])).'</span></td><td>'.$e['count'].'</td></tr>';
    }
    ?>
    </tbody>
  </table>
</section>

<section class="card">
  <h3>Exportar Reporte</h3>
  <p class="muted">Descarga un resumen en formato CSV.</p>
  <a class="btn" href="exportar_reporte.php">Descargar CSV</a>
</section>

<?php include_once 'footer.php'; ?>