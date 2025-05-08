<?php
// dashboard/admin.php
require_once __DIR__ . '/../config.php';

// 1) Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// 2) Nombre para saludo
$userName = $_SESSION['user_name'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Admin | Grupo FERRO</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="main">
    <h1>Bienvenido, <?= htmlspecialchars($userName) ?></h1>
    <p>Panel de <strong>Administración</strong> – Gestiona empresas, usuarios, productos, tickets y pedidos.</p>

    <ul>
      <li><a href="manage_companies.php">Gestionar Empresas</a></li>
      <li><a href="../dashboard/view_tickets_admin.php">Ver y gestionar Tickets</a></li>
      <li><a href="../dashboard/manage_orders.php">Ver y gestionar Pedidos</a></li>
      <li><a href="manage_products.php">Gestionar Productos</a></li>
      <li><a href="manage_price_lists.php">Asignar Listas de Precio</a></li>
      <li><a href="../auth/logout.php">Cerrar Sesión</a></li>
    </ul>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
