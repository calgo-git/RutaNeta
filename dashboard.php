<?php
// =====================================================================
// dashboard.php — Panel principal. Los KPI se construyen en la etapa 4.
// En esta etapa ya es una página protegida: sirve para comprobar que el
// login guardó al conductor en la sesión y que la guardia de acceso funciona.
// =====================================================================
require_once 'config/sesion.php';
requerir_login();                 // sin sesión → login.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel · RutaNeta</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="pagina-acceso">
    <main class="tarjeta-acceso">
        <a href="index.html" class="acceso-logo"><img src="logo.png" alt="RutaNeta, ir a la página principal"></a>
        <!-- El nombre lo guardó login.php en $_SESSION -->
        <h1>Hola, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
        <p class="acceso-intro">Iniciaste sesión. El panel con tus números del mes llega en la etapa 4.</p>
        <a href="logout.php" class="btn btn-primario btn-bloque">Cerrar sesión</a>
    </main>
</body>
</html>
