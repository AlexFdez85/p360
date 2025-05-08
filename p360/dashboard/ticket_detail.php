<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle Ticket</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php
session_start();
require_once __DIR__ . '/../config.php';
// Mostrar errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar permisos
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'operativas') {
    header('Location: ../auth/login.php');
    exit;
}

// Incluir header
require __DIR__ . '/../includes/header.php';

try {
    // Obtener ID de ticket
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        throw new Exception('ID de ticket inválido.');
    }

    // Cargar datos del ticket
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) {
        throw new Exception('Ticket no encontrado o sin permisos.');
    }

    // Cargar archivos adjuntos
    $attachStmt = $pdo->prepare("SELECT * FROM ticket_attachments WHERE ticket_id = ?");
    $attachStmt->execute([$id]);
    $attachments = $attachStmt->fetchAll(PDO::FETCH_ASSOC);

    // Cargar comentarios históricos
    $commentStmt = $pdo->prepare("SELECT * FROM tickets_comments WHERE ticket_id = ? ORDER BY created ASC");
    $commentStmt->execute([$id]);
    $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar envío de nuevo comentario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $msg = trim($_POST['msg'] ?? '');
        if ($msg !== '') {
            // Determinar autor: siempre leer "name" de users
            $uStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $uStmt->execute([$_SESSION['user_id']]);
            $author = $uStmt->fetchColumn() ?: 'Operativo';
            
            // Insertar comentario
            $ins = $pdo->prepare(
                "INSERT INTO tickets_comments (ticket_id, author, comment, created) VALUES (?, ?, ?, NOW())"
            ); $pdo->prepare(
                "INSERT INTO tickets_comments (ticket_id, author, comment, created) VALUES (?, ?, ?, NOW())"
            );
            $ins->execute([$id, $author, $msg]);
            // Redirigir para refrescar la página
            header("Location: ticket_detail.php?id=$id");
            exit;
        }
    }

} catch (Exception $e) {
    echo '<div class="alert alert-danger m-4">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}
?>
<main class="main container">
  <a href="ticket_history.php" class="btn btn-secondary mt-4">← Volver</a>
  <div class="card mt-4 p-4">
    <h1>Ticket #<?= htmlspecialchars($ticket['id']) ?> — <?= htmlspecialchars($ticket['subject']) ?></h1>
    <p><strong>Descripción:</strong><br><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
    <p><strong>Teléfono:</strong> <?= htmlspecialchars($ticket['phone']) ?></p>
    <p><strong>Prioridad:</strong> <?= htmlspecialchars($ticket['priority']) ?></p>
    <p><strong>Estatus:</strong> <?= htmlspecialchars($ticket['status']) ?></p>
    <p><strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></p>

    <?php if (!empty($attachments)): ?>
      <hr>
      <h2>Archivos Adjuntos</h2>
      <div class="attachments mt-2">
        <?php foreach ($attachments as $a): ?>
          <a href="../<?= htmlspecialchars($a['file_path']) ?>" target="_blank" class="mr-2">
            <img src="../<?= htmlspecialchars($a['file_path']) ?>" class="thumb">
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <hr>
    <h2>Comentarios</h2>
    <?php if (empty($comments)): ?>
      <p>Sin comentarios.</p>
    <?php else: ?>
      <?php foreach ($comments as $c): ?>
        <div class="comment-box mb-3">
          <p><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
          <small><?= htmlspecialchars($c['author']) ?> — <?= date('d/m/Y H:i', strtotime($c['created'])) ?></small>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <hr>
    <h2>Responder / Comunicar al Admin</h2>
    <form method="post" class="mt-3">
      <textarea name="msg" rows="3" class="form-control mb-2" placeholder="Escribe tu mensaje..."></textarea>
      <button class="btn btn-primary">Enviar Mensaje</button>
    </form>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
