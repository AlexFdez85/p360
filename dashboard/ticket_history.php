<!DOCTYPE html>
<html lang="es"><head>
  <meta charset="UTF-8"><title>Historial de Tickets</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<?php
session_start(); require_once __DIR__.'/../config.php';
ini_set('display_errors',1); error_reporting(E_ALL);
if (empty($_SESSION['user_id'])||$_SESSION['role']!=='operativas'){
  header('Location:../auth/login.php');exit;
}
require __DIR__.'/../includes/header.php';

$sql="SELECT t.id,t.subject,t.phone,t.priority,t.status,t.created_at FROM tickets t WHERE t.user_id=? ORDER BY t.created_at DESC";
$stmt=$pdo->prepare($sql); $stmt->execute([$_SESSION['user_id']]);
$tickets=$stmt->fetchAll();
?>
<main class="main container">
  <a href="operativas.php" class="btn">← Menú</a>
  <div class="card mt-4 p-4">
    <h1>Historial de Tickets</h1>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>ID</th><th>Título</th><th>Teléfono</th><th>Prioridad</th><th>Estatus</th><th>Fecha</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php if (!$tickets): ?><tr><td colspan="7">Sin tickets.</td></tr><?php else:
            foreach($tickets as $t): ?>
            <tr>
              <td><?=$t['id']?></td><td><?=htmlspecialchars($t['subject'])?></td><td><?=htmlspecialchars($t['phone'])?></td>
              <td><?=$t['priority']?></td><td><?=$t['status']?></td>
              <td><?=date('d/m/Y H:i',strtotime($t['created_at']))?></td>
              <td><a href="ticket_detail.php?id=<?=$t['id']?>" class="btn btn-sm btn-info">Ver</a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php require __DIR__.'/../includes/footer.php'; ?>
