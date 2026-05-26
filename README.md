# UniLink MVC en PHP

## Estructura

- `index.php`: front controller
- `config/`: configuracion y conexion a BD
- `modelo/`: modelos
- `controlador/`: controladores
- `vista/`: vistas y parciales
- `sql/unilink.sql`: script de base de datos

## Levantar en XAMPP

1. Copia la carpeta del proyecto dentro de `htdocs`.
2. Inicia Apache y MySQL en XAMPP.
3. Importa `sql/unilink.sql` desde phpMyAdmin.
4. Verifica `config/config.php` (usuario/clave/DB).
5. Abre en navegador:
   - `http://localhost/Prototipo/index.php`

## Login de prueba

- Correo: `demo@unimagdalena.edu.co`
- Contrasena: `12345678`

## Nota

El login y registro ya usan base de datos con `password_hash` / `password_verify`.
