<?php
include_once 'header.php';
require_once 'conexion.php';
require_once 'csrf_helper.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrador') {
  header('Location: login.php'); exit;
}

// Obtener métricas para el dashboard
$stmt_total_usuarios = $pdo->query('SELECT COUNT(*) FROM usuarios');
$total_usuarios = $stmt_total_usuarios->fetchColumn();

$stmt_total_profesores = $pdo->query('SELECT COUNT(*) FROM profesores p JOIN usuarios u ON p.id_usuario = u.id_usuario WHERE u.estado = "activo"');
$total_profesores = $stmt_total_profesores->fetchColumn();

$stmt_total_estudiantes = $pdo->query('SELECT COUNT(*) FROM estudiantes e JOIN usuarios u ON e.id_usuario = u.id_usuario WHERE u.estado = "activo"');
$total_estudiantes = $stmt_total_estudiantes->fetchColumn();

$stmt_total_materias = $pdo->query('SELECT COUNT(*) FROM materias');
$total_materias = $stmt_total_materias->fetchColumn();

$stmt_total_tutorias = $pdo->query('SELECT COUNT(*) FROM tutoria_inscripciones WHERE estado = "inscrito"');
$total_tutorias = $stmt_total_tutorias->fetchColumn();

// Métricas adicionales para mejor reporte
$stmt_total_disponibilidades = $pdo->query('SELECT COUNT(*) FROM disponibilidad WHERE estado = "disponible" AND fecha >= CURDATE()');
$total_disponibilidades = $stmt_total_disponibilidades->fetchColumn();

$stmt_tutorias_hoy = $pdo->query('SELECT COUNT(*) FROM tutoria_inscripciones ti JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad WHERE ti.estado = "inscrito" AND d.fecha = CURDATE()');
$tutorias_hoy = $stmt_tutorias_hoy->fetchColumn();

$stmt_tutorias_semana = $pdo->query('SELECT COUNT(*) FROM tutoria_inscripciones ti JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad WHERE ti.estado = "inscrito" AND d.fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)');
$tutorias_semana = $stmt_tutorias_semana->fetchColumn();

