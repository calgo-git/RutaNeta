# RutaNeta

Sistema web de gestión financiera para conductores de plataformas digitales: Rappi, DiDi, Uber, Mercado Libre Envíos y Amazon Flex.

## El problema

Un conductor ve que la plataforma le depositó $3,000 en la semana y cree que ganó $3,000. No descuenta la gasolina, el aceite, las llantas, la tenencia, el seguro, los lavados, las multas ni los datos móviles. RutaNeta registra ingresos y gastos y calcula la **ganancia neta real**:

```
Ganancia neta del mes = ingresos del mes − gastos del mes
```

## Estado del proyecto

| Etapa | Rama | Contenido | Estado |
|-------|------|-----------|--------|
| 1 | `etapa-1-base` | Estructura, base de datos y conexión PDO | Lista |
| 2 | `etapa-2-landing` | Landing page con CSS Grid | Lista |
| 3 | `etapa-3-auth` | Registro, login, logout y protección CSRF | Lista |
| 4 | `etapa-4-dashboard-ingresos` | Dashboard con KPIs y módulo de ingresos | Lista |
| 5 | `etapa-5-gastos-historial` | Módulo de gastos e historial mensual | Lista |
| 6 | `etapa-6-final` | Integración, pruebas de seguridad y entrega | Lista |

Cada rama nace de la anterior y funciona por sí sola. `main` contiene la entrega final (v1.0), que llegó con el merge de `etapa-6-final`.

El sistema está completo y probado: landing page, registro, login y logout, panel con KPI, ingresos, gastos e historial. Las secciones de instalación y pruebas están al final de este archivo.

## Landing page

`index.html` y `styles.css` forman la página de presentación. El esqueleto es CSS Grid con 5 áreas:

```
┌──────────┬──────────────────────────────────┐
│  logo    │  header (banner)                 │
├──────────┼──────────────────────────────────┤
│ sidebar  │  cuerpo: problema, tabla,        │
│          │  beneficios, registro rápido     │
├──────────┴──────────────────────────────────┤
│  footer (ancho completo)                    │
└─────────────────────────────────────────────┘
```

En pantallas de menos de 768 px el mismo HTML se acomoda en una sola columna: solo cambia el mapa de `grid-template-areas`. Dentro de cada bloque se usa Flexbox. La landing no usa JavaScript.

El formulario "Empieza gratis" manda nombre y email por GET a `registro.php`, que los precarga en sus campos.

## Acceso: registro, login y sesión

| Archivo | Qué hace |
|---------|----------|
| `config/sesion.php` | Arranca la sesión en cada página y ofrece `requerir_login()`, el token CSRF y los mensajes que se muestran una sola vez |
| `registro.php` | Valida el formulario en PHP, revisa que el email no exista, guarda la contraseña con `password_hash()` y redirige a `login.php` |
| `login.php` | Busca el email con PDO, compara con `password_verify()`, regenera el id de sesión y guarda `usuario_id` y `nombre` en `$_SESSION` |
| `logout.php` | Vacía la sesión, borra la cookie, destruye la sesión y regresa a `login.php` |
| `dashboard.php` | Panel principal protegido (ver la sección siguiente) |

Medidas de seguridad del acceso:

- Las contraseñas se guardan como hash; la base de datos nunca tiene la contraseña real.
- `session_regenerate_id(true)` al entrar: un id de sesión anterior ya no sirve.
- La cookie de sesión es `HttpOnly` (JavaScript no la puede leer) y `SameSite=Lax` (no viaja en POSTs que vienen de otros sitios).
- Cada formulario POST lleva un token CSRF oculto que PHP compara con `hash_equals()`; sin él, responde 403 y no procesa nada.
- `requerir_login()` manda a `login.php` a quien abra una página interna sin sesión.
- Todo lo que se imprime pasa por `htmlspecialchars()`.
- La cabecera `X-Frame-Options: DENY` impide cargar RutaNeta dentro de un iframe de otro sitio.

JavaScript (`js/app.js`) ayuda en el navegador: revisa que las dos contraseñas del registro coincidan antes de enviarlas y muestra u oculta la contraseña con el botón **Ver**. PHP vuelve a validar todo en el servidor.

## Panel e ingresos

Las páginas internas comparten un layout con Flexbox: menú lateral a la izquierda (con el logo sobre un bloque blanco) y área principal a la derecha. En pantallas angostas el menú pasa arriba.

**`dashboard.php`** muestra 4 indicadores del mes en curso, calculados por la base de datos:

| Indicador | Cálculo |
|-----------|---------|
| Ingresos del mes | Suma de `ingresos.monto` con fecha en el mes |
| Gastos del mes | Suma de `gastos.monto` con fecha en el mes (fijos y variables) |
| Ganancia neta | Ingresos del mes − gastos del mes; verde si hay ganancia, rojo si hay pérdida |
| Horas trabajadas | Suma de `ingresos.horas_trab` del mes, con la ganancia neta por hora |

Debajo aparece la tabla de los últimos 5 ingresos.

**`ingresos.php`** atiende tres casos en un solo archivo:

