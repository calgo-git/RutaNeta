<?php
// =====================================================================
// config/sesion.php — Sesión, guardia de acceso y tokens CSRF
// Todas las páginas PHP lo incluyen en su PRIMERA línea de código:
//     require_once 'config/sesion.php';
// Tiene que ir antes de cualquier HTML: session_start() y header() envían
// cabeceras al navegador y fallan si ya se imprimió algo.
// =====================================================================

// Arranca (o retoma) la sesión. Con esto $_SESSION recuerda al conductor
// mientras pasa de login.php a dashboard.php, ingresos.php, gastos.php...
if (session_status() === PHP_SESSION_NONE) {
    // Revisión de seguridad (etapa 6): la cookie de sesión se endurece antes de crearla.
    //   httponly → JavaScript no puede leer la cookie: un XSS no se roba la sesión.
    //   samesite Lax → el navegador no manda la cookie en POSTs que vienen de
    //   otros sitios: segunda barrera contra CSRF, además del token.
    session_set_cookie_params([
        'lifetime' => 0,          // dura hasta cerrar el navegador
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Nadie puede cargar RutaNeta dentro de un <iframe> de otro sitio para engañar
// al conductor y hacerle pulsar "Eliminar" sin que lo note (clickjacking).
header('X-Frame-Options: DENY');

// Mensaje que ven los formularios cuando el token CSRF no coincide
const ERROR_CSRF = 'El formulario expiró o no vino de RutaNeta. Recarga la página e inténtalo de nuevo.';

// ---------------------------------------------------------------------
// Guardia de acceso: las páginas internas la llaman al inicio.
// Sin conductor en sesión, redirige a login.php. El exit corta la ejecución
// para que no se procese ni se imprima nada de la página protegida.
// ---------------------------------------------------------------------
function requerir_login(): void
{
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

// ---------------------------------------------------------------------
// Token CSRF: un valor secreto por sesión que viaja oculto en cada formulario.
// Otro sitio puede hacer que tu navegador envíe un POST a RutaNeta, pero no
// puede leer este token; sin él, el POST se rechaza.
// ---------------------------------------------------------------------
function token_csrf(): string
{
    // Se crea una sola vez por sesión: 32 bytes aleatorios → 64 caracteres hex
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Campo oculto que se imprime dentro de cada <form method="post">
function campo_csrf(): string
{
    return '<input type="hidden" name="csrf_token" value="' . token_csrf() . '">';
}

// Cada página llama a esta función antes de procesar un POST.
// hash_equals() compara en tiempo constante: no da pistas sobre el token.
function validar_csrf(): bool
{
    $enviado = $_POST['csrf_token'] ?? '';
    if (!is_string($enviado) || !hash_equals(token_csrf(), $enviado)) {
        http_response_code(403);   // 403 = prohibido: el navegador y los logs lo registran
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------
// Mensajes "flash": se guardan justo antes de redirigir y la página
// siguiente los muestra una sola vez ("Ingreso guardado", "Cuenta creada").
// ---------------------------------------------------------------------
function guardar_mensaje(string $texto): void
{
    $_SESSION['mensaje'] = $texto;
}

function tomar_mensaje(): string
{
    $texto = $_SESSION['mensaje'] ?? '';
    unset($_SESSION['mensaje']);
    return $texto;
}
