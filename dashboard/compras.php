<?php
// p360/dashboard/compras.php
// ─────────────────────────────────────────────────────────────────────────────
//  Muestra SIEMPRE el último estatus operativo y financiero (igual que en admin)
//  sin perder la lógica de “Ocultar / Mostrar archivados”.
// ─────────────────────────────────────────────────────────────────────────────

session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'compras') {
    header('Location: ../auth/login.php');
    exit;
}

$userId    = $_SESSION['user_id'];
$companyId = $_SESSION['company_id'];

/* ───── 1)  Archivar (ocultar) pedido ─────────────────────────────────────── */
if (isset($_GET['ocultar_id'])) {
    $ocultarId = (int)$_GET['ocultar_id'];
    $pdo->prepare("
        UPDATE orders
           SET visible = 0
         WHERE id = ?
           AND company_id = ?
           AND status = 'entregado'
           AND LOWER(financial_status) = 'pagado'
    ")->execute([$ocultarId, $companyId]);
    header('Location: compras.php');
    exit;
}

/* ───── 2)  Listado con último estatus desde order_status_history ────────── */
$mostrarOcultos = isset($_GET['mostrar_ocultos']) && $_GET['mostrar_ocultos'] == '1';

$sql = "
SELECT
    o.id,
    o.created_at,
    o.total,
    COALESCE(h.new_status,      o.status)          AS status,
    COALESCE(h.new_fin_status,  o.financial_status) AS financial_status,
    o.visible
FROM orders o

/* sub consulta: registro más reciente por pedido */
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
) h ON h.order_id = o.id
/* ------------------------------------------------ */

WHERE o.company_id = :company_id
" . (!$mostrarOcultos ? " AND o.visible = 1 " : "") . "
ORDER BY o.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':company_id' => $companyId]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis Pedidos</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .btn{display:inline-block;background:#c00;color:#fff;padding:.5rem 1rem;text-decoration:none;border-radius:4px;margin:0 5px 1em 0}
    .pedido-oculto{background:#f9f9f9;color:#999}
    table{width:100%;border-collapse:collapse;margin-bottom:1em}
    th,td{border:1px solid #ddd;padding:.5rem;text-align:left}
    th{background:#f5f5f5}
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="main">
  <h1>Mis Pedidos</h1>

  <div style="margin-bottom:1em;">
    <a href="create_order.php" class="btn">+ Crear Pedido</a>
    <a href="?mostrar_ocultos=<?= $mostrarOcultos ? '0' : '1' ?>" class="btn">
      <?= $mostrarOcultos ? 'Ocultar archivados' : 'Mostrar archivados' ?>
    </a>
  </div>

  <?php if (!$pedidos): ?>
    <p>No hay pedidos para mostrar.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Fecha</th>
          <th>Total</th>
          <th>Estado</th>
          <th>Financiero</th>
          <th>Visibilidad</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($pedidos as $p): ?>
        <?php
          $puedeOcultar = $p['visible']
                       && $p['status'] === 'entregado'
                       && strtolower($p['financial_status']) === 'pagado';
        ?>
        <tr class="<?= $p['visible'] ? '' : 'pedido-oculto' ?>">
          <td><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['created_at']) ?></td>
          <td>$<?= number_format($p['total'],2) ?></td>
          <td><?= htmlspecialchars($p['status'] ?? '—') ?></td>
          <td><?= htmlspecialchars($p['financial_status'] ?? '—') ?></td>
          <td><?= $p['visible'] ? 'Visible' : 'Archivado' ?></td>
          <td>
            <a href="order_detail.php?id=<?= $p['id'] ?>" style="color:red;">Ver detalles</a>
            <?php if ($puedeOcultar): ?>
              &nbsp;|&nbsp;
              <a href="?ocultar_id=<?= $p['id'] ?>" style="color:red;">Ocultar</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
