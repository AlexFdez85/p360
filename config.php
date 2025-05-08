<?php
// p360/config.php

// Arranca sesión si no existe aún
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Parámetros de conexión
define('DB_HOST', 'localhost');
define('DB_NAME', 'ffteqbal_p360_db');
define('DB_USER', 'ffteqbal_ffteqball');
define('DB_PASS', 'Petrolera85*');

// Conecta con PDO
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // Si hay error, muere mostrando el mensaje
    die("Error de conexión: " . $e->getMessage());
}
