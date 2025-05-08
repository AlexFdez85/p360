<?php
// p360/dashboard/order_detail.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config.php';

// 1) Solo rol “compras”
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'compras') {
    header('Location: ../auth/login.php');
    exit;
}
$userId    = $_SESSION['user_id'];
$companyId = $_SESSION['company_id'];

// Helper de escape
function e($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// 2) ID de pedido
$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    die("ID de pedido inválido.");
}

// 3) Cargar pedido y validar
$stmt = $pdo->prepare("
    SELECT o.*, c.name AS company_name, u.name AS user_name
    FROM orders o
    JOIN companies c ON c.id = o.company_id
    JOIN users    u ON u.id = o.user_id
    WHERE o.id = ? AND o.company_id = ? AND o.user_id = ?
    LIMIT 1
");
$stmt->execute([$orderId, $companyId, $userId]);
$order = $stmt->fetch();
if (!$order) {
    die("Pedido no encontrado o sin permiso.");
}

// 4) Cargar partidas
$itemStmt = $pdo->prepare("
    SELECT oi.quantity, oi.unit_price, oi.discount_pct, oi.subtotal,
           p.name AS product_name
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

// 5) Cargar historial de status (admin)
$histStmt = $pdo->prepare("
    SELECT h.changed_at, h.old_status, h.new_status, h.comment, usr.name AS changer
    FROM order_status_history h
    JOIN users usr ON usr.id = h.changed_by
    WHERE h.order_id = ?
    ORDER BY h.changed_at ASC
");
$histStmt->execute([$orderId]);
$history = $histStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle Pedido #<?= e($orderId) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="main">
    <a href="compras.php" class="btn">← Mis Pedidos</a>
    <h1>Pedido #<?= e($order['id']) ?> — <?= ucfirst(e($order['status'])) ?></h1>

    <p><strong>Empresa:</strong> <?= e($order['company_name']) ?></p>
    <p><strong>Usuario:</strong> <?= e($order['user_name']) ?></p>

    <!-- Partidas -->
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio</th>
            <th>Desc %</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="5">No hay partidas.</td></tr>
          <?php else: foreach($items as $it): ?>
            <tr>
              <td data-label="Producto"><?= e($it['product_name']) ?></td>
              <td data-label="Cantidad"><?= $it['quantity'] ?></td>
              <td data-label="Precio">$<?= number_format($it['unit_price'],2) ?></td>
              <td data-label="Desc %"><?= number_format($it['discount_pct'],2) ?>%</td>
              <td data-label="Subtotal">$<?= number_format($it['subtotal'],2) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Totales -->
    <p><strong>Subtotal:</strong> $<?= number_format($order['subtotal'],2) ?></p>
    <p><strong>IVA (16%):</strong> $<?= number_format($order['iva'],2) ?></p>
    <p><strong>Total:</strong> $<?= number_format($order['total'],2) ?></p>

    <!-- Comentarios del cliente (solo lectura) -->
    <section style="margin-top:2rem;">
      <h2>Mis Comentarios u Observaciones</h2>
      <div class="box">
        <?= nl2br(e($order['customer_comments'] ?? '— Sin comentarios —')) ?>
      </div>
    </section>

    <!-- Historial de cambios de estado -->
    <section style="margin-top:2rem;">
      <h2>Historial de Estados</h2>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Fecha</th><th>De</th><th>A</th><th>Usuario</th><th>Comentario</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($history)): ?>
              <tr><td colspan="5">Sin historial de cambios.</td></tr>
            <?php else: foreach($history as $h): ?>
              <tr>
                <td data-label="Fecha"><?= e($h['changed_at']) ?></td>
                <td data-label="De"><?= ucfirst(e($h['old_status'])) ?></td>
                <td data-label="A"><?= ucfirst(e($h['new_status'])) ?></td>
                <td data-label="Usuario"><?= e($h['changer']) ?></td>
                <td data-label="Comentario"><?= nl2br(e($h['comment'] ?? '—')) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>



