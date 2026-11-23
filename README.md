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
| 3 | `etapa-3-auth` | Registro, login, logout y protección CSRF | Pendiente |
| 4 | `etapa-4-dashboard-ingresos` | Dashboard con KPIs y módulo de ingresos | Pendiente |
| 5 | `etapa-5-gastos-historial` | Módulo de gastos e historial mensual | Pendiente |
| 6 | `etapa-6-final` | Integración, pruebas de seguridad y entrega | Pendiente |

Cada rama nace de la anterior y funciona por sí sola. `main` solo recibe el merge de la entrega final.

Hasta esta etapa existen la base de datos, la conexión y la landing page. Las páginas que se construyen en etapas posteriores muestran el aviso "Esta sección llega en la etapa N", así ningún enlace termina en una página en blanco.

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

El formulario "Empieza gratis" manda nombre y email por GET a `registro.php`, que los precargará cuando exista el registro (etapa 3).

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
- "Empieza gratis" lleva a `registro.php?nombre=…&email=…`. Por ahora esa página muestra el aviso de la etapa 3, igual que las demás páginas del sistema.

## Usuario de prueba

| Email | Contraseña |
|-------|------------|
| `demo@rutaneta.test` | `demo1234` |

Es la única contraseña del repositorio y existe solo para pruebas. Se podrá usar a partir de la etapa 3, cuando exista el login.
