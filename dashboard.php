<?php
// =====================================================================
// dashboard.php — Panel principal
// PDO suma los movimientos del mes actual y PHP pinta 4 KPI
// más la tabla con los últimos 5 ingresos del conductor.
// =====================================================================
require_once 'config/sesion.php';
requerir_login();                       // sin sesión → login.php
require_once 'config/conexion.php';

// El id SIEMPRE sale de la sesión, nunca de un formulario ni de la URL
$usuario_id = $_SESSION['usuario_id'];

// Mes actual como rango: del día 1 al día 1 del mes siguiente (sin incluirlo).
// Comparar fecha >= inicio AND fecha < fin aprovecha el índice (usuario_id, fecha).
$rango = [
    'usuario_id' => $usuario_id,
    'inicio'     => date('Y-m-01'),
    'fin'        => date('Y-m-01', strtotime('first day of next month')),
];

// KPI 1 y 4: ingresos y horas del mes, en una sola consulta
$consulta = $pdo->prepare(
    'SELECT COALESCE(SUM(monto), 0) AS total, COALESCE(SUM(horas_trab), 0) AS horas
     FROM ingresos
     WHERE usuario_id = :usuario_id AND fecha >= :inicio AND fecha < :fin'
);
$consulta->execute($rango);
$fila = $consulta->fetch();
$ingresos_mes = (float) $fila['total'];
$horas_mes    = (float) $fila['horas'];

// KPI 2: gastos del mes (fijos y variables cuentan igual)
$consulta = $pdo->prepare(
    'SELECT COALESCE(SUM(monto), 0)
     FROM gastos
     WHERE usuario_id = :usuario_id AND fecha >= :inicio AND fecha < :fin'
);
$consulta->execute($rango);
$gastos_mes = (float) $consulta->fetchColumn();

// KPI 3: la regla central de RutaNeta.
// round() quita restos como 0.0000001 que deja la resta de decimales en PHP.
$neto_mes = round($ingresos_mes - $gastos_mes, 2);

// Clase CSS del KPI neto: verde si ganó, rojo si perdió, sin color si está en cero
$clase_neto = '';
if ($neto_mes > 0) {
    $clase_neto = 'positivo';
} elseif ($neto_mes < 0) {
    $clase_neto = 'negativo';
}

// Últimos 5 ingresos del conductor
$consulta = $pdo->prepare(
    'SELECT fecha, plataforma, monto, horas_trab, km_recorr
     FROM ingresos
     WHERE usuario_id = :usuario_id
     ORDER BY fecha DESC, id DESC
     LIMIT 5'
);
$consulta->execute(['usuario_id' => $usuario_id]);
$ultimos = $consulta->fetchAll();

// Nombre del mes en español para el encabezado
$nombres_meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                  'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$mes_texto = $nombres_meses[(int) date('n')] . ' de ' . date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel · RutaNeta</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <!-- .contenedor es Flexbox: menú lateral a la izquierda, área principal a la derecha -->
    <div class="contenedor">
        <aside class="menu-lateral">
            <a href="dashboard.php" class="menu-logo"><img src="logo.png" alt="RutaNeta, ir al panel"></a>
            <nav class="menu-nav" aria-label="Secciones">
                <a href="dashboard.php" class="activo" aria-current="page">Panel</a>
                <a href="ingresos.php">Ingresos</a>
                <a href="gastos.php">Gastos</a>
                <a href="historial.php">Historial</a>
            </nav>
            <div class="menu-usuario">
                <!-- El nombre lo guardó login.php en la sesión -->
                <span>Hola, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong></span>
                <a href="logout.php">Cerrar sesión</a>
            </div>
        </aside>

        <main class="principal">
            <header class="encabezado">
                <h1>Tu mes en números</h1>
                <p>Resumen de <?= htmlspecialchars($mes_texto) ?>: ingresos menos gastos = lo que de verdad ganaste.</p>
            </header>

            <!-- Los 4 KPI. number_format() solo devuelve dígitos, comas y punto: no necesita escape -->
            <section class="kpis" aria-label="Indicadores del mes">
                <article class="kpi">
                    <h2>Ingresos del mes</h2>
                    <p class="kpi-valor">$<?= number_format($ingresos_mes, 2) ?></p>
                    <p class="kpi-detalle">Lo que te pagaron las plataformas</p>
                </article>

                <article class="kpi kpi-gastos">
                    <h2>Gastos del mes</h2>
                    <p class="kpi-valor">$<?= number_format($gastos_mes, 2) ?></p>
                    <p class="kpi-detalle">Fijos y variables</p>
                </article>

                <!-- PHP elige la clase de color según el signo del neto -->
                <article class="kpi <?= $clase_neto ?>">
                    <h2>Ganancia neta</h2>
                    <p class="kpi-valor"><?= $neto_mes < 0 ? '−' : '' ?>$<?= number_format(abs($neto_mes), 2) ?></p>
                    <p class="kpi-detalle"><?= $neto_mes < 0 ? 'Este mes vas en pérdida' : 'Lo que de verdad te queda' ?></p>
                </article>

                <article class="kpi kpi-horas">
                    <h2>Horas trabajadas</h2>
                    <p class="kpi-valor"><?= number_format($horas_mes, 1) ?> h</p>
                    <p class="kpi-detalle">
                        <?php if ($horas_mes > 0): ?>
                            <?= $neto_mes < 0 ? '−' : '' ?>$<?= number_format(abs($neto_mes / $horas_mes), 2) ?> netos por hora
                        <?php else: ?>
                            Aún sin horas registradas
                        <?php endif; ?>
                    </p>
                </article>
            </section>

            <section class="panel">
                <div class="panel-cabecera">
                    <h2>Últimos 5 ingresos</h2>
                    <a href="ingresos.php" class="btn btn-secundario">Ver todos y registrar</a>
                </div>

                <?php if (!$ultimos): ?>
                    <p class="vacio">Aún no registras ingresos. <a href="ingresos.php">Registra el primero</a>.</p>
                <?php else: ?>
                    <div class="tabla-scroll">
                        <table class="tabla tabla-seleccionable">
                            <thead>
                                <tr>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Plataforma</th>
                                    <th scope="col" class="num">Monto</th>
                                    <th scope="col" class="num">Horas</th>
                                    <th scope="col" class="num">Km</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimos as $ingreso): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($ingreso['fecha'])) ?></td>
                                        <td><?= htmlspecialchars($ingreso['plataforma']) ?></td>
                                        <td class="num">$<?= number_format((float) $ingreso['monto'], 2) ?></td>
                                        <td class="num"><?= number_format((float) $ingreso['horas_trab'], 1) ?></td>
                                        <td class="num"><?= number_format((float) $ingreso['km_recorr'], 1) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script src="js/app.js"></script>
</body>
</html>
