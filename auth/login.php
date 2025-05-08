<?php
// p360/auth/login.php

session_start();
require_once __DIR__ . '/../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    // Traemos también el company_id
    $stmt = $pdo->prepare("
        SELECT id, name, email, password, role, company_id
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        // Guardamos en sesión
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['company_id'] = $user['company_id'];

        // Redirigir según rol
        switch ($user['role']) {
            case 'operativas':
                header('Location: ../dashboard/operativas.php');
                break;
            case 'compras':
                header('Location: ../dashboard/compras.php');
                break;
            case 'admin':
                header('Location: ../dashboard/manage_orders.php');
                break;
            default:
                header('Location: ../auth/login.php');
        }
        exit;
    } else {
        $error = 'Credenciales inválidas';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login | Pinta360</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <main class="main">
    <h2>Iniciar sesión</h2>
    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
      <label>Email:<br>
        <input type="email" name="email" required autofocus>
      </label>
      <br><br>
      <label>Contraseña:<br>
        <input type="password" name="password" required>
      </label>
      <br><br>
      <button type="submit" class="btn">Entrar</button>
    </form>
  </main>
</body>
</html>
