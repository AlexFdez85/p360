<?php
// index.php – Pantalla de bienvenida pública
session_start();
if (isset($_SESSION['user_id'])) {
  switch ($_SESSION['role']) {
    case 'admin':     header('Location: dashboard/admin.php');    break;
    case 'compras':   header('Location: dashboard/compras.php');  break;
    case 'operativas':header('Location: dashboard/operativas.php');break;
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Bienvenido a Pinta360</title>
  <link rel="stylesheet" href="/p360/assets/css/style.css">
</head>
<body>

  <main class="welcome-hero">
    <!-- Capa semitransparente sobre la imagen de fondo -->
    <div class="welcome-hero__overlay"></div>

    <!-- Contenido centrado -->
    <div class="welcome-hero__content">
      <!-- Logo -->
      <img src="/p360/assets/images/logo.png"
           alt="Logo Grupo Ferro"
           class="welcome-hero__logo">

      <!-- Título y subtítulo -->
      <h1 class="welcome-hero__title">Bienvenido a Pinta360</h1>
      <p class="welcome-hero__subtitle">
        Gestión de soporte, pedidos y capacitación especializada
      </p>

      <!-- Botón de ingreso -->
      <a href="/p360/auth/login.php" class="btn btn--lg">Ingresar al portal</a>
    </div>
  </main>

  <footer>
    &copy; <?= date('Y') ?> Pinturas Grupo FERRO. Todos los derechos reservados.
  </footer>

</body>
</html>




