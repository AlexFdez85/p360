<?php
// p360/dashboard/create_order.php
// ───────────────────────────────────────────────────────────────────────────────
//  • Aplica el % de descuento que tengas en price_lists.discount_pct
//  • Calcula el precio cliente = precio lista – descuento
//  • Muestra la columna Descuento y usa el precio con descuento en el resumen
// ───────────────────────────────────────────────────────────────────────────────

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config.php';

/* 1) Validar sesión y rol */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'compras') {
    header('Location: ../auth/login.php');
    exit;
}
$userId    = $_SESSION['user_id'];
$companyId = $_SESSION['company_id'];

/* 2) Traer productos con % de descuento de la empresa                */
/*    ( price  = precio lista ;  discount_pct = % que se descuenta )  */
$stmt = $pdo->prepare("
    SELECT  p.id,
            p.name,
            p.unit,
            pl.price                       AS list_price,
            COALESCE(pl.discount_pct,0)    AS discount_pct,
            (pl.price * (1 - COALESCE(pl.discount_pct,0)/100)) AS client_price
    FROM products        p
    JOIN price_lists     pl ON pl.product_id = p.id
                           AND pl.company_id = :cid
    ORDER BY p.name
");
$stmt->execute([':cid' => $companyId]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 3) Procesar envío de formulario */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* 3.1) Cantidades */
    $lines = [];
    foreach ($_POST['qty'] ?? [] as $pid => $q) {
        $pid = (int)$pid;         // producto
        $q   = (int)$q;           // cantidad
        if ($q > 0) $lines[$pid] = $q;
    }
    $comment = trim($_POST['customer_comment'] ?? '');

    if (empty($lines)) {
        $error = 'Debes indicar al menos una cantidad mayor a cero.';
    } else {

        /* 3.2) Mapas de precios */
        $unitPrices   = [];   // precio cliente (ya con descuento)
        $listPrices   = [];   // precio lista
        $discPcts     = [];   // % descuento
        foreach ($products as $p) {
            $unitPrices[$p['id']] = (float)$p['client_price'];
            $listPrices[$p['id']] = (float)$p['list_price'];
            $discPcts[$p['id']]   = (float)$p['discount_pct'];
        }

        /* 3.3) Sub‑total */
        $subtotal = 0;
        foreach ($lines as $pid => $q) {
            if (!isset($unitPrices[$pid])) {
                $error = "El producto ID $pid no tiene precio asignado.";
                break;
            }
            $subtotal += $unitPrices[$pid] * $q;
        }

        if (!$error) {
            $iva   = $subtotal * 0.16;
            $total = $subtotal + $iva;

            /* 3.4) Guardar pedido */
            $insO = $pdo->prepare("
                INSERT INTO orders
                      (company_id,user_id,status,financial_status,
                       subtotal,iva,total,customer_comments,visible,created_at)
                VALUES(?, ?, 'creado','Pedido', ?, ?, ?, ?, 1, NOW())
            ");
            $insO->execute([$companyId,$userId,$subtotal,$iva,$total,$comment]);
            $orderId = $pdo->lastInsertId();

            /* 3.5) Guardar líneas */
            $insI = $pdo->prepare("
                INSERT INTO order_items
                      (order_id,product_id,quantity,unit_price,
                       discount_pct,subtotal)
                VALUES(?, ?, ?, ?, ?, ?)
            ");
            foreach ($lines as $pid => $q) {
                $pr = $unitPrices[$pid];                // P.U. con dto
                $insI->execute([
                    $orderId,
                    $pid,
                    $q,
                    $pr,
                    $discPcts[$pid],                    // % descuento
                    $pr * $q
                ]);
            }
            header('Location: compras.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear Pedido | Compras</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .btn{display:inline-block;background:#c00;color:#fff;padding:.5rem 1rem;text-decoration:none;border-radius:4px}
    .error{color:red;margin-bottom:1em}
    .table-responsive{overflow-x:auto}
    table{width:100%;border-collapse:collapse;margin-bottom:1em}
    th,td{border:1px solid #ddd;padding:.5rem;text-align:left}
    th{background:#f5f5f5}
    #order-summary{max-width:600px;margin:1em 0;padding:1em;background:#fafafa;border:1px solid #ccc;border-radius:4px}
  </style>
</head>
<body>
<?php include __DIR__.'/../includes/header.php'; ?>

<main class="main">
  <a href="compras.php" class="btn">← Volver a Mis Pedidos</a>
  <h1>Crear Nuevo Pedido</h1>

  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <?php if (empty($products)): ?>
    <p>No hay productos con precio asignado para tu empresa.</p>
  <?php else: ?>
  <form method="POST" id="order-form">
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Unidad</th>
            <th>Precio Lista</th>
            <th>% Dto.</th>
            <th>Precio Cliente</th>
            <th>Cantidad</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p):
              $clientPrice = $p['client_price']; ?>
          <tr data-price="<?= $clientPrice ?>">
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['unit']) ?></td>
            <td><?= '$'.number_format($p['list_price'],2) ?></td>
            <td><?= number_format($p['discount_pct'],2).' %' ?></td>
            <td><?= '$'.number_format($clientPrice,2) ?></td>
            <td>
              <input type="number" name="qty[<?= $p['id'] ?>]"
                     value="0" min="0" style="width:4em;">
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div id="order-summary">
      <p>Subtotal: <strong id="sum-subtotal">$0.00</strong></p>
      <p>IVA (16%): <strong id="sum-iva">$0.00</strong></p>
      <p>Total: <strong id="sum-total">$0.00</strong></p>
    </div>

    <label>
      Comentarios u Observaciones:<br>
      <textarea name="customer_comment" rows="3"
                style="width:100%;max-width:600px;"><?= htmlspecialchars($_POST['customer_comment'] ?? '') ?></textarea>
    </label><br><br>

    <button type="submit" class="btn">Generar Pedido</button>
  </form>
  <?php endif; ?>
</main>

<?php include __DIR__.'/../includes/footer.php'; ?>

<script>
const fmt = new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'});

function updateSummary(){
  let subtotal = 0;
  document.querySelectorAll('tbody tr').forEach(tr=>{
    const price = +tr.dataset.price || 0;
    const qty   = +tr.querySelector('input').value || 0;
    subtotal += price * qty;
  });
  const iva   = subtotal * 0.16;
  const total = subtotal + iva;
  document.getElementById('sum-subtotal').textContent = fmt.format(subtotal);
  document.getElementById('sum-iva').textContent      = fmt.format(iva);
  document.getElementById('sum-total').textContent    = fmt.format(total);
}

document.querySelectorAll('input[type=number]').forEach(inp=>{
  inp.addEventListener('input',updateSummary);
});
updateSummary();
</script>
</body>
</html>






