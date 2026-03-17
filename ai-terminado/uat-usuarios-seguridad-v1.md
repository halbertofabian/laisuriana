Resumen funcional (Versión 1)
En esta primera versión del módulo de Usuarios y Seguridad se incluye:

Acceso al sistema con usuario + contraseña.
Pantalla de login con búsqueda de usuario por nombre o usuario.
Al seleccionar usuario, el cursor pasa al campo contraseña.
Módulo Usuarios con 3 pestañas:
Listar usuarios
Roles
Permisos
Gestión de usuarios:
alta
edición
activación/inactivación
asignación de roles
asignación de sucursales
Gestión de roles:
alta
edición
activación/inactivación
asignación de permisos
Visualización de permisos por acción.
En alta/edición de roles, los permisos se muestran por módulo y con descripciones funcionales.
El botón Cerrar sesión está en el menú lateral izquierdo (parte inferior).
El correo del usuario se maneja como dato opcional.
Base de pruebas: https://lasuriana.softmor.com

Guía de pruebas (UAT) para usuario final

Ingreso al sistema
Entrar a: https://lasuriana.softmor.com/login
Escribir al menos 2 letras en Usuario.
Verificar que aparezcan sugerencias.
Seleccionar una sugerencia y validar que el cursor pase a Contraseña.
Ingresar credenciales válidas y confirmar acceso.
Probar credenciales inválidas y validar mensaje de error.
Validar estructura del módulo
Ir al menú lateral y entrar a Usuarios.
Confirmar que existan las pestañas:
Listar usuarios
Roles
Permisos
Prueba de alta de usuario
En pestaña Listar usuarios, dar clic en Nuevo usuario.
Capturar:
Nombre
Usuario
Contraseña
Estatus
Rol(es)
Sucursal(es)
Dejar correo vacío para validar que es opcional.
Guardar y confirmar que el usuario aparece en la tabla.
Prueba de edición de usuario
En la tabla, dar clic en Editar.
Cambiar datos (ejemplo: nombre o estatus).
Guardar y confirmar actualización.
Validar que en edición se pueda dejar contraseña vacía para conservar la actual.
Prueba de activación/inactivación de usuario
Dar clic en Inactivar.
Confirmar cambio de estatus en tabla.
Repetir para Activar.
Prueba de alta de rol
Ir a pestaña Roles.
Clic en Nuevo rol.
Capturar nombre, estatus y descripción.
Seleccionar permisos por módulo.
Guardar y confirmar que el rol aparece en la tabla.
Prueba de edición de rol
Clic en Editar sobre un rol.
Cambiar descripción o permisos.
Guardar y confirmar cambios.
Prueba de activación/inactivación de rol
Clic en Inactivar.
Confirmar cambio en estatus.
Repetir para Activar.
Prueba de consulta de permisos
Ir a pestaña Permisos.
Verificar listado, búsqueda y paginación.
Prueba de cierre de sesión
En menú lateral izquierdo, parte inferior, dar clic en Cerrar sesión.
Confirmar que regresa a login.
Si quieres, te lo convierto también en formato de checklist imprimible (Sí/No/Observaciones) para pruebas con firma de aceptación.


Checklist UAT - Usuarios y Seguridad (Versión 1)
Proyecto: La Suriana Retail
Ambiente: https://lasuriana.softmor.com
Fecha: ____________________
Probador: ____________________
Resultado general: ☐ Aprobado ☐ Aprobado con observaciones ☐ Rechazado

ID	Caso de prueba	Resultado (Sí/No)	Observaciones
1	Se puede abrir https://lasuriana.softmor.com/login		
2	En login, al escribir 2+ letras en Usuario, aparecen sugerencias		
3	Al seleccionar sugerencia, el foco pasa a Contraseña		
4	Con credenciales válidas se permite acceso al sistema		
5	Con credenciales inválidas se muestra mensaje de error		
6	En el menú lateral existe la opción Usuarios		
7	Dentro de Usuarios se ven las pestañas: Listar usuarios, Roles, Permisos		
8	En Listar usuarios, abre modal de Nuevo usuario		
9	En alta de usuario, Nombre/Usuario/Contraseña/Estatus/Roles/Sucursales son obligatorios		
10	En alta de usuario, Correo es opcional		
11	Se guarda usuario nuevo y aparece en la tabla		
12	Se puede editar usuario existente		
13	En edición, se puede dejar Contraseña vacía para conservar la actual		
14	Se puede inactivar usuario y cambia estatus en tabla		
15	Se puede activar usuario y cambia estatus en tabla		
16	En pestaña Roles, abre modal de Nuevo rol		
17	En alta de rol, Nombre/Estatus/Permisos son obligatorios		
18	Los permisos en rol se muestran organizados por módulo		
19	Los permisos muestran descripción funcional entendible		
20	Se guarda rol nuevo y aparece en tabla		
21	Se puede editar rol existente		
22	Se puede inactivar rol y cambia estatus en tabla		
23	Se puede activar rol y cambia estatus en tabla		
24	En pestaña Permisos, carga listado correctamente		
25	En Permisos funciona búsqueda y paginación		
26	El botón Cerrar sesión aparece en menú lateral (parte inferior)		
27	Al cerrar sesión, regresa a login		
Observaciones generales

Firmas

Usuario responsable: ____________________
Líder de proyecto: ____________________
Fecha de aceptación: ____________________