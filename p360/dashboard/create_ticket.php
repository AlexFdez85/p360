<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear Nuevo Ticket</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php
session_start();
require_once __DIR__ . '/../config.php';
ini_set('display_errors', 1); error_reporting(E_ALL);
// Solo rol “operativas”
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'operativas') {
    header('Location: ../auth/login.php'); exit;
}
require __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject     = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $phone       = trim($_POST['phone']);
    $priority    = $_POST['priority'] ?? '';
    if (!$subject || !$description || !$phone || !in_array($priority, ['Alta','Media','Baja'])) {
        $error = 'Completa todos los campos obligatorios.';
    } else {
        // Guarda ticket
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, description, priority, phone, status, created_at) VALUES (?, ?, ?, ?, ?, 'Abierto', NOW())");
        $stmt->execute([$_SESSION['user_id'], $subject, $description, $priority, $phone]);
        $tid = $pdo->lastInsertId();
        // Adjuntos...
        if (!empty($_FILES['attachments']['name'][0])) {
            $dir = __DIR__.'/../uploads/tickets/'.$tid;
            if (!is_dir($dir)) mkdir($dir,0755,true);
            foreach ($_FILES['attachments']['tmp_name'] as $i=>$tmp) {
                if ($_FILES['attachments']['error'][$i]===UPLOAD_ERR_OK) {
                    $fn=uniqid().'_'.basename($_FILES['attachments']['name'][$i]);
                    move_uploaded_file($tmp, "$dir/$fn");
                    $pdo->prepare("INSERT INTO ticket_attachments(ticket_id,file_path) VALUES(?,?)")
                        ->execute([$tid, "uploads/tickets/$tid/$fn"]);
                }
            }
        }
        // Mostrar solo mensaje
        $_SESSION['flash'] = "Tu ticket con número $tid ha sido creado correctamente. Será atendido en 24h hábiles.";
        header('Location: create_ticket.php?success=1'); exit;
    }
}
// Vista de formulario o éxito
?>
<main class="main container">
  <a href="operativas.php" class="btn">← Menú</a>
  <div class="card mt-4 p-4">
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success"><?=$_SESSION['flash']??''?></div>
      <p><a href="ticket_history.php" class="btn btn-primary">Ver Historial de Tickets</a></p>
    <?php else: ?>
      <h1>Crear Nuevo Ticket</h1>
      <?php if (!empty($error)): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data" class="form">
        <label>Asunto<br><input type="text" name="subject" required></label>
        <label>Descripción<br><textarea name="description" rows="4" required></textarea></label>
        <label>Teléfono<br><input type="text" name="phone" required></label>
        <label>Prioridad<br><select name="priority">
          <option value="Media" selected>Media</option>
          <option value="Alta">Alta</option>
          <option value="Baja">Baja</option>
        </select></label>
        <label>Adjuntar imágenes<br><input type="file" name="attachments[]" multiple accept="image/*"></label>
        <button type="submit" class="btn btn-primary mt-3">Enviar</button>
      </form>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__.'/../includes/footer.php'; ?>
