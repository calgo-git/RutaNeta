<?php
// =====================================================================
// registro.php — Crear cuenta
// Flujo: formulario → PHP valida → PDO revisa que el email no exista →
//        password_hash() → INSERT → redirige a login.php con mensaje.
// =====================================================================
require_once 'config/sesion.php';
require_once 'config/conexion.php';

// Con la sesión abierta no tiene sentido crear otra cuenta
if (!empty($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Listas cerradas: PHP solo acepta estas opciones, las mismas que pinta el <select>
$plataformas = ['Rappi', 'DiDi', 'Uber', 'Mercado Libre Envíos', 'Amazon Flex', 'Otra'];
$vehiculos   = ['Auto', 'Motocicleta', 'Bicicleta', 'Otro'];

$errores = [];

// Valores iniciales. Si vienes del formulario de la landing, index.html
// mandó nombre y email por GET y aquí se precargan.
$nombre     = trim($_GET['nombre'] ?? '');
$email      = trim($_GET['email'] ?? '');
$plataforma = '';
$vehiculo   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $plataforma = $_POST['plataforma'] ?? '';
    $vehiculo   = $_POST['vehiculo'] ?? '';
    $password   = $_POST['password'] ?? '';
    $confirmar  = $_POST['confirmar'] ?? '';

    if (!validar_csrf()) {
        $errores[] = ERROR_CSRF;
    } else {
        // Validaciones del servidor: JS ayuda al usuario, pero PHP es quien decide
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $errores[] = 'Escribe tu nombre (máximo 100 caracteres).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errores[] = 'Escribe un email válido.';
        }
        if (!in_array($plataforma, $plataformas, true)) {
            $errores[] = 'Elige una plataforma de la lista.';
        }
        if (!in_array($vehiculo, $vehiculos, true)) {
            $errores[] = 'Elige un vehículo de la lista.';
        }
        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $confirmar) {
            $errores[] = 'Las contraseñas no coinciden.';
        }
    }

    // El email no se puede repetir: se revisa antes de insertar para dar un mensaje claro
    if (!$errores) {
        $consulta = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
        $consulta->execute(['email' => $email]);
        if ($consulta->fetch()) {
            $errores[] = 'Ya existe una cuenta con ese email. Inicia sesión con ella.';
        }
    }

    if (!$errores) {
        // password_hash() guarda una huella irreversible: quien vea la BD no ve la contraseña
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $insertar = $pdo->prepare(
            'INSERT INTO usuarios (nombre, email, password, plataforma, vehiculo)
             VALUES (:nombre, :email, :password, :plataforma, :vehiculo)'
        );
        $insertar->execute([
            'nombre'     => $nombre,
            'email'      => $email,
            'password'   => $hash,
            'plataforma' => $plataforma,
            'vehiculo'   => $vehiculo,
        ]);

        // Post/Redirect/Get: después de guardar se redirige. Si el usuario
        // recarga login.php, el navegador no vuelve a enviar el registro.
        guardar_mensaje('¡Cuenta creada! Ya puedes iniciar sesión.');
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta · RutaNeta</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="pagina-acceso">
    <main class="tarjeta-acceso">
        <a href="index.html" class="acceso-logo"><img src="logo.png" alt="RutaNeta, ir a la página principal"></a>
        <h1>Crea tu cuenta</h1>
        <p class="acceso-intro">Registra lo que ganas y lo que gastas. RutaNeta te dice cuánto te queda de verdad.</p>

        <?php if ($errores): ?>
            <!-- PHP pinta aquí los errores de validación; el formulario conserva lo escrito -->
            <div class="alerta alerta-error" role="alert">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- id="form-registro": js/app.js lo usa para revisar las contraseñas antes del envío -->
        <form method="post" action="registro.php" id="form-registro" class="formulario">
            <?= campo_csrf() ?>

            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required
                       autocomplete="name" value="<?= htmlspecialchars($nombre) ?>">
            </div>

            <div class="campo">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="150" required
                       autocomplete="email" value="<?= htmlspecialchars($email) ?>">
            </div>

            <div class="fila-campos">
                <div class="campo">
                    <label for="plataforma">Plataforma principal</label>
                    <select id="plataforma" name="plataforma" required>
                        <option value="">Elige una</option>
                        <?php foreach ($plataformas as $opcion): ?>
                            <option value="<?= htmlspecialchars($opcion) ?>" <?= $opcion === $plataforma ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opcion) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="vehiculo">Vehículo</label>
                    <select id="vehiculo" name="vehiculo" required>
                        <option value="">Elige uno</option>
                        <?php foreach ($vehiculos as $opcion): ?>
                            <option value="<?= htmlspecialchars($opcion) ?>" <?= $opcion === $vehiculo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opcion) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <!-- data-objetivo dice a JS qué input debe mostrar u ocultar este botón -->
                <div class="campo-password">
                    <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
                    <button type="button" class="btn-ver" data-objetivo="password" aria-pressed="false">Ver</button>
                </div>
                <small>Mínimo 8 caracteres.</small>
            </div>

            <div class="campo">
                <label for="confirmar">Confirma tu contraseña</label>
                <div class="campo-password">
                    <input type="password" id="confirmar" name="confirmar" minlength="8" required autocomplete="new-password">
                    <button type="button" class="btn-ver" data-objetivo="confirmar" aria-pressed="false">Ver</button>
                </div>
            </div>

            <!-- JS escribe aquí el aviso y le quita la clase "oculto" sin recargar la página -->
            <p id="aviso-password" class="aviso-error oculto" role="alert"></p>

            <button type="submit" class="btn btn-primario btn-bloque">Crear cuenta</button>
        </form>

        <p class="acceso-pie">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
    </main>

    <script src="js/app.js"></script>
</body>
</html>
