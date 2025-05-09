<?php
// dashboard/ticket_detail.php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config.php';

$id = $_GET['id'] ?? null;

try {
  // 1) Cargar ticket del cliente
  $stmt = $pdo->prepare("
    SELECT * 
      FROM tickets 
     WHERE id = ? 
       AND user_id = ?
  ");
  $stmt->execute([$id, $_SESSION['user_id']]);
  $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$ticket) {
    throw new Exception('Ticket no encontrado o sin permisos.');
  }

  // 2) Cargar adjuntos
  $a = $pdo->prepare("
    SELECT * 
      FROM ticket_attachments 
     WHERE ticket_id = ?
  ");
  $a->execute([$id]);
  $attachments = $a->fetchAll(PDO::FETCH_ASSOC);

  // 3) Cargar comentarios (tabla tickets_comments, columna created)
  $c = $pdo->prepare("
    SELECT * 
      FROM tickets_comments 
     WHERE ticket_id = ? 
     ORDER BY created ASC
  ");
  $c->execute([$id]);
  $comments = $c->fetchAll(PDO::FETCH_ASSOC);

  // 4) Nuevo comentario cliente
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['msg'] ?? '');
    if ($msg !== '') {
      // Autor = nombre de usuario
      $u = $pdo->prepare("SELECT name FROM users WHERE id = ?");
      $u->execute([$_SESSION['user_id']]);
      $author = $u->fetchColumn() ?: 'Cliente';

      $ins = $pdo->prepare("
        INSERT INTO tickets_comments 
          (ticket_id, author, comment, created)
        VALUES (?, ?, ?, NOW())
      ");
      $ins->execute([$id, $author, $msg]);

      header("Location: ticket_detail.php?id=$id");
      exit;
    }
  }
}
catch (Exception $e) {
  echo '<div class="error">Error: '.htmlspecialchars($e->getMessage()).'</div>';
  require __DIR__ . '/../includes/footer.php';
  exit;
}
?>
<main class="main container">
  <a href="view_tickets.php" class="btn">&larr; Volver a Tickets</a>
  <h1>Ticket #<?= htmlspecialchars($ticket['id']) ?> — <?= htmlspecialchars($ticket['title']) ?></h1>
  <p><strong>Descripción:</strong><br><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
  <p><strong>Teléfono:</strong> <?= htmlspecialchars($ticket['phone']) ?></p>
  <p><strong>Prioridad:</strong> <?= htmlspecialchars($ticket['priority']) ?></p>
  <p><strong>Estatus:</strong> <?= htmlspecialchars($ticket['status']) ?></p>
  <p><strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></p>
  <hr>

  <!-- Adjuntos con miniaturas + Lightbox -->
  <div class="ticket-attachments">
    <h2>Archivos adjuntos</h2>
    <?php if (empty($attachments)): ?>
      <p>No hay archivos adjuntos.</p>
    <?php else: ?>
      <div class="attachments-grid">
        <?php foreach ($attachments as $att):
          $file = basename($att['file_path']);
          // URL respetando subcarpeta ticket_id
          $url  = "/p360/uploads/tickets/{$ticket['id']}/{$file}";
        ?>
          <a href="<?= htmlspecialchars($url) ?>"
             class="glightbox"
             data-glightbox="title: <?= htmlspecialchars($att['file_name']) ?>">
            <img src="<?= htmlspecialchars($url) ?>"
                 alt="<?= htmlspecialchars($att['file_name']) ?>" />
          </a>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </div>
  <hr>

  <!-- Comentarios -->
  <div class="comments">
    <h2>Comentarios</h2>
    <?php if (empty($comments)): ?>
      <p>Sin comentarios.</p>
    <?php else: ?>
      <?php foreach ($comments as $cm): ?>
        <div class="comment">
          <p><?= nl2br(htmlspecialchars($cm['comment'])) ?></p>
          <small>
            <?= htmlspecialchars($cm['author']) ?> —
            <?= date('d/m/Y H:i', strtotime($cm['created'])) ?>
          </small>
        </div>
        <hr>
      <?php endforeach ?>
    <?php endif ?>
  </div>

  <!-- Formulario de respuesta -->
  <div class="reply">
    <h2>Responder / Comunicar al Admin</h2>
    <form method="post">
      <textarea name="msg" rows="4" style="width:100%;"></textarea>
      <button type="submit" class="btn">Enviar Mensaje</button>
    </form>
  </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>








