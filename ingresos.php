<?php
// =====================================================================
// ingresos.php — Alta, listado y eliminación de ingresos
// Un solo archivo atiende tres casos:
//   POST accion=crear    → PHP valida y PDO hace el INSERT
//   POST accion=eliminar → DELETE solo si el registro es del conductor
//   GET                  → SELECT y PHP pinta formulario y tabla
// =====================================================================
require_once 'config/sesion.php';
requerir_login();
require_once 'config/conexion.php';

$usuario_id  = $_SESSION['usuario_id'];
$plataformas = ['Rappi', 'DiDi', 'Uber', 'Mercado Libre Envíos', 'Amazon Flex', 'Otra'];
$errores     = [];

// La plataforma que el conductor eligió al registrarse aparece preseleccionada
$consulta = $pdo->prepare('SELECT plataforma FROM usuarios WHERE id = :id');
$consulta->execute(['id' => $usuario_id]);

// Valores del formulario: por defecto, o lo que escribió el conductor si hubo errores
$datos = [
    'plataforma' => (string) $consulta->fetchColumn(),
    'monto'      => '',
    'horas_trab' => '0',
    'km_recorr'  => '0',
    'fecha'      => date('Y-m-d'),
    'notas'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if (!validar_csrf()) {
        $errores[] = ERROR_CSRF;

    } elseif ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);

        // Filtro de propiedad: aunque alguien cambie el id en el formulario
        // con DevTools, el DELETE solo borra si el ingreso es suyo.
        $borrar = $pdo->prepare('DELETE FROM ingresos WHERE id = :id AND usuario_id = :usuario_id');
        $borrar->execute(['id' => $id, 'usuario_id' => $usuario_id]);

        // rowCount() dice cuántas filas borró: 0 = no existe o es de otra cuenta
        guardar_mensaje($borrar->rowCount() === 1 ? 'Ingreso eliminado.' : 'No se encontró ese ingreso en tu cuenta.');
        header('Location: ingresos.php');
        exit;

    } elseif ($accion === 'crear') {
        $datos['plataforma'] = $_POST['plataforma'] ?? '';
        $datos['monto']      = trim($_POST['monto'] ?? '');
        $datos['horas_trab'] = trim($_POST['horas_trab'] ?? '');
        $datos['km_recorr']  = trim($_POST['km_recorr'] ?? '');
        $datos['fecha']      = trim($_POST['fecha'] ?? '');
        $datos['notas']      = trim($_POST['notas'] ?? '');

        // filter_var() convierte el texto en número, o devuelve false si no lo es.
        // Horas y km vacíos cuentan como 0.
        $monto = filter_var($datos['monto'], FILTER_VALIDATE_FLOAT);
        $horas = filter_var($datos['horas_trab'] === '' ? '0' : $datos['horas_trab'], FILTER_VALIDATE_FLOAT);
        $km    = filter_var($datos['km_recorr'] === '' ? '0' : $datos['km_recorr'], FILTER_VALIDATE_FLOAT);

        // La fecha es válida solo si existe en el calendario (2026-02-30 no pasa)
        $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha']);

        // Reglas de negocio. Los topes corresponden al tamaño de cada columna DECIMAL.
        if (!in_array($datos['plataforma'], $plataformas, true)) {
            $errores[] = 'Elige una plataforma de la lista.';
        }
        if ($monto === false || $monto <= 0 || $monto >= 100000000) {
            $errores[] = 'El monto debe ser mayor que 0.';
        }
        if ($horas === false || $horas < 0 || $horas >= 1000) {
            $errores[] = 'Las horas deben ser 0 o más (máximo 999.99).';
        }
        if ($km === false || $km < 0 || $km >= 1000000) {
            $errores[] = 'Los kilómetros deben ser 0 o más.';
        }
        if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha']) {
            $errores[] = 'Escribe una fecha válida.';
        }
        if (mb_strlen($datos['notas']) > 500) {
            $errores[] = 'Las notas no pueden pasar de 500 caracteres.';
        }

        // Sin errores: PHP pasa los datos a PDO y la BD guarda el ingreso
        if (!$errores) {
            $insertar = $pdo->prepare(
                'INSERT INTO ingresos (usuario_id, plataforma, monto, horas_trab, km_recorr, fecha, notas)
                 VALUES (:usuario_id, :plataforma, :monto, :horas, :km, :fecha, :notas)'
            );
            $insertar->execute([
                'usuario_id' => $usuario_id,   // de la sesión, nunca del formulario
                'plataforma' => $datos['plataforma'],
                'monto'      => $monto,
                'horas'      => $horas,
                'km'         => $km,
                'fecha'      => $datos['fecha'],
                'notas'      => $datos['notas'] === '' ? null : $datos['notas'],
            ]);

            // Post/Redirect/Get: recargar la página ya no duplica el ingreso
            guardar_mensaje('Ingreso guardado.');
            header('Location: ingresos.php');
            exit;
        }
    }
}