- **Registrar:** plataforma, monto, horas, kilómetros, fecha y notas. PHP valida que el monto sea mayor que 0, que horas y kilómetros sean 0 o más, que la fecha exista y que la plataforma esté en la lista. Si algo falla, muestra el error y no inserta nada.
- **Listar:** todos los ingresos del conductor, del más reciente al más antiguo.
- **Eliminar:** un POST con token CSRF. El `DELETE` filtra por el `usuario_id` de la sesión: cambiar el id en el formulario no borra registros de otra cuenta.

Después de guardar o borrar, PHP redirige a la misma página (Post/Redirect/Get): recargar no duplica el registro.

En el navegador, `js/app.js` pide confirmación antes de eliminar y resalta la fila en la que haces clic. El hover de las filas es solo CSS.

## Gastos e historial

**`gastos.php`** sigue el mismo patrón que ingresos (registrar, listar y eliminar con las mismas reglas de seguridad):

- El formulario pide categoría, descripción, monto, fecha y si el gasto es fijo. Las 14 categorías del `<select>` salen del mismo arreglo PHP que valida el servidor, y sus claves son idénticas al `ENUM` de la tabla `gastos`.
- `es_fijo` solo es una etiqueta (Fijo / Variable): el sistema no repite gastos solo, cada mes se registran.
- Los subtotales por categoría los calcula MySQL con `GROUP BY` y `SUM`, con el porcentaje de cada categoría sobre el total.
- El filtro por categoría oculta y muestra filas con una clase CSS, sin recargar la página.

**`historial.php`** compara meses:

- El `<input type="month">` manda el mes por GET (por defecto, el mes actual). Solo consulta, así que no necesita token CSRF.
- MySQL suma ingresos y gastos de los 6 meses que terminan en el mes elegido con `GROUP BY` por mes; los meses sin movimientos salen en cero.
- La tabla muestra Ingresos | Gastos | Neto por mes, más el total de los 6 meses.
- PHP deja cada neto en el atributo `data-neto`; al cargar la página, `js/app.js` lo pinta en verde si es positivo y en rojo si es negativo.

## Tecnologías

- HTML5, CSS3 y JavaScript vanilla, sin frameworks ni librerías
- CSS Grid en la landing y Flexbox en el sistema interno
- PHP 8 sin frameworks, con PDO y consultas preparadas
- MySQL/MariaDB en XAMPP
- Git y GitHub, una rama por etapa

## Estructura

```
RutaNeta/
├── index.html           ← Landing page (CSS Grid)
├── styles.css           ← Estilos de la landing
├── login.php            ← Inicio de sesión
├── registro.php         ← Crear cuenta
├── logout.php           ← Destruir sesión
├── dashboard.php        ← Panel principal con KPIs
├── ingresos.php         ← Formulario y tabla de ingresos
├── gastos.php           ← Formulario y tabla de gastos
├── historial.php        ← Reporte mensual comparativo
├── logo.png             ← Logo
├── README.md            ← Este archivo
├── config/
│   ├── conexion.php     ← Conexión PDO a MySQL
│   └── sesion.php       ← Guardia de sesión y tokens CSRF
├── css/
│   └── estilos.css      ← Estilos del sistema interno
├── js/
│   └── app.js           ← JS vanilla: validaciones y UI
└── sql/
    └── rutaneta.sql     ← Script completo de la BD
```

## Base de datos

`sql/rutaneta.sql` crea la base `rutaneta` con tres tablas:

| Tabla | Para qué sirve |
|-------|----------------|
| `usuarios` | Un conductor por fila; la contraseña se guarda como hash |
| `ingresos` | Pagos de las plataformas, con horas y kilómetros |
| `gastos` | Gastos de trabajo en 14 categorías; `es_fijo` marca los recurrentes |

`ingresos` y `gastos` apuntan a `usuarios` con llave foránea y `ON DELETE CASCADE`. Los montos tienen reglas `CHECK` y ambas tablas tienen un índice por `(usuario_id, fecha)`.

Los datos de prueba se fechan el día que importas el script, repartidos en el mes actual y el anterior:

| Mes | Ingresos | Gastos | Neto |
|-----|----------|--------|------|
| Mes anterior | $3,810.50 | $4,499.00 | −$688.50 |
| Mes actual | $3,080.00 | $2,389.00 | $691.00 |

## Requisitos

- XAMPP con Apache, PHP 8 y MySQL/MariaDB
- Git, para clonar el repositorio
- Un navegador actualizado

## Instalación en XAMPP

1. **Clona el proyecto dentro de `htdocs`**, la carpeta que sirve Apache:

   ```bash
   cd C:/xampp/htdocs
   git clone https://github.com/calgo-git/RutaNeta.git
   ```

   En macOS la carpeta es `/Applications/XAMPP/htdocs`. El proyecto debe quedar en `htdocs/RutaNeta`.

2. **Enciende los servicios:** en el panel de control de XAMPP pulsa **Start** en **Apache** y en **MySQL**.

