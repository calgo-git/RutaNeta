<?php
// =====================================================================
// gastos.php — Alta, listado, subtotales y eliminación de gastos
// Mismo patrón que ingresos.php:
//   POST accion=crear    → PHP valida y PDO hace el INSERT
//   POST accion=eliminar → DELETE con filtro de propiedad
//   GET                  → tabla de gastos + subtotales por categoría (GROUP BY)
// =====================================================================
require_once 'config/sesion.php';
requerir_login();
require_once 'config/conexion.php';

$usuario_id = $_SESSION['usuario_id'];

// Los 14 valores del ENUM de la tabla gastos (clave) con su texto para la pantalla (valor).
// El <select> del formulario y el del filtro se pintan desde este mismo arreglo.
$categorias = [
    'gasolina'              => 'Gasolina',
    'aceite'                => 'Aceite',
    'llantas'               => 'Llantas',
    'frenos'                => 'Frenos',
    'mantenimiento_general' => 'Mantenimiento general',
    'lavado'                => 'Lavado',
    'tenencia'              => 'Tenencia',
    'seguro'                => 'Seguro',
    'verificacion'          => 'Verificación',
    'multa'                 => 'Multa',
    'estacionamiento'       => 'Estacionamiento',
    'telefono'              => 'Teléfono',
    'datos_internet'        => 'Datos e internet',
    'otros'                 => 'Otros',
];

$errores = [];
$datos = [
    'categoria'   => '',
    'descripcion' => '',
    'monto'       => '',
    'fecha'       => date('Y-m-d'),
    'es_fijo'     => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if (!validar_csrf()) {
        $errores[] = ERROR_CSRF;

    } elseif ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);

        // Filtro de propiedad: solo se borra si el gasto pertenece al conductor en sesión
        $borrar = $pdo->prepare('DELETE FROM gastos WHERE id = :id AND usuario_id = :usuario_id');
        $borrar->execute(['id' => $id, 'usuario_id' => $usuario_id]);

        guardar_mensaje($borrar->rowCount() === 1 ? 'Gasto eliminado.' : 'No se encontró ese gasto en tu cuenta.');
        header('Location: gastos.php');
        exit;

    } elseif ($accion === 'crear') {
        $datos['categoria']   = $_POST['categoria'] ?? '';
        $datos['descripcion'] = trim($_POST['descripcion'] ?? '');
        $datos['monto']       = trim($_POST['monto'] ?? '');
        $datos['fecha']       = trim($_POST['fecha'] ?? '');
        // Un checkbox sin marcar no viaja en el POST: si no llegó, el gasto es variable
        $datos['es_fijo']     = isset($_POST['es_fijo']) ? 1 : 0;

        $monto = filter_var($datos['monto'], FILTER_VALIDATE_FLOAT);
        $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha']);

        // La categoría debe ser una de las 14 claves del ENUM
        if (!array_key_exists($datos['categoria'], $categorias)) {
            $errores[] = 'Elige una categoría de la lista.';
        }
        if (mb_strlen($datos['descripcion']) > 200) {
            $errores[] = 'La descripción no puede pasar de 200 caracteres.';
        }
        if ($monto === false || $monto <= 0 || $monto >= 100000000) {
            $errores[] = 'El monto debe ser mayor que 0.';
        }
        if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha']) {
            $errores[] = 'Escribe una fecha válida.';
        }

        if (!$errores) {
            $insertar = $pdo->prepare(
                'INSERT INTO gastos (usuario_id, categoria, descripcion, monto, fecha, es_fijo)
                 VALUES (:usuario_id, :categoria, :descripcion, :monto, :fecha, :es_fijo)'
            );
            $insertar->execute([
                'usuario_id'  => $usuario_id,   // de la sesión, nunca del formulario
                'categoria'   => $datos['categoria'],
                'descripcion' => $datos['descripcion'] === '' ? null : $datos['descripcion'],
                'monto'       => $monto,
                'fecha'       => $datos['fecha'],
                'es_fijo'     => $datos['es_fijo'],
            ]);

            guardar_mensaje('Gasto guardado.');
            header('Location: gastos.php');
            exit;
        }
    }
}

// Todos los gastos del conductor, del más reciente al más antiguo
$consulta = $pdo->prepare(
    'SELECT id, categoria, descripcion, monto, fecha, es_fijo
     FROM gastos
     WHERE usuario_id = :usuario_id
     ORDER BY fecha DESC, id DESC'
);
$consulta->execute(['usuario_id' => $usuario_id]);
$gastos = $consulta->fetchAll();

// Subtotales por categoría: GROUP BY junta las filas de cada categoría y SUM las suma.
// La BD hace la cuenta; PHP solo la pinta.
$consulta = $pdo->prepare(
    'SELECT categoria, COUNT(*) AS cantidad, SUM(monto) AS subtotal
     FROM gastos
     WHERE usuario_id = :usuario_id
     GROUP BY categoria
     ORDER BY subtotal DESC'
);
$consulta->execute(['usuario_id' => $usuario_id]);
$subtotales = $consulta->fetchAll();

// Total general para calcular el porcentaje de cada categoría
$total_gastos = 0;
foreach ($subtotales as $fila) {
    $total_gastos += (float) $fila['subtotal'];
}