// Procesar acciones administrativas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Verificar CSRF
  $csrf_token = $_POST['csrf_token'] ?? '';
  if (!verify_csrf_token($csrf_token)) {
    $error = 'Solicitud inválida. Por favor intenta nuevamente.';
  } else {
    $action = $_POST['action'] ?? '';

    // Cambiar estado (activo/inactivo)
    if ($action === 'toggle_state') {
      $u = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
      $newState = in_array($_POST['state'] ?? '', ['activo', 'inactivo']) ? $_POST['state'] : 'activo';
      if ($u && $u > 0) {
        $stmt = $pdo->prepare('UPDATE usuarios SET estado = :s WHERE id_usuario = :id');
        $stmt->execute(['s' => $newState, 'id' => $u]);
        header('Location: dashboard_admin_v2.php?success=state_updated'); exit;
      }
    }

    // Crear usuario
    if ($action === 'create_user') {
      $nombre = trim($_POST['nombre'] ?? '');
      $correo = filter_var(trim($_POST['correo'] ?? ''), FILTER_VALIDATE_EMAIL);
      $rol = in_array($_POST['rol'] ?? '', ['estudiante', 'profesor', 'administrador']) ? $_POST['rol'] : 'estudiante';
      $pass = $_POST['password'] ?? '';
      $codigo = trim($_POST['codigo_estudiantil'] ?? '');
      $carrera = trim($_POST['carrera'] ?? '');
      $semestre = filter_var($_POST['semestre'] ?? 0, FILTER_VALIDATE_INT);
      $especialidad = trim($_POST['especialidad'] ?? '');
      $departamento = trim($_POST['departamento'] ?? '');

      // Validaciones
      if (empty($nombre) || strlen($nombre) < 2 || strlen($nombre) > 100) {
        $error = 'El nombre debe tener entre 2 y 100 caracteres.';
      } elseif (!$correo) {
        $error = 'Correo electrónico inválido.';
      } elseif (empty($pass) || strlen($pass) < 4 || strlen($pass) > 100) {
        $error = 'La contraseña debe tener entre 4 y 100 caracteres.';
      } else {
        // Verificar correo duplicado
        $stmtCheck = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE correo = :c LIMIT 1');
        $stmtCheck->execute(['c' => $correo]);
        if ($stmtCheck->fetchColumn()) {
          $error = 'El correo ya está registrado.';
        } else {
          // Insertar usuario
          $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, contrasena, rol, estado) VALUES (:n, :c, :p, :r, "activo")');
          $stmt->execute(['n' => $nombre, 'c' => $correo, 'p' => $pass, 'r' => $rol]);
          $newUserId = $pdo->lastInsertId();

          // Crear registro específico según rol
          if ($rol === 'estudiante') {
            $stmtEst = $pdo->prepare('INSERT INTO estudiantes (id_usuario, codigo_estudiantil, carrera, semestre) VALUES (:u, :c, :car, :s)');
            $stmtEst->execute(['u' => $newUserId, 'c' => $codigo ?: null, 'car' => $carrera ?: null, 's' => $semestre ?: null]);
          } elseif ($rol === 'profesor') {
            $stmtProf = $pdo->prepare('INSERT INTO profesores (id_usuario, especialidad, departamento) VALUES (:u, :e, :d)');
            $stmtProf->execute(['u' => $newUserId, 'e' => $especialidad ?: null, 'd' => $departamento ?: null]);
          }

          header('Location: dashboard_admin_v2.php?success=user_created'); exit;
        }
      }
    }

    // Eliminar usuario
    if ($action === 'delete_user') {
      $u = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
      if ($u && $u > 0 && $u != $_SESSION['user_id']) {
        // Verificar si es el último administrador
        $stmtAdmin = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE rol = "administrador" AND estado = "activo"');
        $stmtAdmin->execute();
        $adminCount = $stmtAdmin->fetchColumn();
        
        $stmtUserRole = $pdo->prepare('SELECT rol FROM usuarios WHERE id_usuario = :id');
        $stmtUserRole->execute(['id' => $u]);
        $userRole = $stmtUserRole->fetchColumn();
        
        if ($userRole === 'administrador' && $adminCount <= 1) {
          $error = 'No puedes eliminar al último administrador activo.';
        } else {
          // Eliminar registros relacionados
          $pdo->prepare('DELETE FROM estudiantes WHERE id_usuario = :id')->execute(['id' => $u]);
          $pdo->prepare('DELETE FROM profesores WHERE id_usuario = :id')->execute(['id' => $u]);
          $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = :id')->execute(['id' => $u]);
          header('Location: dashboard_admin_v2.php?success=user_deleted'); exit;
        }
      }
    }

    // Crear materia
    if ($action === 'create_materia') {
      $nombre = trim($_POST['nombre_materia'] ?? '');
      $profesor_id = filter_var($_POST['profesor_id'] ?? 0, FILTER_VALIDATE_INT);
      
      if (empty($nombre) || strlen($nombre) < 2 || strlen($nombre) > 100) {
        $error = 'El nombre de la materia debe tener entre 2 y 100 caracteres.';
      } elseif ($profesor_id <= 0) {
        $error = 'Debes seleccionar un profesor.';
      } else {
        $stmt = $pdo->prepare('INSERT INTO materias (nombre) VALUES (:n)');
        $stmt->execute(['n' => $nombre]);
        $materiaId = $pdo->lastInsertId();
        
        $stmtLink = $pdo->prepare('INSERT INTO profesor_materia (id_profesor, id_materia) VALUES (:p, :m)');
        $stmtLink->execute(['p' => $profesor_id, 'm' => $materiaId]);
        
        header('Location: dashboard_admin_v2.php?success=materia_created'); exit;
      }
    }
  }
}

