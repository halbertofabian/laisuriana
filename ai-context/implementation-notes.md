# Preparacion inicial Laravel + plantilla_base

## Resumen de implementacion

- Se levanto la base de Laravel 12 en la raiz del repositorio.
- Se integro la plantilla base en `public/vendor-template/assets`.
- Se creo un layout reusable en `resources/views/layouts/app.blade.php`.
- Se agrego una vista inicial de dashboard y una vista demo de DataTables con carga AJAX.
- Se configuraron rutas y controladores minimos para validar la base de UI.
- Se ajusto zona horaria a `America/Mexico_City`.
- Se dejo entorno base configurado para MySQL.

## Supuestos conservadores aplicados

- Se uso `html-starter/vertical-menu-template` para evitar arrastrar vistas demo extensas y mantener base limpia.
- Se mantuvo esta tarea sin desarrollo de modulos de negocio.
- Se agrego un ejemplo DataTables solo como base tecnica para siguientes modulos.
