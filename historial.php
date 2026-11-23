<?php
// =====================================================================
// historial.php — Reporte mensual comparativo. Se construye en la etapa 5.
// Mientras tanto es una página protegida con el menú del sistema, para que
// el enlace "Historial" del menú lateral no lleve a un callejón sin salida.
// =====================================================================
require_once 'config/sesion.php';
requerir_login();                       // sin sesión → login.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial · RutaNeta</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div class="contenedor">
        <aside class="menu-lateral">
            <a href="dashboard.php" class="menu-logo"><img src="logo.png" alt="RutaNeta, ir al panel"></a>
            <nav class="menu-nav" aria-label="Secciones">
                <a href="dashboard.php">Panel</a>
                <a href="ingresos.php">Ingresos</a>
                <a href="gastos.php">Gastos</a>
                <a href="historial.php" class="activo" aria-current="page">Historial</a>
            </nav>
            <div class="menu-usuario">
                <span>Hola, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong></span>
                <a href="logout.php">Cerrar sesión</a>
            </div>
        </aside>

        <main class="principal">
            <header class="encabezado">
                <h1>Historial</h1>
                <p>Aquí compararás el mes que elijas con los 5 anteriores.</p>
            </header>

            <section class="panel">
                <p class="vacio">Esta sección llega en la etapa 5. <a href="dashboard.php">Volver al panel</a>.</p>
            </section>
        </main>
    </div>
</body>
</html>
