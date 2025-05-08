<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrar Tickets</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php
session_start();
require_once __DIR__ . '/../config.php';
ini_set('display_errors',1); error_reporting(E_ALL);
// Solo rol admin
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../auth/login.php'); exit;
}
require __DIR__ . '/../includes/header.php';

// Procesar filtros opcionales (estado, prioridad)
$statusFilter = $_GET['status'] ?? '';
$prioFilter = $_GET['priority'] ?? '';

$sql = "SELECT t.id, t.subject, t.priority, t.status, t.created_at, u.name AS cliente
        FROM tickets t
        JOIN users u ON u.id = t.user_id";
$params = [];
$clauses = [];
if ($statusFilter) { $clauses[] = "t.status = ?"; $params[] = $statusFilter; }
if ($prioFilter)  { $clauses[] = "t.priority = ?"; $params[] = $prioFilter; }
if ($clauses) {
  $sql .= ' WHERE ' . implode(' AND ', $clauses);
}
$sql .= ' ORDER BY t.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main class="main container">
  <h1>Administrar Tickets</h1>
  <form method="get" class="form-inline mb-3">
    <label>Estado:
      <select name="status">
        <option value="">Todos</option>
        <option value="Abierto" <?= $statusFilter==='Abierto'?'selected':'' ?>>Abierto</option>
        <option value="En Proceso" <?= $statusFilter==='En Proceso'?'selected':'' ?>>En Proceso</option>
        <option value="Cerrado" <?= $statusFilter==='Cerrado'?'selected':'' ?>>Cerrado</option>
      </select>
    </label>
    <label>Prioridad:
      <select name="priority">
        <option value="">Todas</option>
        <option value="Alta" <?= $prioFilter==='Alta'?'selected':'' ?>>Alta</option>
        <option value="Media" <?= $prioFilter==='Media'?'selected':'' ?>>Media</option>
        <option value="Baja" <?= $prioFilter==='Baja'?'selected':'' ?>>Baja</option>
      </select>
    </label>
    <button class="btn btn-secondary ml-2">Filtrar</button>
  </form>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr>
        <th>ID</th><th>Asunto</th><th>Cliente</th><th>Prioridad</th><th>Estatus</th><th>Fecha</th><th>Acciones</th>
      </tr></thead>
      <tbody>
        <?php if (empty($tickets)): ?>
        <tr><td colspan="7">Sin tickets.</td></tr>
        <?php else: foreach($tickets as $t): ?>
        <tr>
          <td><?= $t['id'] ?></td>
          <td><?= htmlspecialchars($t['subject']) ?></td>
          <td><?= htmlspecialchars($t['cliente']) ?></td>
          <td><?= htmlspecialchars($t['priority']) ?></td>
          <td><?= htmlspecialchars($t['status']) ?></td>
          <td><?= date('d/m/Y H:i',strtotime($t['created_at'])) ?></td>
          <td><a href="ticket_detail_admin.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-info">Ver / Editar</a></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>