<?php
session_start();
require_once __DIR__ . '/../config.php';

// Mostrar errores en desarrollo
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1) Solo rol “admin”
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// 2) ID de pedido
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('ID inválido.');
}

// 3) Eliminar archivos si se solicita
if (isset($_GET['delete_invoice'])) {
    $path = $pdo->query("SELECT invoice_file FROM orders WHERE id = {$id}")
                ->fetchColumn();
    if ($path && file_exists(__DIR__ . "/../{$path}")) {
        unlink(__DIR__ . "/../{$path}");
    }
    $pdo->prepare("UPDATE orders SET invoice_file = NULL WHERE id = ?")
        ->execute([$id]);
    header("Location: order_detail_admin.php?id={$id}");
    exit;
}
if (isset($_GET['delete_payment'])) {
    $path = $pdo->query("SELECT payment_proof FROM orders WHERE id = {$id}")
                ->fetchColumn();
    if ($path && file_exists(__DIR__ . "/../{$path}")) {
        unlink(__DIR__ . "/../{$path}");
    }
    $pdo->prepare("UPDATE orders SET payment_proof = NULL WHERE id = ?")
        ->execute([$id]);
    header("Location: order_detail_admin.php?id={$id}");
    exit;
}

// 4) Procesar POST (subida + estados)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Subir factura
    if (!empty($_FILES['invoice']) && $_FILES['invoice']['error'] === UPLOAD_ERR_OK) {
        $fname = time() . '_' . basename($_FILES['invoice']['name']);
        $dest  = "uploads/invoices/{$fname}";
        move_uploaded_file($_FILES['invoice']['tmp_name'], __DIR__ . "/../{$dest}");
        $pdo->prepare("UPDATE orders SET invoice_file = ? WHERE id = ?")
            ->execute([$dest, $id]);
    }
    // Subir comprobante de pago
    if (!empty($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $pname = time() . '_' . basename($_FILES['payment_proof']['name']);
        $dest2 = "uploads/payments/{$pname}";
        move_uploaded_file($_FILES['payment_proof']['tmp_name'], __DIR__ . "/../{$dest2}");
        $pdo->prepare("UPDATE orders SET payment_proof = ? WHERE id = ?")
            ->execute([$dest2, $id]);
    }
    // Estados y comentario
    $newOpStatus  = $_POST['status'] ?? '';
    $newFinStatus = $_POST['financial_status'] ?? '';
    $adminComment = trim($_POST['admin_comment'] ?? '');

    $pdo->prepare("UPDATE orders SET status = ?, financial_status = ? WHERE id = ?")
        ->execute([$newOpStatus, $newFinStatus, $id]);

    if ($adminComment !== '') {
        $uStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $uStmt->execute([$_SESSION['user_id']]);
        $author = $uStmt->fetchColumn() ?: 'Administrador';

        $pdo->prepare("
            INSERT INTO order_status_history
              (order_id, old_status, new_status, comment, changed_by, changed_at)
            VALUES (?, '', ?, ?, ?, NOW())
        ")->execute([$id, $newOpStatus, $adminComment, $_SESSION['user_id']]);
    }

    header("Location: order_detail_admin.php?id={$id}");
    exit;
}

// 5) Cargar datos del pedido
$stmt = $pdo->prepare("
    SELECT o.*, c.name AS company_name, u.name AS user_name
    FROM orders o
    JOIN companies c ON c.id = o.company_id
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
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
  <title>Pedido #<?= htmlspecialchars($order['id']) ?> — Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="main container py-4">
    <a href="manage_orders.php" class="btn btn-secondary mb-3">← Volver a Pedidos</a>

    <div class="card p-4">
      <h1>Pedido #<?= htmlspecialchars($order['id']) ?> — <?= htmlspecialchars(ucfirst($order['status'])) ?></h1>
      <p><strong>Empresa:</strong> <?= htmlspecialchars($order['company_name']) ?></p>
      <p><strong>Usuario:</strong> <?= htmlspecialchars($order['user_name']) ?></p>
      <p><strong>Comentario del Cliente:</strong><br><?= nl2br(htmlspecialchars($order['customer_comments'] ?? '—')) ?></p>
      <p><strong>Estado Financiero:</strong> <?= htmlspecialchars($order['financial_status']) ?></p>

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

      <p class="mt-3"><strong>Total:</strong> $<?= number_format($order['total'],2) ?></p>

      <!-- Subir Factura -->
      <hr><h2>Subir Factura</h2>
      <?php if (empty($order['invoice_file'])): ?>
        <form method="post" enctype="multipart/form-data" class="mb-3">
          <input type="file" name="invoice" class="form-control-file mb-2">
          <button class="btn btn-primary">Subir factura</button>
        </form>
      <?php else: ?>
        <p>
          <a href="/p360/<?= htmlspecialchars($order['invoice_file']) ?>" target="_blank">Ver factura</a>
          <a href="?id=<?= $id ?>&delete_invoice=1" class="text-danger ml-2">✖ Eliminar</a>
        </p>
      <?php endif; ?>

      <!-- Subir Comprobante de Pago -->
      <hr><h2>Subir Comprobante de Pago</h2>
      <?php if (empty($order['payment_proof'])): ?>
        <form method="post" enctype="multipart/form-data" class="mb-3">
          <input type="file" name="payment_proof" class="form-control-file mb-2">
          <button class="btn btn-primary">Subir comprobante de pago</button>
        </form>
      <?php else: ?>
        <p>
          <a href="/p360/<?= htmlspecialchars($order['payment_proof']) ?>" target="_blank">Ver comprobante de pago</a>
          <a href="?id=<?= $id ?>&delete_payment=1" class="text-danger ml-2">✖ Eliminar</a>
        </p>
      <?php endif; ?>

      <!-- Cambiar estados -->
      <hr>
      <form method="post" class="mb-4">
        <div class="form-group">
          <label for="status">Estado Operativo</label>
          <select name="status" id="status" class="form-control">
            <?php foreach (['creado','enviado','recibido','igualacion'] as $s): ?>
              <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                <?= ucfirst($s) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group mt-2">
          <label for="financial_status">Estado Financiero</label>
          <select name="financial_status" id="financial_status" class="form-control">
            <?php foreach (['Pedido','Facturado'] as $fs): ?>
              <option value="<?= $fs ?>" <?= $order['financial_status'] === $fs ? 'selected' : '' ?>>
                <?= ucfirst($fs) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group mt-3">
          <label for="admin_comment">Comentario de seguimiento</label>
          <textarea name="admin_comment" id="admin_comment" class="form-control" rows="3"
            placeholder="Escribe tu comentario…"></textarea>
        </div>
        <button class="btn btn-success mt-3">Guardar cambios</button>
      </form>

      <!-- Historial de Estados -->
      <hr><h2>Historial de Estados</h2>
      <div class="table-responsive">
        <table class="table">
          <thead><tr>
            <th>Fecha</th><th>De</th><th>A</th><th>Comentario</th><th>Usuario</th>
          </tr></thead>
          <tbody>
            <?php if (empty($history)): ?>
              <tr><td colspan="5">Sin historial de cambios.</td></tr>
            <?php else: foreach ($history as $h): ?>
              <tr>
                <td><?= htmlspecialchars($h['changed_at']) ?></td>
                <td><?= htmlspecialchars($h['old_status']) ?></td>
                <td><?= htmlspecialchars($h['new_status']) ?></td>
                <td><?= nl2br(htmlspecialchars($h['comment'] ?? '—')) ?></td>
                <td><?= htmlspecialchars($h['changed_by']) ?></td>
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


