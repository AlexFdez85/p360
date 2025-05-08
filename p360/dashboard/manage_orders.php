<?php
// p360/dashboard/manage_orders.php
// ─────────────────────────────────────────────────────────────────────────────
//  • Ahora la columna “Operativo” (y “Financiero”) siempre muestra el ÚLTIMO
//    estatus registrado en order_status_history.
//  • Si todavía no existe historial, se muestra el valor que vive en orders.
// ─────────────────────────────────────────────────────────────────────────────

session_start();
require_once __DIR__ . '/../config.php';

/* 1) Solo admin */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

/* 2) Pedidos visibles con su último estatus operativo y financiero         */
/*    (usa LEFT JOIN a una sub‑consulta que localiza la fila más reciente)  */
$sql = "
SELECT
    o.id,
    c.name                                         AS empresa,
    u.name                                         AS usuario,
    COALESCE(last_h.new_status,      o.status)     AS operativo,
    COALESCE(last_h.new_fin_status,  o.financial_status) AS financiero,
    o.total
FROM orders o
JOIN companies c ON c.id = o.company_id
JOIN users     u ON u.id = o.user_id

/* sub‑consulta: último registro de historial por pedido ------------------ */
LEFT JOIN (
    SELECT h.order_id,
           h.new_status,
           h.new_fin_status
    FROM order_status_history h
    INNER JOIN (
        SELECT order_id, MAX(id) AS max_id
        FROM order_status_history
        GROUP BY order_id
    ) m ON m.order_id = h.order_id AND m.max_id = h.id
) last_h ON last_h.order_id = o.id
/* ----------------------------------------------------------------------- */

WHERE o.visible = 1
ORDER BY o.created_at DESC
";
$stmt   = $pdo->query($sql);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Admon de Pedidos</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .table-responsive{overflow-x:auto}
    table{width:100%;border-collapse:collapse;margin-bottom:1em}
    th,td{border:1px solid #ddd;padding:.5rem;text-align:left}
    th{background:#f5f5f5}
  </style>
</head>
<body>
<?php include __DIR__.'/../includes/header.php'; ?>

<main class="main">
  <h1>Admon de Pedidos</h1>

  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Empresa</th>
          <th>Usuario</th>
          <th>Operativo</th>
          <th>Financiero</th>
          <th>Total</th>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$orders): ?>
          <tr><td colspan="7">No hay pedidos.</td></tr>
        <?php else: foreach ($orders as $o): ?>
          <tr>
            <td><?= $o['id'] ?></td>
            <td><?= htmlspecialchars($o['empresa']) ?></td>
            <td><?= htmlspecialchars($o['usuario']) ?></td>
            <td><?= ucfirst(htmlspecialchars($o['operativo']   ?? '—')) ?></td>
            <td><?= ucfirst(htmlspecialchars($o['financiero'] ?? '—')) ?></td>
            <td>$<?= number_format($o['total'],2) ?></td>
            <td><a href="order_detail_admin.php?id=<?= $o['id'] ?>" style="color:red;">Ver detalle</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>