$mensaje = tomar_mensaje();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos · RutaNeta</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div class="contenedor">
        <aside class="menu-lateral">
            <a href="dashboard.php" class="menu-logo"><img src="logo.png" alt="RutaNeta, ir al panel"></a>
            <nav class="menu-nav" aria-label="Secciones">
                <a href="dashboard.php">Panel</a>
                <a href="ingresos.php">Ingresos</a>
                <a href="gastos.php" class="activo" aria-current="page">Gastos</a>
                <a href="historial.php">Historial</a>
            </nav>
            <div class="menu-usuario">
                <span>Hola, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong></span>
                <a href="logout.php">Cerrar sesión</a>
            </div>
        </aside>

        <main class="principal">
            <header class="encabezado">
                <h1>Gastos</h1>
                <p>Todo lo que cuesta trabajar: gasolina, mantenimiento, seguro, datos... Cada gasto se resta de tu ganancia.</p>
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
                <h2>Registrar gasto</h2>
                <form method="post" action="gastos.php" class="form-alta">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="accion" value="crear">

                    <div class="campo">
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Elige una</option>
                            <?php foreach ($categorias as $valor => $texto): ?>
                                <option value="<?= htmlspecialchars($valor) ?>" <?= $valor === $datos['categoria'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($texto) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo campo-ancho">
                        <label for="descripcion">Descripción (opcional)</label>
                        <input type="text" id="descripcion" name="descripcion" maxlength="200"
                               value="<?= htmlspecialchars($datos['descripcion']) ?>">
                    </div>

                    <div class="campo">
                        <label for="monto">Monto ($)</label>
                        <input type="number" id="monto" name="monto" min="0.01" step="0.01" required
                               value="<?= htmlspecialchars($datos['monto']) ?>">
                    </div>

                    <div class="campo">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" required
                               value="<?= htmlspecialchars($datos['fecha']) ?>">
                    </div>

                    <!-- es_fijo es solo una etiqueta: el sistema no repite el gasto cada mes -->
                    <div class="campo campo-check">
                        <input type="checkbox" id="es_fijo" name="es_fijo" value="1" <?= $datos['es_fijo'] ? 'checked' : '' ?>>
                        <label for="es_fijo">Es fijo (se repite cada mes)</label>
                    </div>

                    <button type="submit" class="btn btn-primario">Guardar gasto</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-cabecera">
                    <h2>Tus gastos</h2>
                    <?php if ($gastos): ?>
                        <!-- js/app.js escucha este <select> y oculta las filas de otras categorías -->
                        <div class="barra-filtro">
                            <label for="filtro-categoria">Filtrar por categoría</label>
                            <select id="filtro-categoria">
                                <option value="todas">Todas</option>
                                <?php foreach ($categorias as $valor => $texto): ?>
                                    <option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($texto) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!$gastos): ?>
                    <p class="vacio">Todavía no hay gastos. Registra el primero con el formulario de arriba.</p>
                <?php else: ?>
                    <div class="tabla-scroll">
                        <table class="tabla tabla-seleccionable" id="tabla-gastos">
                            <thead>
                                <tr>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Categoría</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col" class="num">Monto</th>
                                    <th scope="col" class="acciones">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gastos as $gasto): ?>
                                    <!-- data-categoria es la clave del ENUM: el filtro de JS la compara con el <select> -->
                                    <tr data-categoria="<?= htmlspecialchars($gasto['categoria']) ?>">
                                        <td><?= date('d/m/Y', strtotime($gasto['fecha'])) ?></td>
                                        <td><?= htmlspecialchars($categorias[$gasto['categoria']]) ?></td>
                                        <td><?= htmlspecialchars($gasto['descripcion'] ?? '') ?></td>
                                        <td>
                                            <?php if ($gasto['es_fijo']): ?>
                                                <span class="etiqueta etiqueta-fijo">Fijo</span>
                                            <?php else: ?>
                                                <span class="etiqueta etiqueta-variable">Variable</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="num">$<?= number_format((float) $gasto['monto'], 2) ?></td>
                                        <td class="acciones">
                                            <form method="post" action="gastos.php" class="form-eliminar">
                                                <?= campo_csrf() ?>
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id" value="<?= (int) $gasto['id'] ?>">
                                                <button type="submit" class="btn-eliminar">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p id="sin-resultados" class="vacio oculto">No tienes gastos en esa categoría.</p>
                <?php endif; ?>
            </section>

            <?php if ($subtotales): ?>
                <section class="panel">
                    <h2>Subtotales por categoría</h2>
                    <div class="tabla-scroll">
                        <table class="tabla">
                            <thead>
                                <tr>
                                    <th scope="col">Categoría</th>
                                    <th scope="col" class="num">Registros</th>
                                    <th scope="col" class="num">Subtotal</th>
                                    <th scope="col" class="num">% del total</th>
                                    <th scope="col">Peso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subtotales as $fila): ?>
                                    <?php $porcentaje = $total_gastos > 0 ? (float) $fila['subtotal'] / $total_gastos * 100 : 0; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($categorias[$fila['categoria']]) ?></td>
                                        <td class="num"><?= (int) $fila['cantidad'] ?></td>
                                        <td class="num">$<?= number_format((float) $fila['subtotal'], 2) ?></td>
                                        <td class="num"><?= number_format($porcentaje, 1) ?>%</td>
                                        <!-- El ancho depende del dato, por eso va como estilo en línea -->
                                        <td><div class="barra"><span style="width: <?= number_format($porcentaje, 1, '.', '') ?>%"></span></div></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th scope="row">Total</th>
                                    <td class="num"><?= count($gastos) ?></td>
                                    <td class="num">$<?= number_format($total_gastos, 2) ?></td>
                                    <td class="num">100%</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <script src="js/app.js"></script>
</body>
</html>