// Mensajes de éxito
if (isset($_GET['success'])) {
  switch($_GET['success']) {
    case 'user_created': $success = 'Usuario creado exitosamente.'; break;
    case 'user_deleted': $success = 'Usuario eliminado exitosamente.'; break;
    case 'state_updated': $success = 'Estado de usuario actualizado.'; break;
    case 'materia_created': $success = 'Materia creada exitosamente.'; break;
  }
}

// Obtener datos para las tablas
$stmt_usuarios = $pdo->query('
  SELECT u.id_usuario, u.nombre, u.correo, u.rol, u.estado, u.fecha_creacion,
    CASE 
      WHEN u.rol = "estudiante" THEN e.codigo_estudiantil
      WHEN u.rol = "profesor" THEN p.especialidad
      ELSE NULL
    END as detalle
  FROM usuarios u
  LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
  LEFT JOIN profesores p ON u.id_usuario = p.id_usuario
  ORDER BY u.fecha_creacion DESC
');

$stmt_materias = $pdo->query('
  SELECT m.id_materia, m.nombre, u.nombre as profesor_nombre
  FROM materias m
  LEFT JOIN profesor_materia pm ON m.id_materia = pm.id_materia
  LEFT JOIN profesores p ON pm.id_profesor = p.id_profesor
  LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
  ORDER BY m.nombre
');

$stmt_profesores = $pdo->query('
  SELECT u.id_usuario, u.nombre, u.correo, p.especialidad, p.departamento
  FROM usuarios u
  JOIN profesores p ON u.id_usuario = p.id_usuario
  WHERE u.estado = "activo"
  ORDER BY u.nombre
');
?>

<style>
/* Estilos mejorados para el dashboard */
.admin-dashboard {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.metric-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px;
  padding: 25px;
  display: flex;
  align-items: center;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
}

.metric-card:hover {
  transform: translateY(-5px);
}

.metric-card:nth-child(2) {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.metric-card:nth-child(3) {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.metric-card:nth-child(4) {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.metric-card:nth-child(5) {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.metric-icon {
  font-size: 2.5rem;
  margin-right: 20px;
}

.metric-data h3 {
  font-size: 2rem;
  margin: 0;
  font-weight: 700;
}

.metric-data p {
  margin: 5px 0 0 0;
  opacity: 0.9;
  font-size: 0.9rem;
}

.admin-content {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 30px;
}

.admin-sidebar {
  background: white;
  border-radius: 12px;
  padding: 25px;
  height: fit-content;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.sidebar-section {
  margin-bottom: 30px;
}

.sidebar-section h4 {
  color: #333;
  margin-bottom: 15px;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.sidebar-section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar-section li {
  margin-bottom: 8px;
}

.sidebar-section a {
  color: #666;
  text-decoration: none;
  padding: 8px 12px;
  display: block;
  border-radius: 6px;
  transition: all 0.3s ease;
}

.sidebar-section a:hover {
  background: #f8f9fa;
  color: #333;
  transform: translateX(5px);
}

.main-content {
  background: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.content-section {
  margin-bottom: 40px;
}

.content-section h3 {
  color: #333;
  margin-bottom: 20px;
  font-size: 1.3rem;
  border-bottom: 2px solid #667eea;
  padding-bottom: 10px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  overflow: hidden;
}

.data-table th {
  background: #f8f9fa;
  padding: 15px;
  text-align: left;
  font-weight: 600;
  color: #333;
  border-bottom: 2px solid #dee2e6;
}

.data-table td {
  padding: 15px;
  border-bottom: 1px solid #dee2e6;
}

.data-table tr:hover {
  background: #f8f9fa;
}

.chip {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.chip.activo {
  background: #d4edda;
  color: #155724;
}

.chip.inactivo {
  background: #f8d7da;
  color: #721c24;
}

.chip.estudiante {
  background: #cce5ff;
  color: #004085;
}

.chip.profesor {
  background: #fff3cd;
  color: #856404;
}

.chip.administrador {
  background: #e2e3e5;
  color: #383d41;
}

.btn-group {
  display: flex;
  gap: 8px;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.8rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.search-bar {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.search-bar input {
  flex: 1;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.alert {
  padding: 15px 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.alert.success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert.error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
  .admin-content {
    grid-template-columns: 1fr;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="admin-dashboard">
  <!-- Métricas Principales -->
  <div class="metrics-grid">
    <div class="metric-card">
      <div class="metric-icon">👥</div>
      <div class="metric-data">
        <h3><?php echo $total_usuarios; ?></h3>
        <p>Total Usuarios</p>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-icon">👨‍🏫</div>
      <div class="metric-data">
        <h3><?php echo $total_profesores; ?></h3>
        <p>Profesores Activos</p>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-icon">🎓</div>
      <div class="metric-data">
        <h3><?php echo $total_estudiantes; ?></h3>
        <p>Estudiantes Activos</p>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-icon">📚</div>
      <div class="metric-data">
        <h3><?php echo $total_materias; ?></h3>
        <p>Materias Disponibles</p>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-icon">📊</div>
      <div class="metric-data">
        <h3><?php echo $total_tutorias; ?></h3>
        <p>Tutorías Activas</p>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-icon">📅</div>
      <div class="metric-data">
        <h3><?php echo $total_disponibilidades; ?></h3>
        <p>Disponibilidades Activas</p>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-icon">🎯</div>
      <div class="metric-data">
        <h3><?php echo $tutorias_hoy; ?></h3>
        <p>Tutorías Hoy</p>
      </div>
    </div>
    <div class="metric-card">
      <div class="metric-icon">📈</div>
      <div class="metric-data">
        <h3><?php echo $tutorias_semana; ?></h3>
        <p>Tutorías Esta Semana</p>
      </div>
    </div>
  </div>

  <!-- Mensajes -->
  <?php if (!empty($error)): ?>
    <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  
  <?php if (!empty($success)): ?>
    <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <div class="admin-content">
    <!-- Sidebar de Navegación -->
    <div class="admin-sidebar">
      <div class="sidebar-section">
        <h4>👥 Gestión de Usuarios</h4>
        <ul>
          <li><a href="#usuarios">📋 Lista de Usuarios</a></li>
          <li><a href="#crear-usuario">➕ Crear Usuario</a></li>
        </ul>
      </div>
      
      <div class="sidebar-section">
        <h4>📚 Gestión Académica</h4>
        <ul>
          <li><a href="#tutorias-solicitadas">📋 Tutorías Solicitadas</a></li>
          <li><a href="#materias">📖 Materias</a></li>
          <li><a href="#profesores">👨‍🏫 Profesores</a></li>
        </ul>
      </div>
      
      <div class="sidebar-section">
        <h4>📊 Estadísticas</h4>
        <ul>
          <li><a href="#estadisticas">📈 Reportes y Estadísticas</a></li>
        </ul>
      </div>
    </div>

    <!-- Contenido Principal -->
    <div class="main-content">
      <!-- Crear Nuevo Usuario -->
      <div id="crear-usuario" class="content-section">
        <h3>➕ Crear Nuevo Usuario</h3>
        <form method="post">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="create_user">
          
          <div class="form-grid">
            <div class="field">
              <label>Nombre completo</label>
              <input type="text" name="nombre" required>
            </div>
            
            <div class="field">
              <label>Correo electrónico</label>
              <input type="email" name="correo" required>
            </div>
            
            <div class="field">
              <label>Contraseña</label>
              <input type="password" name="password" required>
            </div>
            
            <div class="field">
              <label>Rol</label>
              <select name="rol" required>
                <option value="estudiante">🎓 Estudiante</option>
                <option value="profesor">👨‍🏫 Profesor</option>
                <option value="administrador">👤 Administrador</option>
              </select>
            </div>
            
            <div class="field" id="estudiante-fields" style="display: none;">
              <label>Código Estudiantil</label>
              <input type="text" name="codigo_estudiantil">
            </div>
            
            <div class="field" id="estudiante-carrera" style="display: none;">
              <label>Carrera</label>
              <input type="text" name="carrera">
            </div>
            
            <div class="field" id="estudiante-semestre" style="display: none;">
              <label>Semestre</label>
              <input type="number" name="semestre" min="1">
            </div>
            
            <div class="field" id="profesor-especialidad" style="display: none;">
              <label>Especialidad</label>
              <input type="text" name="especialidad">
            </div>
            
            <div class="field" id="profesor-departamento" style="display: none;">
              <label>Departamento</label>
              <input type="text" name="departamento">
            </div>
          </div>
          
          <button class="btn primary" type="submit">Crear Usuario</button>
        </form>
      </div>

      <!-- Lista de Usuarios -->
      <div id="usuarios" class="content-section">
        <h3>📋 Lista de Usuarios</h3>
        
        <div class="search-bar">
          <input type="text" id="searchUsers" placeholder="🔍 Buscar usuarios..." onkeyup="filterUsers()">
          <select id="filterRole" onchange="filterUsers()">
            <option value="">Todos los roles</option>
            <option value="estudiante">Estudiantes</option>
            <option value="profesor">Profesores</option>
            <option value="administrador">Administradores</option>
          </select>
        </div>
        
        <table class="data-table" id="usersTable">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Detalle</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($user = $stmt_usuarios->fetch()): ?>
              <tr data-role="<?php echo $user['rol']; ?>">
                <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                <td><?php echo htmlspecialchars($user['correo']); ?></td>
                <td><span class="chip <?php echo $user['rol']; ?>"><?php echo ucfirst($user['rol']); ?></span></td>
                <td><span class="chip <?php echo $user['estado']; ?>"><?php echo ucfirst($user['estado']); ?></span></td>
                <td><?php echo htmlspecialchars($user['detalle'] ?: '-'); ?></td>
                <td>
                  <div class="btn-group">
                    <?php if ($user['estado'] === 'activo'): ?>
                      <form method="post" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="toggle_state">
                        <input type="hidden" name="user_id" value="<?php echo $user['id_usuario']; ?>">
                        <input type="hidden" name="state" value="inactivo">
                        <button class="btn warning btn-sm" type="submit">⏸️ Desactivar</button>
                      </form>
                    <?php else: ?>
                      <form method="post" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="toggle_state">
                        <input type="hidden" name="user_id" value="<?php echo $user['id_usuario']; ?>">
                        <input type="hidden" name="state" value="activo">
                        <button class="btn success btn-sm" type="submit">▶️ Activar</button>
                      </form>
                    <?php endif; ?>
                    
                    <?php if ($user['id_usuario'] != $_SESSION['user_id']): ?>
                      <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?php echo $user['id_usuario']; ?>">
                        <button class="btn danger btn-sm" type="submit">🗑️ Eliminar</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- Tutorías Solicitadas -->
      <div id="tutorias-solicitadas" class="content-section">
        <h3>📋 Tutorías Solicitadas</h3>
        
        <div class="search-bar">
          <input type="text" id="searchTutorias" placeholder="🔍 Buscar tutorías..." onkeyup="filterTutorias()">
          <select id="filterEstado" onchange="filterTutorias()">
            <option value="">Todos los estados</option>
            <option value="inscrito">Inscritas</option>
            <option value="cancelado">Canceladas</option>
            <option value="asistio">Asistieron</option>
            <option value="no_asistio">No asistieron</option>
          </select>
        </div>
        
        <?php
        // Obtener todas las tutorías solicitadas
        $stmt_tutorias_solicitadas = $pdo->query('
          SELECT 
            ti.id_inscripcion,
            ti.estado,
            ti.fecha_inscripcion,
            u_est.nombre as estudiante_nombre,
            u_est.correo as estudiante_email,
            u_prof.nombre as profesor_nombre,
            m.nombre as materia_nombre,
            d.fecha as tutoria_fecha,
            d.hora_inicio as tutoria_hora,
            d.cupo_maximo,
            d.cupo_actual
          FROM tutoria_inscripciones ti
          JOIN estudiantes e ON ti.id_estudiante = e.id_estudiante
          JOIN usuarios u_est ON e.id_usuario = u_est.id_usuario
          JOIN profesores p ON ti.id_profesor = p.id_profesor
          JOIN usuarios u_prof ON p.id_usuario = u_prof.id_usuario
          JOIN disponibilidad d ON ti.id_disponibilidad = d.id_disponibilidad
          LEFT JOIN materias m ON ti.id_materia = m.id_materia
          ORDER BY d.fecha DESC, d.hora_inicio DESC, ti.fecha_inscripcion DESC
        ');
        ?>
        
        <table class="data-table" id="tutoriasTable">
          <thead>
            <tr>
              <th>Estudiante</th>
              <th>Profesor</th>
              <th>Materia</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Estado</th>
              <th>Cupos</th>
              <th>Fecha Inscripción</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($tutoria = $stmt_tutorias_solicitadas->fetch()): ?>
              <tr data-estado="<?php echo $tutoria['estado']; ?>">
                <td>
                  <strong><?php echo htmlspecialchars($tutoria['estudiante_nombre']); ?></strong><br>
                  <small style="color: #666;"><?php echo htmlspecialchars($tutoria['estudiante_email']); ?></small>
                </td>
                <td><?php echo htmlspecialchars($tutoria['profesor_nombre']); ?></td>
                <td><?php echo htmlspecialchars($tutoria['materia_nombre'] ?: 'Sin materia'); ?></td>
                <td><?php echo htmlspecialchars($tutoria['tutoria_fecha']); ?></td>
                <td><?php echo htmlspecialchars(substr($tutoria['tutoria_hora'], 0, 5)); ?></td>
                <td>
                  <?php
                  $color_estado = '';
                  $texto_estado = ucfirst($tutoria['estado']);
                  switch($tutoria['estado']) {
                    case 'inscrito':
                      $color_estado = 'success';
                      $texto_estado = 'Inscrito';
                      break;
                    case 'cancelado':
                      $color_estado = 'cancelada';
                      $texto_estado = 'Cancelado';
                      break;
                    case 'asistio':
                      $color_estado = 'success';
                      $texto_estado = 'Asistió';
                      break;
                    case 'no_asistio':
                      $color_estado = 'cancelada';
                      $texto_estado = 'No asistió';
                      break;
                  }
                  ?>
                  <span class="chip <?php echo $color_estado; ?>"><?php echo $texto_estado; ?></span>
                </td>
                <td>
                  <?php 
                  $cupos_disponibles = $tutoria['cupo_maximo'] - $tutoria['cupo_actual'];
                  echo $tutoria['cupo_actual'] . '/' . $tutoria['cupo_maximo'];
                  if ($cupos_disponibles > 0) {
                    echo ' <small style="color: green;">(' . $cupos_disponibles . ' libres)</small>';
                  } else {
                    echo ' <small style="color: red;">(lleno)</small>';
                  }
                  ?>
                </td>
                <td>
                  <small><?php echo date('d/m/Y H:i', strtotime($tutoria['fecha_inscripcion'])); ?></small>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        
        <?php if ($stmt_tutorias_solicitadas->rowCount() === 0): ?>
          <div style="text-align: center; color: #666; padding: 40px;">
            <p>No hay tutorías solicitadas en el sistema.</p>
            <small>Las tutorías aparecerán aquí cuando los estudiantes se inscriban en las disponibilidades de los profesores.</small>
          </div>
        <?php endif; ?>
      </div>

      <!-- Materias -->
      <div id="materias" class="content-section">
        <h3>📖 Gestión de Materias</h3>
        <form method="post" style="margin-bottom: 30px;">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="create_materia">
          
          <div class="form-grid">
            <div class="field">
              <label>Nombre de la materia</label>
              <input type="text" name="nombre_materia" required>
            </div>
            
            <div class="field">
              <label>Profesor asignado</label>
              <select name="profesor_id" required>
                <option value="">Seleccionar profesor</option>
                <?php while ($prof = $stmt_profesores->fetch()): ?>
                  <option value="<?php echo $prof['id_usuario']; ?>"><?php echo htmlspecialchars($prof['nombre']); ?></option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
          
          <button class="btn primary" type="submit">Crear Materia</button>
        </form>
        
        <table class="data-table">
          <thead>
            <tr>
              <th>Materia</th>
              <th>Profesor Asignado</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $stmt_profesores->execute(); // Reset pointer
            while ($materia = $stmt_materias->fetch()): 
            ?>
              <tr>
                <td><?php echo htmlspecialchars($materia['nombre']); ?></td>
                <td><?php echo htmlspecialchars($materia['profesor_nombre'] ?: 'Sin asignar'); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- Reportes y Estadísticas -->
      <div id="estadisticas" class="content-section">
        <h3>📊 Reportes y Estadísticas del Sistema</h3>
        
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
          <!-- Estadísticas de Tutorías por Estado -->
          <div class="stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h4 style="margin-bottom: 15px; color: #333;">📈 Tutorías por Estado</h4>
            <?php
            $stmt_tutorias_estado = $pdo->query('
              SELECT ti.estado, COUNT(*) as cantidad 
              FROM tutoria_inscripciones ti 
              GROUP BY ti.estado
            ');
            ?>
            <table style="width: 100%;">
              <?php while ($estado = $stmt_tutorias_estado->fetch()): ?>
                <tr>
                  <td style="padding: 5px;"><?php echo ucfirst($estado['estado']); ?></td>
                  <td style="padding: 5px; text-align: right; font-weight: bold;"><?php echo $estado['cantidad']; ?></td>
                </tr>
              <?php endwhile; ?>
            </table>
          </div>

          <!-- Profesores más Activos -->
          <div class="stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h4 style="margin-bottom: 15px; color: #333;">👨‍🏫 Profesores Más Activos</h4>
            <?php
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
            ?>
            <table style="width: 100%;">
              <?php while ($prof = $stmt_profesores_activos->fetch()): ?>
                <tr>
                  <td style="padding: 5px;"><?php echo htmlspecialchars($prof['nombre']); ?></td>
                  <td style="padding: 5px; text-align: right; font-weight: bold;"><?php echo $prof['tutorias_count']; ?></td>
                </tr>
              <?php endwhile; ?>
            </table>
          </div>

          <!-- Materias más Populares -->
          <div class="stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h4 style="margin-bottom: 15px; color: #333;">📚 Materias más Populares</h4>
            <?php
            $stmt_materias_populares = $pdo->query('
              SELECT m.nombre, COUNT(ti.id_inscripcion) as inscripciones_count
              FROM materias m
              JOIN tutoria_inscripciones ti ON m.id_materia = ti.id_materia
              WHERE ti.estado = "inscrito"
              GROUP BY m.id_materia, m.nombre
              ORDER BY inscripciones_count DESC
              LIMIT 5
            ');
            ?>
            <table style="width: 100%;">
              <?php while ($materia = $stmt_materias_populares->fetch()): ?>
                <tr>
                  <td style="padding: 5px;"><?php echo htmlspecialchars($materia['nombre']); ?></td>
                  <td style="padding: 5px; text-align: right; font-weight: bold;"><?php echo $materia['inscripciones_count']; ?></td>
                </tr>
              <?php endwhile; ?>
            </table>
          </div>

          <!-- Estudiantes más Activos -->
          <div class="stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h4 style="margin-bottom: 15px; color: #333;">🎓 Estudiantes más Activos</h4>
            <?php
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
            ?>
            <table style="width: 100%;">
              <?php while ($est = $stmt_estudiantes_activos->fetch()): ?>
                <tr>
                  <td style="padding: 5px;"><?php echo htmlspecialchars($est['nombre']); ?></td>
                  <td style="padding: 5px; text-align: right; font-weight: bold;"><?php echo $est['tutorias_count']; ?></td>
                </tr>
              <?php endwhile; ?>
            </table>
          </div>
        </div>

        <!-- Resumen General -->
        <div class="summary-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
          <h4 style="margin-bottom: 15px; color: #333;">📋 Resumen General del Sistema</h4>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div style="text-align: center;">
              <div style="font-size: 2rem; font-weight: bold; color: #667eea;"><?php echo $total_tutorias; ?></div>
              <div style="color: #666;">Total Tutorías Activas</div>
            </div>
            <div style="text-align: center;">
              <div style="font-size: 2rem; font-weight: bold; color: #f093fb;"><?php echo $total_disponibilidades; ?></div>
              <div style="color: #666;">Disponibilidades Activas</div>
            </div>
            <div style="text-align: center;">
              <div style="font-size: 2rem; font-weight: bold; color: #4facfe;"><?php 
                $stmt_avg_students = $pdo->query('SELECT AVG(cupo_actual) as avg FROM disponibilidad WHERE cupo_actual > 0');
                $avg = $stmt_avg_students->fetchColumn();
                echo round($avg, 1);
              ?></div>
              <div style="color: #666;">Promedio Estudiantes por Tutoría</div>
            </div>
            <div style="text-align: center;">
              <div style="font-size: 2rem; font-weight: bold; color: #43e97b;"><?php 
                $stmt_fill_rate = $pdo->query('SELECT AVG(cupo_actual/cupo_maximo)*100 as fill_rate FROM disponibilidad WHERE cupo_maximo > 0');
                $fill_rate = $stmt_fill_rate->fetchColumn();
                echo round($fill_rate, 1) . '%';
              ?></div>
              <div style="color: #666;">Tasa de Ocupación</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Mostrar/ocultar campos según el rol
document.querySelector('select[name="rol"]').addEventListener('change', function() {
  const role = this.value;
  
  // Ocultar todos los campos específicos
  document.getElementById('estudiante-fields').style.display = 'none';
  document.getElementById('estudiante-carrera').style.display = 'none';
  document.getElementById('estudiante-semestre').style.display = 'none';
  document.getElementById('profesor-especialidad').style.display = 'none';
  document.getElementById('profesor-departamento').style.display = 'none';
  
  // Mostrar campos según el rol
  if (role === 'estudiante') {
    document.getElementById('estudiante-fields').style.display = 'block';
    document.getElementById('estudiante-carrera').style.display = 'block';
    document.getElementById('estudiante-semestre').style.display = 'block';
  } else if (role === 'profesor') {
    document.getElementById('profesor-especialidad').style.display = 'block';
    document.getElementById('profesor-departamento').style.display = 'block';
  }
});

// Función de búsqueda y filtrado
function filterUsers() {
  const searchTerm = document.getElementById('searchUsers').value.toLowerCase();
  const filterRole = document.getElementById('filterRole').value;
  const rows = document.querySelectorAll('#usersTable tbody tr');
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    const role = row.getAttribute('data-role');
    
    const matchesSearch = text.includes(searchTerm);
    const matchesRole = !filterRole || role === filterRole;
    
    row.style.display = matchesSearch && matchesRole ? '' : 'none';
  });
}

// Función de búsqueda y filtrado para tutorías
function filterTutorias() {
  const searchTerm = document.getElementById('searchTutorias').value.toLowerCase();
  const filterEstado = document.getElementById('filterEstado').value;
  const rows = document.querySelectorAll('#tutoriasTable tbody tr');
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    const estado = row.getAttribute('data-estado');
    
    const matchesSearch = text.includes(searchTerm);
    const matchesEstado = !filterEstado || estado === filterEstado;
    
    row.style.display = matchesSearch && matchesEstado ? '' : 'none';
  });
}

// Smooth scroll para navegación
document.querySelectorAll('.sidebar-section a').forEach(anchor => {
  anchor.addEventListener('click', function(e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

// Inicializar campos según rol seleccionado
document.addEventListener('DOMContentLoaded', function() {
  const roleSelect = document.querySelector('select[name="rol"]');
  if (roleSelect) {
    roleSelect.dispatchEvent(new Event('change'));
  }
});
</script>

<?php include_once 'footer.php'; ?>
