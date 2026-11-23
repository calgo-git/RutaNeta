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
| 5 | `etapa-5-gastos-historial` | Módulo de gastos e historial mensual | Pendiente |
| 6 | `etapa-6-final` | Integración, pruebas de seguridad y entrega | Pendiente |

Cada rama nace de la anterior y funciona por sí sola. `main` solo recibe el merge de la entrega final.

Hasta esta etapa existen la base de datos, la conexión, la landing page, el acceso (registro, login y logout), el panel con KPI y el módulo de ingresos. Gastos e Historial ya son páginas protegidas dentro del menú, con el aviso "Esta sección llega en la etapa 5", así ningún enlace termina en una página en blanco.

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

Medidas de seguridad de esta etapa:

- Las contraseñas se guardan como hash; la base de datos nunca tiene la contraseña real.
- `session_regenerate_id(true)` al entrar: un id de sesión anterior ya no sirve.
- Cada formulario POST lleva un token CSRF oculto que PHP compara con `hash_equals()`; sin él, responde 403 y no procesa nada.
- `requerir_login()` manda a `login.php` a quien abra una página interna sin sesión.
- Todo lo que se imprime pasa por `htmlspecialchars()`.

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

## Instalación en XAMPP

1. Clona el repositorio dentro de `htdocs` para que Apache lo sirva:

   ```bash
   cd C:/xampp/htdocs
   git clone https://github.com/calgo-git/RutaNeta.git
   ```

2. En el panel de XAMPP pulsa **Start** en **Apache** y en **MySQL**.
3. Abre `http://localhost/phpmyadmin`, entra a la pestaña **Importar**, elige `sql/rutaneta.sql` y pulsa **Importar**. Si lo vuelves a importar, borra las tablas y las crea de nuevo con los datos de prueba.
4. Revisa `config/conexion.php`. Los valores por defecto (`localhost`, puerto `3306`, usuario `root` sin contraseña) sirven para una instalación normal de XAMPP.

## Probar esta etapa

- `http://localhost/RutaNeta/config/conexion.php` debe mostrar una **página en blanco**: la conexión funciona. Si aparece "No se pudo conectar…", revisa que MySQL esté encendido y los datos de `config/conexion.php`.
- En phpMyAdmin, la base `rutaneta` debe tener 1 usuario, 5 ingresos y 8 gastos.
- `http://localhost/RutaNeta/` muestra la landing: logo, banner azul noche, menú lateral, contenido y footer de ancho completo.
- Al angostar la ventana por debajo de 768 px, la landing pasa a una sola columna.
- "Empieza gratis" lleva al registro con nombre y email ya puestos.
- En el registro, dos contraseñas distintas muestran un aviso sin recargar la página. Al crear la cuenta llegas al login con el mensaje "¡Cuenta creada!", y en phpMyAdmin la columna `password` empieza con `$2y$`.
- Entra con el usuario de prueba. Con los datos recién importados, el panel muestra $3,080.00 de ingresos, $2,389.00 de gastos, $691.00 de ganancia neta (en verde) y 16.0 horas.
- En **Ingresos** registra uno: aparece "Ingreso guardado" y la fila nueva; el panel suma el monto. Al recargar con F5 no se duplica.
- Haz clic en una fila para resaltarla. Pulsa **Eliminar**: con Cancelar no pasa nada; con Aceptar se borra.
- Validación del servidor: con F12 quita el `min` del campo Monto, escribe 0 y guarda. Sale "El monto debe ser mayor que 0" y no se inserta nada.
- **Gastos** e **Historial** abren dentro del menú con el aviso de la etapa 5. "Cerrar sesión" te regresa al login.
- En una ventana privada abre `http://localhost/RutaNeta/dashboard.php`: te manda al login.
- Prueba CSRF: en el login, con F12 borra el `<input name="csrf_token">` y pulsa Entrar. Sale "El formulario expiró o no vino de RutaNeta" con respuesta 403.

## Usuario de prueba

| Email | Contraseña |
|-------|------------|
| `demo@rutaneta.test` | `demo1234` |

Es la única contraseña del repositorio y existe solo para pruebas.
