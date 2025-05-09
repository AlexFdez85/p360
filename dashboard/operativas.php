<?php
// dashboard/operativas.php
require_once __DIR__ . '/../config.php';

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operativas') {
    header('Location: ../auth/login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario Operativas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Área Operativa | Grupo FERRO</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="main">
    <h1>Bienvenido(a), <?= htmlspecialchars($userName) ?></h1>
    <p>Panel de <strong>Soporte Técnico</strong> – Sección Operativas.</p>

    <ul>
      <li><a href="../dashboard/create_ticket.php">Crear Nuevo Ticket</a></li>
      <li><a href="../dashboard/view_tickets.php">Ver Historial de Tickets</a></li>
      <li><a href="../auth/logout.php">Cerrar Sesión</a></li>
    </ul>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
