<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle y Gestión de Ticket</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php
session_start(); require_once __DIR__ . '/../config.php';
ini_set('display_errors',1); error_reporting(E_ALL);
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../auth/login.php'); exit;
}
require __DIR__ . '/../includes/header.php';
try {
  $id = isset($_GET['id'])?(int)$_GET['id']:0;
  if (!$id) throw new Exception('ID inválido');
  // Cargar ticket
  $stmt = $pdo->prepare("SELECT t.*, u.name AS cliente FROM tickets t JOIN users u ON u.id=t.user_id WHERE t.id=?");
  $stmt->execute([$id]);
  $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$ticket) throw new Exception('Ticket no hallado');
  // Adjuntos
  $attachStmt = $pdo->prepare("SELECT * FROM ticket_attachments WHERE ticket_id=?");
  $attachStmt->execute([$id]);
  $attachments = $attachStmt->fetchAll(PDO::FETCH_ASSOC);
  // Comentarios
  $commentStmt = $pdo->prepare("SELECT * FROM tickets_comments WHERE ticket_id=? ORDER BY created ASC");
  $commentStmt->execute([$id]);
  $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
  // Procesar formulario admin
  if ($_SERVER['REQUEST_METHOD']==='POST') {
    // Estado y prioridad
    $newStatus = $_POST['status'];
    $newPrio   = $_POST['priority'];
    $adminMsg  = trim($_POST['admin_comment'] ?? '');
    // Actualizar
    $upd = $pdo->prepare("UPDATE tickets SET status=?, priority=? WHERE id=?");
    $upd->execute([$newStatus, $newPrio, $id]);
    // Agregar comentario si hay
    if ($adminMsg !== '') {
      // Obtener nombre de administrador
      $uStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
      $uStmt->execute([$_SESSION['user_id']]);
      $author = $uStmt->fetchColumn() ?: 'Administrador';
      $ins = $pdo->prepare("INSERT INTO tickets_comments(ticket_id, author, comment, created) VALUES(?,?,?,NOW())");
      $ins->execute([$id, $author, $adminMsg]);
    }
    // Redirigir después de guardar
    header("Location: ticket_detail_admin.php?id=$id"); exit;("Location: ticket_detail_admin.php?id=$id"); exit;
  }
} catch(Exception $e) {
  echo '<div class="alert alert-danger m-4">Error: '.htmlspecialchars($e->getMessage()).'</div>';
  require __DIR__.'/../includes/footer.php';exit;
}
?>
<main class="main container">
  <a href="view_tickets_admin.php" class="btn btn-secondary mt-4">← Volver</a>
  <div class="card mt-4 p-4">
    <h1>Ticket #<?=htmlspecialchars($ticket['id'])?> — <?=htmlspecialchars($ticket['subject'])?></h1>
    <p><strong>Cliente:</strong> <?=htmlspecialchars($ticket['cliente'])?></p>
    <p><strong>Descripción:</strong><br><?=nl2br(htmlspecialchars($ticket['description']))?></p>
    <p><strong>Teléfono:</strong> <?=htmlspecialchars($ticket['phone'])?></p>
    <p><strong>Creado:</strong> <?=date('d/m/Y H:i',strtotime($ticket['created_at']))?></p>

    <?php if($attachments):?>
      <hr><h2>Archivos</h2>
      <?php foreach($attachments as $a):?>
        <a href="../<?=htmlspecialchars($a['file_path'])?>" target="_blank">
          <img src="../<?=htmlspecialchars($a['file_path'])?>" class="thumb">
        </a>
      <?php endforeach;?>
    <?php endif;?>

    <form method="post" class="mt-4">
      <label>Estatus:
        <select name="status">
          <option value="Abierto" <?= $ticket['status']==='Abierto'?'selected':'' ?>>Abierto</option>
          <option value="En Proceso" <?= $ticket['status']==='En Proceso'?'selected':'' ?>>En Proceso</option>
          <option value="Cerrado" <?= $ticket['status']==='Cerrado'?'selected':'' ?>>Cerrado</option>
        </select>
      </label>
      <label class="ml-3">Prioridad:
        <select name="priority">
          <option value="Alta" <?= $ticket['priority']==='Alta'?'selected':'' ?>>Alta</option>
          <option value="Media" <?= $ticket['priority']==='Media'?'selected':'' ?>>Media</option>
          <option value="Baja" <?= $ticket['priority']==='Baja'?'selected':'' ?>>Baja</option>
        </select>
      </label>
      <hr>
      <h2>Comentarios de Admin</h2>
      <?php foreach($comments as $c):?>
        <div class="comment-box mb-2">
          <p><?=nl2br(htmlspecialchars($c['comment']))?></p>
          <small><?=htmlspecialchars($c['author'])?> — <?=date('d/m/Y H:i',strtotime($c['created']))?></small>
        </div>
      <?php endforeach;?>
      <textarea name="admin_comment" rows="3" class="form-control mt-3" placeholder="Comentario de respuesta..."></textarea>
      <button class="btn btn-primary mt-2">Guardar Cambios</button>
    </form>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>