// Historial completo del conductor, del más reciente al más antiguo
$consulta = $pdo->prepare(
    'SELECT id, fecha, plataforma, monto, horas_trab, km_recorr, notas
     FROM ingresos
     WHERE usuario_id = :usuario_id
     ORDER BY fecha DESC, id DESC'
);
$consulta->execute(['usuario_id' => $usuario_id]);
$ingresos = $consulta->fetchAll();

$mensaje = tomar_mensaje();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresos · RutaNeta</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div class="contenedor">
        <aside class="menu-lateral">
            <a href="dashboard.php" class="menu-logo"><img src="logo.png" alt="RutaNeta, ir al panel"></a>
            <nav class="menu-nav" aria-label="Secciones">
                <a href="dashboard.php">Panel</a>
                <a href="ingresos.php" class="activo" aria-current="page">Ingresos</a>
                <a href="gastos.php">Gastos</a>
                <a href="historial.php">Historial</a>
            </nav>
            <div class="menu-usuario">
                <span>Hola, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong></span>
                <a href="logout.php">Cerrar sesión</a>
            </div>
        </aside>

        <main class="principal">
            <header class="encabezado">
                <h1>Ingresos</h1>
                <p>Registra cada pago de las plataformas con las horas y los kilómetros de ese trabajo.</p>
            </header>

            <?php if ($mensaje !== ''): ?>
                <p class="alerta alerta-exito" role="status"><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?>

            <?php if ($errores): ?>
                <div class="alerta alerta-error" role="alert">
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="panel">
                <h2>Registrar ingreso</h2>
                <!-- PHP recibe este formulario en el mismo archivo (accion=crear) -->
                <form method="post" action="ingresos.php" class="form-alta">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="accion" value="crear">

                    <div class="campo">
                        <label for="plataforma">Plataforma</label>
                        <select id="plataforma" name="plataforma" required>
                            <option value="">Elige una</option>
                            <?php foreach ($plataformas as $opcion): ?>
                                <option value="<?= htmlspecialchars($opcion) ?>" <?= $opcion === $datos['plataforma'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($opcion) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="monto">Monto ($)</label>
                        <input type="number" id="monto" name="monto" min="0.01" step="0.01" required
                               value="<?= htmlspecialchars($datos['monto']) ?>">
                    </div>

                    <div class="campo">
                        <label for="horas_trab">Horas</label>
                        <input type="number" id="horas_trab" name="horas_trab" min="0" max="999.99" step="0.01"
                               value="<?= htmlspecialchars($datos['horas_trab']) ?>">
                    </div>

                    <div class="campo">
                        <label for="km_recorr">Km</label>
                        <input type="number" id="km_recorr" name="km_recorr" min="0" step="0.01"
                               value="<?= htmlspecialchars($datos['km_recorr']) ?>">
                    </div>

                    <div class="campo">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" required
                               value="<?= htmlspecialchars($datos['fecha']) ?>">
                    </div>

                    <div class="campo campo-ancho">
                        <label for="notas">Notas (opcional)</label>
                        <input type="text" id="notas" name="notas" maxlength="500"
                               value="<?= htmlspecialchars($datos['notas']) ?>">
                    </div>

                    <button type="submit" class="btn btn-primario">Guardar ingreso</button>
                </form>
            </section>

            <section class="panel">
                <h2>Historial de ingresos</h2>

                <?php if (!$ingresos): ?>
                    <p class="vacio">Todavía no hay ingresos. Usa el formulario de arriba para registrar el primero.</p>
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
                                    <th scope="col">Notas</th>
                                    <th scope="col" class="acciones">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- PDO trajo las filas; PHP las convierte en <tr> -->
                                <?php foreach ($ingresos as $ingreso): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($ingreso['fecha'])) ?></td>
                                        <td><?= htmlspecialchars($ingreso['plataforma']) ?></td>
                                        <td class="num">$<?= number_format((float) $ingreso['monto'], 2) ?></td>
                                        <td class="num"><?= number_format((float) $ingreso['horas_trab'], 1) ?></td>
                                        <td class="num"><?= number_format((float) $ingreso['km_recorr'], 1) ?></td>
                                        <td><?= htmlspecialchars($ingreso['notas'] ?? '') ?></td>
                                        <td class="acciones">
                                            <!-- Eliminar es un POST con token CSRF; js/app.js pide confirmación antes de enviarlo -->
                                            <form method="post" action="ingresos.php" class="form-eliminar">
                                                <?= campo_csrf() ?>
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id" value="<?= (int) $ingreso['id'] ?>">
                                                <button type="submit" class="btn-eliminar">Eliminar</button>
                                            </form>
                                        </td>
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
