<?php
// dashboard/manage_company_users.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php'; // carga sendEmail()

// 1) Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// 2) Validar company_id
$companyId = intval($_GET['company_id'] ?? 0);
if (!$companyId) {
    die("Empresa inválida");
}

// 3) Cargar nombre de empresa
$stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
$stmt->execute([$companyId]);
$company = $stmt->fetch();
if (!$company) {
    die("Empresa no encontrada");
}

// Helper de escape
function e($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$action = $_GET['action'] ?? 'list';
$error  = $_GET['error']  ?? null;
$msg    = $_GET['msg']    ?? null;

// 4) Alta de usuario
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $plainPwd = $_POST['password'];
    $role     = $_POST['role'];

    // Comprobar duplicado
    $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $chk->execute([$email]);
    if ($chk->fetchColumn() > 0) {
        header("Location: manage_company_users.php?company_id=$companyId&action=add&error=exists");
        exit;
    }

    $password = password_hash($plainPwd, PASSWORD_DEFAULT);

    try {
        // Insertar usuario
        $insert = $pdo->prepare("
          INSERT INTO users (name, email, password, role, company_id)
          VALUES (?, ?, ?, ?, ?)
        ");
        $insert->execute([$name, $email, $password, $role, $companyId]);

        // Enviar correo
        $subject = "Bienvenido al Portal Grupo FERRO";
        $body    = "
          <h2>Hola, {$name}</h2>
          <p>Tu cuenta ha sido creada:</p>
          <ul>
            <li><strong>Email:</strong> {$email}</li>
            <li><strong>Contraseña:</strong> {$plainPwd}</li>
          </ul>
          <p>
            Accede aquí:
            <a href='https://www.gpoferro.com/p360/auth/login.php'>
              https://www.gpoferro.com/p360/auth/login.php
            </a>
          </p>
        ";
        sendEmail($email, $name, $subject, $body);

        header("Location: manage_company_users.php?company_id=$companyId&msg=added");
        exit;

    } catch (PDOException $e) {
        header("Location: manage_company_users.php?company_id=$companyId&action=add&error=db");
        exit;
    }
}

// 5) Eliminación de usuario
if ($action === 'delete' && isset($_GET['id'])) {
    $del = $pdo->prepare("
      DELETE FROM users
      WHERE id = ? AND company_id = ?
    ");
    $del->execute([(int)$_GET['id'], $companyId]);
    header("Location: manage_company_users.php?company_id=$companyId&msg=deleted");
    exit;
}

// 6) Listar usuarios
$users = [];
if ($action === 'list') {
    $stmt = $pdo->prepare("
      SELECT id, name, email, role, created_at
      FROM users
      WHERE company_id = ?
      ORDER BY name
    ");
    $stmt->execute([$companyId]);
    $users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Usuarios de <?= e($company['name']) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="main">
    <a href="manage_companies.php" class="btn">← Empresas</a>
    <h1>Usuarios de “<?= e($company['name']) ?>”</h1>

    <?php if ($action === 'list'): ?>
      <?php if ($msg === 'added'): ?>
        <p class="info">Usuario creado correctamente.</p>
      <?php elseif ($msg === 'deleted'): ?>
        <p class="info">Usuario eliminado.</p>
      <?php endif; ?>

      <a href="?company_id=<?= $companyId ?>&action=add" class="btn">+ Nuevo Usuario</a>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Alta</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['role']) ?></td>
                <td><?= $u['created_at'] ?></td>
                <td class="actions">
                  <a href="?company_id=<?= $companyId ?>&action=delete&id=<?= $u['id'] ?>"
                     onclick="return confirm('¿Eliminar este usuario?');">
                    Eliminar
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
              <tr><td colspan="5">No hay usuarios registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    <?php elseif ($action === 'add'): ?>
      <?php if ($error === 'exists'): ?>
        <p class="error">Ese email ya está registrado.</p>
      <?php elseif ($error === 'db'): ?>
        <p class="error">Error al guardar en la base de datos.</p>
      <?php endif; ?>

      <h2>Nuevo Usuario en <?= e($company['name']) ?></h2>
      <form method="POST">
        <label>Nombre:<br>
          <input type="text" name="name" required>
        </label><br>
        <label>Email:<br>
          <input type="email" name="email" required>
        </label><br>
        <label>Contraseña:<br>
          <input type="password" name="password" required>
        </label><br>
        <label>Rol:<br>
          <select name="role">
            <option value="operativas">Operativas</option>
            <option value="compras">Compras</option>
          </select>
        </label><br><br>
        <button type="submit">Crear Usuario</button>
        <a href="manage_company_users.php?company_id=<?= $companyId ?>" class="btn" style="margin-left:1rem;">Cancelar</a>
      </form>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
