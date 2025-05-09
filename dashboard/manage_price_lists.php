<?php
// dashboard/manage_price_lists.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config.php';

// 1) Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Helper de escape
function e($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// 2) Acción a ejecutar
$action = $_GET['action'] ?? 'list';

// 3) Procesar eliminación
if ($action === 'delete' && !empty($_GET['id'])) {
    $id = (int) $_GET['id'];
    $del = $pdo->prepare("DELETE FROM price_lists WHERE id = ?");
    $del->execute([$id]);
    header('Location: manage_price_lists.php?msg=deleted');
    exit;
}

// 4) Procesar alta/edición (igual que antes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyId   = (int) $_POST['company_id'];
    $productId   = (int) $_POST['product_id'];
    $clientPrice = (float) $_POST['price'];

    // obtener precio base
    $stmt = $pdo->prepare("SELECT base_price FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $basePrice = (float) $stmt->fetchColumn();

    // calcular descuento
    $discountPct = $basePrice > 0
        ? round((1 - $clientPrice / $basePrice) * 100, 2)
        : 0.00;

    if (!empty($_POST['id'])) {
        // edición
        $stmt = $pdo->prepare("
          UPDATE price_lists
          SET company_id = ?, product_id = ?, price = ?, discount_pct = ?
          WHERE id = ?
        ");
        $stmt->execute([
            $companyId,
            $productId,
            $clientPrice,
            $discountPct,
            (int)$_POST['id']
        ]);
        header('Location: manage_price_lists.php?msg=updated');
    } else {
        // alta
        $stmt = $pdo->prepare("
          INSERT INTO price_lists (company_id, product_id, price, discount_pct)
          VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $companyId,
            $productId,
            $clientPrice,
            $discountPct
        ]);
        header('Location: manage_price_lists.php?msg=added');
    }
    exit;
}

// 5) Preparar datos para la vista
$companies = $pdo->query("SELECT id, name FROM companies ORDER BY name")->fetchAll();
$products  = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll();

if ($action === 'edit' && !empty($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM price_lists WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $current = $stmt->fetch();
    if (!$current) {
        header('Location: manage_price_lists.php?msg=notfound');
        exit;
    }
}

if ($action === 'list') {
    $lists = $pdo->query("
      SELECT pl.id, c.name AS company, p.name AS product,
             pl.price, pl.discount_pct, pl.created_at
      FROM price_lists pl
      JOIN companies c ON c.id = pl.company_id
      JOIN products p  ON p.id = pl.product_id
      ORDER BY c.name, p.name
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listas de Precio | Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>
  <main class="main">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h1>Listas de Precio</h1>
      <a href="../dashboard/admin.php" class="btn">← Volver al Menú</a>
    </div>

    <?php if ($action === 'list'): ?>
      <?php if (!empty($_GET['msg'])): ?>
        <p class="info">
          <?php if ($_GET['msg']==='added') echo 'Lista creada.'; ?>
          <?php if ($_GET['msg']==='updated') echo 'Lista actualizada.'; ?>
          <?php if ($_GET['msg']==='deleted') echo 'Lista eliminada.'; ?>
          <?php if ($_GET['msg']==='notfound') echo 'Lista no encontrada.'; ?>
        </p>
      <?php endif; ?>

      <a href="?action=add" class="btn" style="margin-bottom:1rem;">+ Nueva Lista de Precio</a>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Empresa</th>
              <th>Producto</th>
              <th>Precio Cliente</th>
              <th>% Descuento</th>
              <th>Creado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lists as $pl): ?>
            <tr>
              <td><?= e($pl['company']) ?></td>
              <td><?= e($pl['product']) ?></td>
              <td>$<?= number_format($pl['price'],2) ?></td>
              <td><?= number_format($pl['discount_pct'],2) ?>%</td>
              <td><?= $pl['created_at'] ?></td>
              <td class="actions">
                <a href="?action=edit&id=<?= $pl['id'] ?>">Editar</a>
                <a href="?action=delete&id=<?= $pl['id'] ?>"
                   onclick="return confirm('¿Eliminar esta lista?');">
                  Eliminar
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($lists)): ?>
            <tr><td colspan="6">No hay listas de precio.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    <?php elseif (in_array($action, ['add','edit'])): 
      $isEdit = $action==='edit';
      $title  = $isEdit ? 'Editar Lista de Precio' : 'Nueva Lista de Precio';
      $idVal  = $isEdit ? (int)$current['id'] : '';
      $selComp= $isEdit ? (int)$current['company_id'] : '';
      $selProd= $isEdit ? (int)$current['product_id'] : '';
      $priceVal = $isEdit ? $current['price'] : '';
    ?>
      <h2><?= $title ?></h2>
      <form method="POST">
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= $idVal ?>">
        <?php endif; ?>

        <label>
          Empresa:<br>
          <select name="company_id" required>
            <option value="">— Selecciona —</option>
            <?php foreach ($companies as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']==$selComp?'selected':''?>>
              <?= e($c['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          Producto:<br>
          <select name="product_id" required>
            <option value="">— Selecciona —</option>
            <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $p['id']==$selProd?'selected':''?>>
              <?= e($p['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          Precio Cliente:<br>
          <input type="number" name="price" step="0.01" required value="<?= $priceVal ?>">
        </label>
        <button type="submit"><?= $isEdit ? 'Actualizar' : 'Crear' ?></button>
        <a href="manage_price_lists.php" class="btn" style="margin-left:1rem;">Cancelar</a>
      </form>
    <?php endif; ?>
  </main>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