3. **Importa la base de datos:**
   1. Abre `http://localhost/phpmyadmin`.
   2. Entra a la pestaña **Importar**.
   3. Elige `sql/rutaneta.sql` y pulsa **Importar**.

   El script crea la base `rutaneta`, sus 3 tablas y los datos de prueba. Si lo importas otra vez, borra las tablas y las crea de nuevo: sirve para regresar a los datos de prueba.

4. **Configura la conexión** en `config/conexion.php`. Los valores por defecto sirven para una instalación normal de XAMPP:

   ```php
   $servidor   = 'localhost';
   $puerto     = '3306';
   $base_datos = 'rutaneta';
   $usuario_bd = 'root';
   $clave_bd   = '';
   ```

   Cambia `$clave_bd` si le pusiste contraseña a `root`, o `$puerto` si moviste MySQL de puerto (por ejemplo, a 3307). Para comprobar la conexión abre `http://localhost/RutaNeta/config/conexion.php`: una página en blanco significa que conecta.

5. **Abre el proyecto** en `http://localhost/RutaNeta/`.

## Usuario de prueba

| Email | Contraseña |
|-------|------------|
| `demo@rutaneta.test` | `demo1234` |

Es la única contraseña del repositorio y existe solo para pruebas. Con los datos recién importados, su panel muestra $3,080.00 de ingresos, $2,389.00 de gastos, $691.00 de ganancia neta y 16.0 horas.

## Recorrido de prueba

1. En la landing escribe nombre y email en "Empieza gratis": llegas al registro con esos datos puestos.
2. Crea la cuenta (si las contraseñas no coinciden, JS avisa sin recargar) y entra con ella.
3. En **Ingresos** registra un pago; en **Gastos** registra un gasto marcado como fijo.
4. En **Panel** revisa que los 4 indicadores cambiaron.
5. En **Historial** compara el mes actual con los anteriores.
6. Elimina un registro: el sistema pide confirmación.
7. Cierra sesión.

## Pruebas de seguridad

Resultados del 23/11/2026, probados con PHP 8.3 y MariaDB 10.11 (los mismos motores de XAMPP) y un navegador real. No se ejecutaron dentro de XAMPP.

| Prueba | Cómo se hizo | Resultado |
|--------|--------------|-----------|
| Abrir páginas internas sin sesión | Pedir `dashboard.php`, `ingresos.php`, `gastos.php` e `historial.php` sin haber iniciado sesión | Las 4 responden con una redirección a `login.php`. **Pasa** |
| Borrar un registro de otro usuario cambiando el `id` | Con la cuenta de otro usuario, cambiar con F12 el `<input name="id">` de una fila por `1` (un registro del usuario demo) y pulsar Eliminar, en ingresos y en gastos | Mensaje "No se encontró ese ingreso / gasto en tu cuenta." y el registro del demo sigue en la BD. **Pasa** |
| Enviar un formulario sin token CSRF | Borrar con F12 el `<input name="csrf_token">` y enviar registro, login, alta de ingreso y alta de gasto | Los 4 responden 403 con "El formulario expiró o no vino de RutaNeta…" y no guardan nada. **Pasa** |
| Inyección SQL en el login | Escribir `' OR '1'='1` como email y contraseña | "Email o contraseña incorrectos." **Pasa** |
| HTML en los campos de texto | Guardar `<script>alert(1)</script>` en las notas de un ingreso | Se muestra como texto, no se ejecuta. **Pasa** |
| Cookie de sesión | Revisar `PHPSESSID` en F12 → Application → Cookies | `HttpOnly` y `SameSite=Lax`; `document.cookie` no la ve. **Pasa** |
| RutaNeta dentro de un iframe | Cargar `login.php` en un `<iframe>` de otra página | El navegador lo bloquea por `X-Frame-Options: DENY`. **Pasa** |

## Revisión de consistencia

| Lista | Base de datos | Formularios | JavaScript |
|-------|---------------|-------------|------------|
| Plataformas (6) | `VARCHAR(50)`; PHP solo acepta la lista | El mismo arreglo de 6 valores en `registro.php` e `ingresos.php` | No tiene lista propia |
| Vehículos (4) | `VARCHAR(50)`; PHP solo acepta la lista | Arreglo en `registro.php` | No tiene lista propia |
| Categorías (14) | `ENUM` en `sql/rutaneta.sql` | `$categorias` en `gastos.php`, con las mismas 14 claves y en el mismo orden; el formulario y el filtro salen de ahí | Lee `data-categoria` del HTML |

## Seguridad aplicada

- Contraseñas con `password_hash()` y `password_verify()`.
- `requerir_login()` al inicio de cada página interna.
- Todas las consultas con PDO, `prepare()` y parámetros.
- Toda lectura y borrado filtra por el `usuario_id` de la sesión, nunca por un dato del formulario o de la URL.
- Token CSRF en todos los formularios POST, comparado con `hash_equals()`.
- Eliminar siempre es POST con token CSRF.
- Salidas escapadas con `htmlspecialchars()`.
- Sesión regenerada al iniciar sesión, cookie `HttpOnly` y `SameSite=Lax`, y `X-Frame-Options: DENY`.
