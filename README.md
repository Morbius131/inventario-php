# Inventario PHP

Aplicación web de control de inventario hecha con **PHP 8 puro** (sin frameworks): arquitectura MVC propia, router con parámetros, PDO + SQLite, autenticación con sesiones y un CRUD completo de productos con búsqueda, filtros, paginación y exportación a CSV.

> Proyecto de portafolio: está pensado para leerse y ejecutarse sin dependencias ni configuración.

## Funcionalidades

- Inicio y cierre de sesión con `password_hash` / `password_verify`, regeneración del ID de sesión y bloqueo temporal tras 5 intentos fallidos.
- CRUD de productos (SKU único, categoría, precio, stock y stock mínimo).
- Búsqueda por nombre o SKU, filtro por categoría y paginación (10 por página).
- Panel con métricas: total de productos, unidades, valor del inventario y productos con stock bajo.
- Alertas visuales de stock bajo.
- Creación de categorías al vuelo desde el formulario del producto.
- Exportación a CSV respetando los filtros activos (con BOM para Excel y protección contra inyección de fórmulas).
- Base de datos SQLite que se crea sola con datos demo la primera vez que se ejecuta.

## Seguridad

- Consultas preparadas en todo el acceso a datos (PDO), incluida la búsqueda con `LIKE` escapado.
- Escape de salida en todas las vistas (`e()`).
- Protección CSRF en cada formulario que modifica datos (`hash_equals`).
- Cookies de sesión `HttpOnly` + `SameSite=Lax`.
- Cabeceras `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options` y `Referrer-Policy`; sin JavaScript inline.
- Precios guardados como enteros (centavos) para evitar errores de redondeo con decimales.

## Requisitos

- PHP 8.2 o superior con las extensiones `pdo_sqlite` y `mbstring`.

## Cómo ejecutarlo

```bash
cd inventario-php
php -S localhost:8000 -t public
```

Abre <http://localhost:8000> e ingresa con la cuenta demo:

| Usuario | Contraseña |
|---|---|
| `admin@demo.com` | `admin123` |

Para reiniciar los datos de ejemplo, detén el servidor y borra `database/inventario.sqlite`.

También funciona con Apache: apunta el `DocumentRoot` a `public/` (incluye `.htaccess` con reescritura).

## Estructura

```
inventario-php/
├── public/              # Único directorio expuesto: front controller y assets
│   ├── index.php        # Rutas + despacho
│   └── assets/          # CSS y JS
├── app/
│   ├── bootstrap.php    # Autoload PSR-4, sesión y manejo de errores
│   ├── helpers.php      # Funciones de vista (e, money, flash, url…)
│   ├── Core/            # Router, View, Database, Auth, Csrf
│   ├── Models/          # Product, User (SQL con PDO)
│   ├── Controllers/     # AuthController, ProductController
│   └── views/           # Plantillas PHP
└── database/            # SQLite generado en el primer arranque
```

## Rutas

| Método | Ruta | Descripción |
|---|---|---|
| GET/POST | `/login` | Formulario e inicio de sesión |
| POST | `/logout` | Cierra la sesión |
| GET | `/products` | Listado con `?q=`, `?category=` y `?page=` |
| GET | `/products/export` | Descarga CSV (respeta filtros) |
| GET/POST | `/products/create`, `/products` | Crear producto |
| GET/POST | `/products/{id}/edit`, `/products/{id}` | Editar producto |
| POST | `/products/{id}/delete` | Eliminar producto |

## Ideas para ampliarlo

- Movimientos de inventario (entradas/salidas) con historial por producto.
- Roles de usuario (administrador / consulta).
- Pruebas automatizadas con PHPUnit.
