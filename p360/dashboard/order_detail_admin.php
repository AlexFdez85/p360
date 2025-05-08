<?php
// p360/dashboard/order_detail_admin.php

ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config.php';

// Sólo admin
if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin') {
    header('Location: ../auth/login.php'); exit;
}
$userId = $_SESSION['user_id'];

// Helper
function e($s){ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }

// ID de pedido
$orderId = (int)($_GET['id']??0);
if (!$orderId) die("ID inválido");

// Cargar pedido
$stmt = $pdo->prepare("
  SELECT o.*, c.name AS company_name, u.name AS user_name
  FROM orders o
  JOIN companies c ON c.id=o.company_id
  JOIN users    u ON u.id=o.user_id
  WHERE o.id=? LIMIT 1
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) die("Pedido no encontrado.");

// Flash
$flash = '';
if (isset($_GET['msg'])) {
  if ($_GET['msg']==='invoice')    $flash = "Factura subida correctamente.";
  elseif ($_GET['msg']==='status') $flash = "Status y comentario guardados.";
}

// Procesar formulario (invoice + estados)
if ($_SERVER['REQUEST_METHOD']==='POST') {
  // 1) Subida de factura
  if (isset($_FILES['invoice'])) {
    $f = $_FILES['invoice'];
    if ($f['error']===UPLOAD_ERR_OK
        && in_array($f['type'],['application/pdf','image/jpeg','image/png'],true)
        && $f['size']<=5*1024*1024) {
      $d=__DIR__.'/../uploads/invoices/'; if(!is_dir($d))mkdir($d,0755,true);
      $fn=time().'_'.preg_replace('/[^a-zA-Z0-9_\-\.]/','',basename($f['name']));
      if(move_uploaded_file($f['tmp_name'],$d.$fn)){
        $u=$pdo->prepare("UPDATE orders SET invoice_file=? WHERE id=?");
        $u->execute([$fn,$orderId]);
        header("Location: order_detail_admin.php?id={$orderId}&msg=invoice");
        exit;
      }
    }
  }
  // 2) Cambio de estados
  if (isset($_POST['new_status'],$_POST['new_financial_status'])) {
    $ns = $_POST['new_status'];
    $nfs= $_POST['new_financial_status'];
    $cm = trim($_POST['status_comment']);
    // Insertar historial
    $ins=$pdo->prepare("
      INSERT INTO order_status_history
      (order_id,old_status,new_status,old_fin_status,new_fin_status,comment,changed_by)
      VALUES(?,?,?,?,?,?,?)
    ");
    $ins->execute([
      $orderId,
      $order['status'],$ns,
      $order['financial_status'],$nfs,
      $cm,
      $userId
    ]);
    // Actualizar
    $upd=$pdo->prepare("
      UPDATE orders SET status=?,financial_status=? WHERE id=?
    ");
    $upd->execute([$ns,$nfs,$orderId]);
    header("Location: order_detail_admin.php?id={$orderId}&msg=status");
    exit;
  }
}

// Traer partidas
$items = $pdo->prepare("
  SELECT oi.quantity,oi.unit_price,oi.discount_pct,oi.subtotal,p.name AS product_name
  FROM order_items oi
  JOIN products p ON p.id=oi.product_id
  WHERE oi.order_id=?
");
$items->execute([$orderId]);
$items=$items->fetchAll();

// Traer historial
$history = $pdo->prepare("
  SELECT h.*,usr.name AS changer
  FROM order_status_history h
  JOIN users usr ON usr.id=h.changed_by
  WHERE h.order_id=? ORDER BY h.changed_at ASC
");
$history->execute([$orderId]);
$history=$history->fetchAll();

// Etiquetas
$flowLabels = [
  'creado'               => 'Creado',
  'recibido'             => 'Recibido',
  'en_proceso'           => 'En Proceso',
  'esperando_transporte' => 'Esperando Transporte',
  'en_ruta'              => 'En Ruta',
  'entregado'            => 'Entregado',
];
$finLabels = [
  'Pedido'            => 'Pedido',
  'Facturado'         => 'Facturado',
  'Pagado'            => 'Pagado',
  'Pendiente_de_Pago' => 'Pendiente de Pago',
  'Vencido'           => 'Vencido',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle Admin Pedido #<?=e($orderId)?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include __DIR__.'/../includes/header.php'; ?>
  <main class="main">
    <a href="manage_orders.php" class="btn">← Volver a Pedidos</a>
    <h1>Pedido #<?=e($order['id'])?> — <?=e($flowLabels[$order['status']] ?? $order['status'])?></h1>

    <?php if($flash): ?>
      <p class="info"><?=e($flash)?></p>
    <?php endif; ?>

    <p><strong>Empresa:</strong> <?=e($order['company_name'])?></p>
    <p><strong>Usuario:</strong> <?=e($order['user_name'])?></p>
    <p><strong>Comentario del Cliente:</strong><br>
      <?=nl2br(e($order['customer_comments']??'— Sin observaciones —'))?>
    </p>
    <p><strong>Estado Financiero:</strong>
      <?=e($finLabels[$order['financial_status']] ?? $order['financial_status'])?>
    </p>

    <!-- Partidas -->
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Producto</th><th>Cantidad</th><th>Precio</th>
            <th>Desc %</th><th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($items)): ?>
            <tr><td colspan="5">No hay partidas.</td></tr>
          <?php else: foreach($items as $it): ?>
            <tr>
              <td data-label="Producto"><?=e($it['product_name'])?></td>
              <td data-label="Cantidad"><?=$it['quantity']?></td>
              <td data-label="Precio">$<?=number_format($it['unit_price'],2)?></td>
              <td data-label="Desc %"><?=number_format($it['discount_pct'],2)?>%</td>
              <td data-label="Subtotal">$<?=number_format($it['subtotal'],2)?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <p><strong>Total:</strong> $<?=number_format($order['total'],2)?></p>

    <!-- Subir factura -->
    <section style="margin-top:2rem;">
      <h2>Subir Factura</h2>
      <?php if (empty($order['invoice_file'])): ?>
        <form method="POST" enctype="multipart/form-data">
          <input type="file" name="invoice" accept=".pdf,image/*" required>
          <button type="submit">Subir factura</button>
        </form>
      <?php else: ?>
        <p><a href="../uploads/invoices/<?=e($order['invoice_file'])?>" target="_blank">Ver factura</a></p>
      <?php endif; ?>
    </section>

    <!-- Cambiar estados -->
    <section style="margin-top:2rem;">
      <h2>Editar Estados</h2>
      <form method="POST">
        <label>
          Flujo operativo:
          <select name="new_status" required>
            <?php foreach($flowLabels as $key=>$lbl): ?>
              <option value="<?=$key?>" <?=$key===$order['status']?'selected':''?>>
                <?=$lbl?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <br><br>
        <label>
          Estado financiero:
          <select name="new_financial_status" required>
            <?php foreach($finLabels as $key=>$lbl): ?>
              <option value="<?=$key?>" <?=$key===$order['financial_status']?'selected':''?>>
                <?=$lbl?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <br><br>
        <label>
          Comentario:<br>
          <textarea name="status_comment" rows="3" style="width:100%;"></textarea>
        </label>
        <br>
        <button type="submit">Guardar cambios</button>
      </form>
    </section>

    <!-- Historial -->
    <section style="margin-top:2rem;">
      <h2>Historial de Cambios</h2>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Flujo: De→A</th>
              <th>Financiero: De→A</th>
              <th>Usuario</th>
              <th>Comentario</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($history)): ?>
              <tr><td colspan="5">Sin historial.</td></tr>
            <?php else: foreach($history as $h): ?>
              <tr>
                <td data-label="Fecha"><?=$h['changed_at']?></td>
                <td data-label="Flujo">
                  <?=e($flowLabels[$h['old_status']]??$h['old_status'])?>
                  → <?=e($flowLabels[$h['new_status']]??$h['new_status'])?>
                </td>
                <td data-label="Financiero">
                  <?=e($finLabels[$h['old_fin_status']]??$h['old_fin_status'])?>
                  → <?=e($finLabels[$h['new_fin_status']]??$h['new_fin_status'])?>
                </td>
                <td data-label="Usuario"><?=e($h['changer'])?></td>
                <td data-label="Comentario"><?=nl2br(e($h['comment']??'—'))?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>
  <?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>

