<?php
// =====================================================================
// login.php — Inicio de sesión
// Flujo: formulario → PDO busca el email → password_verify() compara →
//        session_regenerate_id() → $_SESSION guarda al conductor → dashboard.php
// =====================================================================
require_once 'config/sesion.php';
require_once 'config/conexion.php';

// Si ya hay sesión, directo al panel
if (!empty($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

// Mensaje que dejó registro.php antes de redirigir ("¡Cuenta creada!")
$mensaje = tomar_mensaje();

// logout.php redirige aquí con ?salida=1
if (isset($_GET['salida'])) {
    $mensaje = 'Cerraste sesión. ¡Buen camino!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!validar_csrf()) {
        $error = ERROR_CSRF;
    } elseif ($email === '' || $password === '') {
        $error = 'Escribe tu email y tu contraseña.';
    } else {
        $consulta = $pdo->prepare('SELECT id, nombre, password FROM usuarios WHERE email = :email');
        $consulta->execute(['email' => $email]);
        $usuario = $consulta->fetch();

        // password_verify() saca la huella de lo que escribiste y la compara con el hash guardado
        if ($usuario && password_verify($password, $usuario['password'])) {
            // Id de sesión nuevo al entrar: si alguien conocía el anterior, ya no le sirve
            session_regenerate_id(true);

            // Desde aquí, todas las páginas saben quién es el conductor
            $_SESSION['usuario_id'] = (int) $usuario['id'];
            $_SESSION['nombre']     = $usuario['nombre'];

            header('Location: dashboard.php');
            exit;
        }

        // Mismo mensaje si el email no existe o si la contraseña falla:
        // así nadie puede averiguar qué emails tienen cuenta
        $error = 'Email o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión · RutaNeta</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="pagina-acceso">
    <main class="tarjeta-acceso">
        <a href="index.html" class="acceso-logo"><img src="logo.png" alt="RutaNeta, ir a la página principal"></a>
        <h1>Inicia sesión</h1>
        <p class="acceso-intro">Revisa cuánto llevas ganado este mes.</p>

        <?php if ($mensaje !== ''): ?>
            <p class="alerta alerta-exito" role="status"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="alerta alerta-error" role="alert"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="login.php" class="formulario">
            <?= campo_csrf() ?>

            <div class="campo">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="150" required
                       autocomplete="email" value="<?= htmlspecialchars($email) ?>">
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <div class="campo-password">
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <button type="button" class="btn-ver" data-objetivo="password" aria-pressed="false">Ver</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primario btn-bloque">Entrar</button>
        </form>

        <p class="acceso-pie">¿No tienes cuenta? <a href="registro.php">Crea una gratis</a></p>
    </main>

    <script src="js/app.js"></script>
</body>
</html>
