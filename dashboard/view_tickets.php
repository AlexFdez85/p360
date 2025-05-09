<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Historial de Tickets</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php
session_start();
require_once __DIR__ . '/../config.php';
// Depuración: mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Sólo rol operativas
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'operativas') {
    header('Location: ../auth/login.php');
    exit;
}
require __DIR__ . '/../includes/header.php';

// Mensaje flash extendido con ID
if (isset($_GET['msg'], $_GET['id']) && $_GET['msg']==='created') {
    $tid = intval($_GET['id']);
    echo '<div class="alert alert-success m-4">'
       . "Tu ticket con número $tid ha sido creado correctamente, este será atendido antes de las siguientes 24 horas hábiles. Gracias por usar nuestro portal." 
       . '</div>';
}

// Obtener todos los tickets del usuario (historial)
$stmt = $pdo->prepare(
    "SELECT t.id, t.subject, t.phone, t.priority, t.status, t.created_at, u.name AS cliente
     FROM tickets t
     JOIN users u ON u.id = t.user_id
     WHERE t.user_id = ?
     ORDER BY t.created_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main class="main container">
  <a href="operativas.php" class="btn btn-secondary mt-4">← Menú</a>
  <div class="card mt-4 p-4">
    <h1>Historial de Tickets</h1>
    <div class="table-responsive">
      <table class="table table-striped mt-3">
        <thead>
          <tr>
            <th>ID</th><th>Título</th><th>Teléfono</th><th>Cliente</th><th>Prioridad</th><th>Estatus</th><th>Fecha</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($tickets)): ?>
            <tr><td colspan="8">No hay tickets registrados.</td></tr>
          <?php else: foreach ($tickets as $t): ?>
            <tr>
              <td><?= $t['id'] ?></td>
              <td><?= htmlspecialchars($t['subject']) ?></td>
              <td><?= htmlspecialchars($t['phone']) ?></td>
              <td><?= htmlspecialchars($t['cliente']) ?></td>
              <td><?= $t['priority'] ?></td>
              <td><?= $t['status'] ?></td>
              <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
              <td><a href="ticket_detail.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-info">Ver</a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>