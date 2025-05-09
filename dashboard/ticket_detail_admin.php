<?php
// dashboard/ticket_detail_admin.php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config.php';

$id = $_GET['id'] ?? null;

try {
  // 1) Cargar ticket + nombre de cliente
  $t = $pdo->prepare("
    SELECT t.*, u.name AS client_name
      FROM tickets t
      JOIN users u ON u.id = t.user_id
     WHERE t.id = ?
  ");
  $t->execute([$id]);
  $ticket = $t->fetch(PDO::FETCH_ASSOC);
  if (!$ticket) {
    throw new Exception('Ticket no encontrado.');
  }

  // 2) Adjuntos
  $a = $pdo->prepare("SELECT * FROM ticket_attachments WHERE ticket_id = ?");
  $a->execute([$id]);
  $attachments = $a->fetchAll(PDO::FETCH_ASSOC);

  // 3) Comentarios admin+cliente
  $c = $pdo->prepare("
    SELECT * 
      FROM tickets_comments 
     WHERE ticket_id = ? 
     ORDER BY created ASC
  ");
  $c->execute([$id]);
  $comments = $c->fetchAll(PDO::FETCH_ASSOC);

  // 4) Nuevo comentario admin
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['msg'] ?? '');
    if ($msg !== '') {
      $ins = $pdo->prepare("
        INSERT INTO tickets_comments 
          (ticket_id, author, comment, created)
        VALUES (?, 'Administrador', ?, NOW())
      ");
      $ins->execute([$id, $msg]);

      header("Location: ticket_detail_admin.php?id=$id");
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
  <a href="view_tickets_admin.php" class="btn">&larr; Volver a Tickets</a>
  <h1>Ticket #<?= htmlspecialchars($ticket['id']) ?> — <?= htmlspecialchars($ticket['title']) ?></h1>
  <p><strong>Cliente:</strong> <?= htmlspecialchars($ticket['client_name']) ?></p>
  <p><strong>Descripción:</strong><br><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
  <p><strong>Teléfono:</strong> <?= htmlspecialchars($ticket['phone']) ?></p>
  <p><strong>Prioridad:</strong> <?= htmlspecialchars($ticket['priority']) ?></p>
  <p><strong>Estatus:</strong> <?= htmlspecialchars($ticket['status']) ?></p>
  <p><strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></p>
  <hr>

  <!-- Adjuntos -->
  <div class="ticket-attachments">
    <h2>Archivos adjuntos</h2>
    <?php if (empty($attachments)): ?>
      <p>No hay archivos adjuntos.</p>
    <?php else: ?>
      <div class="attachments-grid">
        <?php foreach ($attachments as $att):
          $file = basename($att['file_path']);
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
    <h2>Responder al Cliente</h2>
    <form method="post">
      <textarea name="msg" rows="4" style="width:100%;"></textarea>
      <button type="submit" class="btn">Enviar Mensaje</button>
    </form>
  </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>





