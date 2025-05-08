<?php
require_once __DIR__ . '/../config.php';

// 1) Verificar sesión y rol 'compras'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'compras') {
    header('Location: ../auth/login.php');
    exit;
}

// 2) Obtener ID de pedido
$pedidoId = intval($_GET['id'] ?? 0);
if (!$pedidoId) {
    die("Pedido inválido");
}

// 3) Traer datos del pedido
$stmt = $pdo->prepare("
  SELECT p.*, u.name AS cliente
  FROM pedidos p
  JOIN users u ON u.id = p.user_id
  WHERE p.id = ?
");
$stmt->execute([$pedidoId]);
$pedido = $stmt->fetch();
if (!$pedido) {
    die("Pedido no encontrado");
}

// 4) Traer líneas del pedido con datos de producto
$lines = $pdo->prepare("
  SELECT
    pi.*,
    pr.name,
    pr.unit
  FROM pedido_items pi
  JOIN products pr ON pr.id = pi.product_id
  WHERE pi.pedido_id = ?
");
$lines->execute([$pedidoId]);
$items = $lines->fetchAll();

// 5) Calcular totales (puedes usar los campos de pedidos si ya los llenas)
$subtotal = 0;
$iva_total = 0;
$total    = 0;
$savingsTotal = 0;
foreach ($items as $it) {
    $sub   = $it['quantity'] * $it['price'];
    $iva   = round($sub * 0.16, 2);
    $tot   = round($sub + $iva, 2);
    $subtotal += $sub;
    $iva_total += $iva;
    $total    += $tot;
    $savingsTotal += $it['savings_total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle Pedido #<?= $pedidoId ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    table { width:100%; border-collapse:collapse; margin-top:1rem; }
    th, td { padding:0.6rem; border:1px solid #ccc; text-align:center; }
    th { background:#eee; }
    tfoot th, tfoot td { font-weight:bold; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="main">
    <a href="../dashboard/compras.php" class="btn">← Volver al Menú</a>
    <h1>Pedido #<?= $pedidoId ?></h1>
    <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente']) ?></p>
    <p><strong>Fecha:</strong> <?= $pedido['created_at'] ?></p>

    <table>
      <thead>
        <tr>
          <th>Producto</th>
          <th>Cant.</th>
          <th>Precio Base</th>
          <th>Precio Cliente</th>
          <th>% Desc.</th>
          <th>Ahorro U.</th>
          <th>Ahorro T.</th>
          <th>Sub-Total</th>
          <th>IVA (16%)</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <?php
          $sub   = $it['quantity'] * $it['price'];
          $iva   = round($sub * 0.16, 2);
          $tot   = round($sub + $iva, 2);
        ?>
        <tr>
          <td><?= htmlspecialchars($it['name']) ?></td>
          <td><?= $it['quantity'] ?></td>
          <td>$<?= number_format($it['base_price'],2) ?></td>
          <td>$<?= number_format($it['price'],     2) ?></td>
          <td><?=      $it['discount_pct'] ?> %</td>
          <td>$<?= number_format($it['savings_unit'],  2) ?></td>
          <td>$<?= number_format($it['savings_total'], 2) ?></td>
          <td>$<?= number_format($sub, 2) ?></td>
          <td>$<?= number_format($iva, 2) ?></td>
          <td>$<?= number_format($tot, 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="6" style="text-align:right">Total Ahorro:</td>
          <td>$<?= number_format($savingsTotal, 2) ?></td>
          <td>$<?= number_format($subtotal,     2) ?></td>
          <td>$<?= number_format($iva_total,    2) ?></td>
          <td>$<?= number_format($total,        2) ?></td>
        </tr>
      </tfoot>
    </table>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
