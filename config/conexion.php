<?php
// =====================================================================
// config/conexion.php — Conexión PDO a MySQL/MariaDB de XAMPP
// Toda página que lee o guarda datos hace:  require_once 'config/conexion.php';
// y desde ese momento usa la variable $pdo para preparar sus consultas.
// =====================================================================

// Zona horaria de México. XAMPP trae Europe/Berlin en php.ini: sin esta línea,
// después de las 5 p. m. PHP creería que ya es mañana, y el "mes actual" del
// dashboard y la fecha por defecto de los formularios saldrían mal.
date_default_timezone_set('America/Mexico_City');

// Datos de la instalación de XAMPP. Cámbialos si tu MySQL usa otro puerto o contraseña.
$servidor   = 'localhost';
$puerto     = '3306';
$base_datos = 'rutaneta';
$usuario_bd = 'root';
$clave_bd   = '';            // XAMPP instala root sin contraseña

// El DSN le dice a PDO qué motor usar, dónde está y con qué BD trabajar.
// charset=utf8mb4: acentos y eñes viajan bien entre PHP y MySQL.
$dsn = "mysql:host=$servidor;port=$puerto;dbname=$base_datos;charset=utf8mb4";

$opciones = [
    // Si una consulta falla, PDO lanza una excepción en vez de fallar en silencio
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // fetch() devuelve arreglos asociativos: $fila['monto']
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Consultas preparadas reales en MySQL: el SQL y los datos viajan por separado
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $usuario_bd, $clave_bd, $opciones);
} catch (PDOException $e) {
    // El detalle técnico va al log de Apache; en pantalla solo un mensaje claro.
    error_log('RutaNeta: no se pudo conectar a MySQL: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa que MySQL esté encendido en XAMPP y los datos de config/conexion.php.');
}
