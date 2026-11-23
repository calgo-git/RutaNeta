<?php
// =====================================================================
// logout.php — Cerrar sesión
// No pinta HTML: borra la sesión y regresa a login.php.
// =====================================================================
require_once 'config/sesion.php';

// 1) Vacía los datos de la sesión (usuario_id, nombre, token CSRF...)
$_SESSION = [];

// 2) Borra la cookie de sesión del navegador para no dejar un id viejo
setcookie(session_name(), '', time() - 3600, '/');

// 3) Destruye la sesión en el servidor
session_destroy();

// login.php ve ?salida=1 y muestra "Cerraste sesión"
header('Location: login.php?salida=1');
exit;
