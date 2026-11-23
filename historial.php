<?php
// =====================================================================
// historial.php — Reporte mensual comparativo
// El <input type="month"> manda el mes por GET. PDO suma ingresos y gastos
// de los 6 meses que terminan en el mes elegido y PHP arma la tabla.
// JS (js/app.js) pinta el neto de verde o rojo con el valor de data-neto.
// =====================================================================
require_once 'config/sesion.php';
requerir_login();
require_once 'config/conexion.php';

$usuario_id = $_SESSION['usuario_id'];

$nombres_meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio',
                  'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

// Mes elegido en formato AAAA-MM. Si no llega o no es válido, se usa el mes actual.
// Es un GET porque solo consulta: no cambia datos, así que no necesita token CSRF.
$mes = $_GET['mes'] ?? date('Y-m');
$inicio_mes = is_string($mes) ? DateTime::createFromFormat('!Y-m', $mes) : false;
if (!$inicio_mes || $inicio_mes->format('Y-m') !== $mes) {
    $mes = date('Y-m');
    $inicio_mes = DateTime::createFromFormat('!Y-m', $mes);
}

// Ventana de 6 meses que termina en el mes elegido: [inicio_ventana, fin_ventana)
$inicio_ventana = (clone $inicio_mes)->modify('-5 months');
$fin_ventana    = (clone $inicio_mes)->modify('+1 month');

// Una fila en ceros por cada mes: así los meses sin movimientos también aparecen
$meses = [];
$cursor = clone $inicio_ventana;
for ($i = 0; $i < 6; $i++) {
    $meses[$cursor->format('Y-m')] = ['ingresos' => 0.0, 'gastos' => 0.0];
    $cursor->modify('+1 month');
}

$parametros = [
    'usuario_id' => $usuario_id,
    'inicio'     => $inicio_ventana->format('Y-m-d'),
    'fin'        => $fin_ventana->format('Y-m-d'),
];

// SUM agrupado por mes: una consulta trae los ingresos de los 6 meses
$consulta = $pdo->prepare(
    "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(monto) AS total
     FROM ingresos
     WHERE usuario_id = :usuario_id AND fecha >= :inicio AND fecha < :fin
     GROUP BY mes"
);
$consulta->execute($parametros);
foreach ($consulta->fetchAll() as $fila) {
    $meses[$fila['mes']]['ingresos'] = (float) $fila['total'];
}

// Lo mismo para los gastos
$consulta = $pdo->prepare(
    "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(monto) AS total
     FROM gastos
     WHERE usuario_id = :usuario_id AND fecha >= :inicio AND fecha < :fin
     GROUP BY mes"
);
$consulta->execute($parametros);
foreach ($consulta->fetchAll() as $fila) {
    $meses[$fila['mes']]['gastos'] = (float) $fila['total'];
}

// Ganancia neta de cada mes = ingresos − gastos, y totales de la ventana.
// round() quita restos como 0.0000001 que deja la resta de decimales en PHP.
$totales = ['ingresos' => 0.0, 'gastos' => 0.0];
foreach ($meses as $clave => $valores) {
    $meses[$clave]['neto'] = round($valores['ingresos'] - $valores['gastos'], 2);
    $totales['ingresos'] += $valores['ingresos'];
    $totales['gastos']   += $valores['gastos'];
}
$totales['neto'] = round($totales['ingresos'] - $totales['gastos'], 2);

// Resumen del mes elegido (es la última fila de la ventana)
$elegido = $meses[$mes];
$elegido_texto = $nombres_meses[(int) $inicio_mes->format('n')] . ' ' . $inicio_mes->format('Y');
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
                <h1>Historial mensual</h1>
                <p>Elige un mes y compáralo con los 5 anteriores.</p>
            </header>

            <section class="panel">
                <!-- GET: el mes elegido queda en la URL (historial.php?mes=2026-10) -->
                <form method="get" action="historial.php" class="filtro-mes">
                    <div class="campo">
                        <label for="mes">Mes</label>
                        <!-- pattern y placeholder ayudan en navegadores sin calendario de meses -->
                        <input type="month" id="mes" name="mes" value="<?= htmlspecialchars($mes) ?>"
                               pattern="[0-9]{4}-[0-9]{2}" placeholder="AAAA-MM" required>
                    </div>
                    <button type="submit" class="btn btn-secundario">Ver mes</button>
                </form>
            </section>

            <!-- Resumen del mes elegido; data-neto lleva el número crudo para que JS lo lea -->
            <section class="kpis" aria-label="Resumen de <?= htmlspecialchars($elegido_texto) ?>">
                <article class="kpi">
                    <h2>Ingresos · <?= htmlspecialchars($elegido_texto) ?></h2>
                    <p class="kpi-valor">$<?= number_format($elegido['ingresos'], 2) ?></p>
                </article>
                <article class="kpi kpi-gastos">
                    <h2>Gastos · <?= htmlspecialchars($elegido_texto) ?></h2>
                    <p class="kpi-valor">$<?= number_format($elegido['gastos'], 2) ?></p>
                </article>
                <article class="kpi" data-neto="<?= number_format($elegido['neto'], 2, '.', '') ?>">
                    <h2>Neto · <?= htmlspecialchars($elegido_texto) ?></h2>
                    <p class="kpi-valor"><?= $elegido['neto'] < 0 ? '−' : '' ?>$<?= number_format(abs($elegido['neto']), 2) ?></p>
                </article>
            </section>

            <section class="panel">
                <h2>Comparativa de 6 meses</h2>
                <div class="tabla-scroll">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th scope="col">Mes</th>
                                <th scope="col" class="num">Ingresos</th>
                                <th scope="col" class="num">Gastos</th>
                                <th scope="col" class="num">Neto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meses as $clave => $valores): ?>
                                <?php $fecha_mes = DateTime::createFromFormat('!Y-m', $clave); ?>
                                <tr class="<?= $clave === $mes ? 'mes-elegido' : '' ?>">
                                    <td><?= $nombres_meses[(int) $fecha_mes->format('n')] . ' ' . $fecha_mes->format('Y') ?></td>
                                    <td class="num">$<?= number_format($valores['ingresos'], 2) ?></td>
                                    <td class="num">$<?= number_format($valores['gastos'], 2) ?></td>
                                    <td class="num celda-neto" data-neto="<?= number_format($valores['neto'], 2, '.', '') ?>">
                                        <?= $valores['neto'] < 0 ? '−' : '' ?>$<?= number_format(abs($valores['neto']), 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th scope="row">Total 6 meses</th>
                                <td class="num">$<?= number_format($totales['ingresos'], 2) ?></td>
                                <td class="num">$<?= number_format($totales['gastos'], 2) ?></td>
                                <td class="num celda-neto" data-neto="<?= number_format($totales['neto'], 2, '.', '') ?>">
                                    <?= $totales['neto'] < 0 ? '−' : '' ?>$<?= number_format(abs($totales['neto']), 2) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script src="js/app.js"></script>
</body>
</html>
