<?php
// includes/header.php

// S¨®lo arrancar sesi¨®n si no est¨¢ activa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Si no hay usuario logueado, redirigir al login
if (!isset($_SESSION['user_id'])) {
    header('Location: /p360/auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Portal Pinta360</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/p360/assets/css/style.css">
  <!-- GLightbox -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
</head>
<body>
  <header class="site-header">
    <div class="site-header__inner">
      <!-- Logo a la izquierda -->
      <a href="/p360/dashboard/<?= htmlspecialchars($_SESSION['role']) ?>.php"
         class="site-header__logo-link">
        <img src="/p360/assets/images/logo.png"
             alt="Logo Grupo Ferro"
             class="site-header__logo">
      </a>

      <!-- Men¨² y Cerrar sesi¨®n a la derecha -->
      <nav class="site-header__nav">
        <a href="/p360/dashboard/<?= htmlspecialchars($_SESSION['role']) ?>.php"
           class="site-header__link">Menu</a>
        <a href="/p360/auth/logout.php"
           class="site-header__link site-header__logout">Cerrar sesion</a>
      </nav>
    </div>
  </header>


