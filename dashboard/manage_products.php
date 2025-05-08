<?php
// dashboard/manage_products.php
require_once __DIR__ . '/../config.php';

// 1) Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// 2) Determinar acción: list, add, edit, delete
$action = $_GET['action'] ?? 'list';

// Función auxiliar de escape
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 3) Procesar acciones de formulario
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']);
    $unit       = trim($_POST['unit']);
    $base_price = floatval($_POST['base_price']);
    $stmt = $pdo->prepare("INSERT INTO products (name, unit, base_price) VALUES (?, ?, ?)");
    $stmt->execute([$name, $unit, $base_price]);
    header('Location: manage_products.php?msg=added');
    exit;

} elseif ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name       = trim($_POST['name']);
        $unit       = trim($_POST['unit']);
        $base_price = floatval($_POST['base_price']);
        $stmt = $pdo->prepare("UPDATE products SET name = ?, unit = ?, base_price = ? WHERE id = ?");
        $stmt->execute([$name, $unit, $base_price, $id]);
        header('Location: manage_products.php?msg=updated');
        exit;
    } else {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) {
            header('Location: manage_products.php?msg=notfound');
            exit;
        }
    }

} elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: manage_products.php?msg=deleted');
    exit;
}

// 4) Listar productos
if ($action === 'list') {
    $products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestionar Productos | Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    table { width:100%; border-collapse: collapse; margin-top:1rem; }
    th, td { padding:0.6rem; border:1px solid #ccc; text-align:left; }
    th { background: #eee; }
    .actions a { margin-right:0.5rem; color: var(--color-accent); }
    form { max-width:400px; margin-top:1rem; }
    form label { display:block; margin-top:1rem; font-weight:bold; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="main">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h1>Gestión de Productos</h1>
      <a href="../dashboard/admin.php" class="btn">← Volver al Menú</a>
    </div>

    <?php if ($action === 'list'): ?>
      <a href="?action=add" class="btn" style="margin-bottom:1rem;">+ Nuevo Producto</a>
      <?php if (!empty($_GET['msg'])): ?>
        <p class="info">Operación: <?= e($_GET['msg']) ?></p>
      <?php endif; ?>

      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Unidad</th>
            <th>Precio Base</th>
            <th>Sub-Total</th>
            <th>IVA (16%)</th>
            <th>Total</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): 
            $subtotal = $p['base_price'];
            // Si existen columnas generadas:
            if (isset($p['price_iva'], $p['price_total'])) {
              $iva   = $p['price_iva'];
              $total = $p['price_total'];
            } else {
              $iva   = round($subtotal * 0.16, 2);
              $total = round($subtotal + $iva,    2);
            }
          ?>
            <tr>
              <td><?= e($p['name']) ?></td>
              <td><?= e($p['unit']) ?></td>
              <td>$<?= number_format($subtotal, 2) ?></td>
              <td>$<?= number_format($subtotal, 2) ?></td>
              <td>$<?= number_format($iva,      2) ?></td>
              <td>$<?= number_format($total,    2) ?></td>
              <td class="actions">
                <a href="?action=edit&id=<?= $p['id'] ?>">Editar</a>
                <a href="?action=delete&id=<?= $p['id'] ?>"
                   onclick="return confirm('¿Eliminar este producto?');">Eliminar</a>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($products)): ?>
            <tr><td colspan="7">No hay productos registrados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

    <?php elseif ($action === 'add' || $action === 'edit'): ?>
      <?php
        $isEdit = ($action === 'edit');
        $title  = $isEdit ? 'Editar Producto' : 'Nuevo Producto';
      ?>
      <h2><?= $title ?></h2>
      <form method="POST" action="">
        <label>
          Nombre:
          <input type="text" name="name" required value="<?= $isEdit ? e($product['name']) : '' ?>">
        </label>
        <label>
          Unidad (ej. Lts, Kg):
          <input type="text" name="unit" required value="<?= $isEdit ? e($product['unit']) : '' ?>">
        </label>
        <label>
          Precio Base:
          <input type="number" name="base_price" step="0.01" required value="<?= $isEdit ? e($product['base_price']) : '' ?>">
        </label>
        <button type="submit"><?= $isEdit ? 'Actualizar' : 'Crear' ?></button>
        <a href="manage_products.php" class="btn" style="margin-left:1rem;">Cancelar</a>
      </form>
    <?php endif; ?>

