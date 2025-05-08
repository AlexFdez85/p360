<!-- p360/includes/header.php -->
<header class="site-header">
  <div class="logo">
    <img src="/p360/assets/images/logo.png" alt="Grupo Ferro" height="40">
    <span>Portal Pinta360</span>
  </div>
  <nav class="site-nav">
    <a href="<?php
      // Enlace relativo en el mismo directorio
      if   ($_SESSION['role'] === 'admin')   echo 'admin.php';
      elseif ($_SESSION['role'] === 'compras') echo 'compras.php';
      else                                   echo 'operativas.php';
    ?>">Menu</a>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesion</a>
  </nav>
</header>



