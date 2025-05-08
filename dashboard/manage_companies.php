<?php
// dashboard/manage_companies.php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit;
}

$action = $_GET['action'] ?? 'list';
function e($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}

// CRUD
if ($action==='add' && $_POST){
  $name = trim($_POST['name']);
  $pdo->prepare("INSERT INTO companies (name) VALUES (?)")
      ->execute([$name]);
  header('Location: manage_companies.php?msg=added'); exit;
}
if ($action==='edit' && isset($_GET['id'])){
  $id = (int)$_GET['id'];
  if ($_POST){
    $pdo->prepare("UPDATE companies SET name=? WHERE id=?")
        ->execute([trim($_POST['name']), $id]);
    header('Location: manage_companies.php?msg=updated'); exit;
  } else {
    $company = $pdo->prepare("SELECT * FROM companies WHERE id=?")
                    ->execute([$id]);
    $company = $pdo->prepare("SELECT * FROM companies WHERE id=?")->execute([$id]);
    $company = $pdo->query("SELECT * FROM companies WHERE id=$id")->fetch();
    if (!$company) { header('Location: manage_companies.php?msg=notfound'); exit; }
  }
}
if ($action==='delete' && isset($_GET['id'])){
  $pdo->prepare("DELETE FROM companies WHERE id=?")
      ->execute([(int)$_GET['id']]);
  header('Location: manage_companies.php?msg=deleted'); exit;
}
if ($action==='list'){
  $list = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();
}
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><title>Empresas | Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<?php include __DIR__.'/../includes/header.php'; ?>
<main class="main">
  <div style="display:flex;justify-content:space-between">
    <h1>Empresas</h1>
    <a href="../dashboard/admin.php" class="btn">← Menú</a>
  </div>
  <?php if($action==='list'): ?>
    <a href="?action=add" class="btn">+ Nueva Empresa</a>
    <?php if(!empty($_GET['msg'])): ?>
      <p class="info">Operación: <?=e($_GET['msg'])?></p>
    <?php endif; ?>
    <table><thead>
      <tr><th>Empresa</th><th>Creada</th><th>Acciones</th></tr>
    </thead><tbody>
    <?php foreach($list as $c): ?>
      <tr>
        <td><?=e($c['name'])?></td>
        <td><?=$c['created_at']?></td>
        <td>
          <a href="?action=edit&id=<?=$c['id']?>">Editar</a>
          <a href="?action=delete&id=<?=$c['id']?>"
             onclick="return confirm('Eliminar empresa?')">Eliminar</a>
          <a href="manage_company_users.php?company_id=<?=$c['id']?>">
            Usuarios
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>

  <?php elseif(in_array($action,['add','edit'])): 
    $isEdit = $action==='edit';
    $title = $isEdit?'Editar Empresa':'Nueva Empresa';
  ?>
    <h2><?= $title ?></h2>
    <form method="POST">
      <label>Nombre de la empresa:
        <input type="text" name="name" required
          value="<?= $isEdit?e($company['name']):'' ?>">
      </label><br><br>
      <button type="submit"><?= $isEdit?'Actualizar':'Crear' ?></button>
      <a href="manage_companies.php" class="btn">Cancelar</a>
    </form>
  <?php endif; ?>
</main>
<?php include __DIR__.'/../includes/footer.php'; ?>
</body></html>
