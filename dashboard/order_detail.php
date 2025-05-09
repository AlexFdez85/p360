<?php
session_start();
require_once __DIR__ . '/../config.php';

// Mostrar errores (solo en desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1) Permitir solo rol “compras”
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'compras') {
    header('Location: ../auth/login.php');
    exit;
}

// 2) ID de pedido
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('ID inválido.');
}

// 3) Eliminar comprobante de pago si solicita el cliente
if (isset($_GET['delete_payment'])) {
    $path = $pdo->query("SELECT payment_proof FROM orders WHERE id = {$id}")
                ->fetchColumn();
    if ($path && file_exists(__DIR__ . "/../{$path}")) {
        unlink(__DIR__ . "/../{$path}");
    }
    $pdo->prepare("UPDATE orders SET payment_proof = NULL WHERE id = ?")
        ->execute([$id]);
    header("Location: order_detail.php?id={$id}");
    exit;
}

// 4) Procesar subida de comprobante de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['payment_proof'])) {
    if ($_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $pname = time() . '_' . basename($_FILES['payment_proof']['name']);
        $dest  = "uploads/payments/{$pname}";
        move_uploaded_file($_FILES['payment_proof']['tmp_name'],
                          __DIR__ . "/../{$dest}");
        $pdo->prepare("UPDATE orders SET payment_proof = ? WHERE id = ?")
            ->execute([$dest, $id]);
    }
    header("Location: order_detail.php?id={$id}");
    exit;
}

// 5) Cargar datos del pedido (verificación que sea del mismo cliente)
$stmt = $pdo->prepare("
    SELECT o.*, c.name AS company_name, u.name AS user_name
    FROM orders o
    JOIN companies c ON c.id = o.company_id
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ? AND o.user_id = ?
    LIMIT 1
");
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC) ?: die('Pedido no encontrado.');

// 6) Cargar partidas
$itemStmt = $pdo->prepare("
    SELECT oi.quantity, oi.unit_price, oi.discount_pct, oi.subtotal,
           p.name AS product_name
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// 7) Cargar historial de estados
$histStmt = $pdo->prepare("
    SELECT changed_at, old_status, new_status, comment, changed_by
    FROM order_status_history
    WHERE order_id = ?
    ORDER BY changed_at ASC
");
$histStmt->execute([$id]);
$history = $histStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pedido #<?= htmlspecialchars($order['id']) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="main container py-4">
    <a href="compras.php" class="btn btn-secondary mb-3">← Mis Pedidos</a>

    <div class="card p-4">
      <h1>Pedido #<?= htmlspecialchars($order['id']) ?></h1>
      <p><strong>Empresa:</strong> <?= htmlspecialchars($order['company_name']) ?></p>
      <p><strong>Usuario:</strong> <?= htmlspecialchars($order['user_name']) ?></p>

      <div class="table-responsive mt-3">
        <table class="table">
          <thead><tr>
            <th>Producto</th><th>Cantidad</th><th>Precio</th><th>Desc %</th><th>Subtotal</th>
          </tr></thead>
          <tbody>
            <?php if (empty($items)): ?>
              <tr><td colspan="5">No hay partidas.</td></tr>
            <?php else: foreach ($items as $it): ?>
              <tr>
                <td><?= htmlspecialchars($it['product_name']) ?></td>
                <td><?= $it['quantity'] ?></td>
                <td>$<?= number_format($it['unit_price'],2) ?></td>
                <td><?= number_format($it['discount_pct'],2) ?>%</td>
                <td>$<?= number_format($it['subtotal'],2) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <p class="mt-3"><strong>Subtotal:</strong> $<?= number_format($order['subtotal'],2) ?></p>
      <p><strong>IVA (16%):</strong> $<?= number_format($order['iva'],2) ?></p>
      <p><strong>Total:</strong> $<?= number_format($order['total'],2) ?></p>

      <!-- Factura subida por admin (solo ver) -->
      <hr><h2>Factura</h2>
      <?php if (!empty($order['invoice_file'])): ?>
        <p>
          <a href="/p360/<?= htmlspecialchars($order['invoice_file']) ?>" target="_blank">
            Ver factura
          </a>
        </p>
      <?php else: ?>
        <p>No hay factura cargada.</p>
      <?php endif; ?>

      <!-- Subir / Eliminar comprobante de pago -->
      <hr><h2>Comprobante de Pago</h2>
      <?php if (empty($order['payment_proof'])): ?>
        <form method="post" enctype="multipart/form-data" class="mb-3">
          <input type="file" name="payment_proof" class="form-control-file mb-2" required>
          <button class="btn btn-primary">Subir comprobante de pago</button>
        </form>
      <?php else: ?>
        <p>
          <a href="/p360/<?= htmlspecialchars($order['payment_proof']) ?>"
             target="_blank">Ver comprobante de pago</a>
          <a href="?id=<?= $id ?>&delete_payment=1" class="text-danger ml-2">✖ Eliminar</a>
        </p>
      <?php endif; ?>

      <!-- Mis Comentarios u Observaciones -->
      <hr><h2>Mis Comentarios u Observaciones</h2>
      <div class="box">
        <?= nl2br(htmlspecialchars($order['customer_comments'] ?? '—')) ?>
      </div>

      <!-- Historial de Estados -->
      <hr><h2>Historial de Estados</h2>
      <div class="table-responsive">
        <table class="table">
          <thead><tr>
            <th>Fecha</th><th>De</th><th>A</th><th>Usuario</th><th>Comentario</th>
          </tr></thead>
          <tbody>
            <?php if (empty($history)): ?>
              <tr><td colspan="5">Sin historial de cambios.</td></tr>
            <?php else: foreach ($history as $h): ?>
              <tr>
                <td><?= htmlspecialchars($h['changed_at']) ?></td>
                <td><?= htmlspecialchars($h['old_status']) ?></td>
                <td><?= htmlspecialchars($h['new_status']) ?></td>
                <td><?= htmlspecialchars($h['changed_by']) ?></td>
                <td><?= nl2br(htmlspecialchars($h['comment'] ?? '—')) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
