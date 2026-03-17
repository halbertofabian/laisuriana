# Project Context

## Regla Obligatoria de Lectura

Antes de crear, modificar o proponer cualquiera de los siguientes elementos:

- modulo
- funcionalidad
- endpoint
- vista
- migracion
- modelo
- servicio
- controlador
- proceso de sincronizacion
- consulta SQL
- cambio de arquitectura

todo agente de IA debe leer primero este archivo:

- `/ai-context/project-context.md`

Ningun agente debe generar codigo sin revisar primero este contexto.

---

## Descripcion General del Proyecto

Este proyecto consiste en construir un sistema retail offline multisucursal para un cliente que vende tela, ropa y productos relacionados.

El sistema debe permitir que cada sucursal opere de forma independiente aunque no tenga conexion a internet, manteniendo posteriormente sincronizacion con una capa central en la nube.

El negocio contempla operacion en:

- piso de venta
- caja
- inventario
- almacenes
- traspasos entre sucursales
- compras
- facturacion
- reportes
- administracion de usuarios, roles y permisos

---

## Objetivo del Sistema

Construir una aplicacion web profesional, escalable y mantenible que permita:

- operar offline por sucursal
- compartir catalogos maestros entre todas las sucursales
- controlar inventario por sucursal y por almacen
- levantar pedidos en piso y cobrarlos posteriormente en caja
- sincronizar informacion entre sucursales y capa central
- crecer por modulos sin romper la arquitectura

---

## Vision Operativa del Negocio

### Reglas funcionales clave

- Cada sucursal tiene su propio inventario.
- Cada sucursal puede tener uno o mas almacenes.
- El catalogo de productos es compartido entre sucursales.
- El catalogo de clientes es compartido entre sucursales.
- Los proveedores y reglas comerciales se consideran informacion maestra compartida.
- El inventario no se comparte directamente entre sucursales; cada sucursal controla su propia existencia.
- Debe existir flujo de venta en piso:
  - se levanta el pedido
  - se genera un folio o ticket
  - el cliente pasa a caja
  - en caja se recupera el pedido y se cobra
- La operacion critica no debe depender de internet en tiempo real.
- La sincronizacion debe ser eventual, tolerante a fallos y auditable.


# Integracion Manual - Modulo de Bitacora (para project-context.md)

## 1) Plan de trabajo propuesto para el modulo de bitacora

### Objetivo
Implementar un modulo de Bitacora transversal que registre eventos de seguridad y acciones sensibles de negocio para trazabilidad, auditoria operativa y soporte.

### Alcance funcional inicial
- Registro de accesos:
  - inicio de sesion exitoso
  - inicio de sesion fallido
  - cierre de sesion
- Registro de acciones sensibles:
  - altas, ediciones, activaciones e inactivaciones
  - cambios de roles/permisos
  - eventos criticos de modulos (ventas, inventario, caja, etc.)
- Consulta de bitacora:
  - filtros por fecha, modulo, accion, usuario y sucursal
  - tabla con DataTables
- Exclusiones:
  - no registrar datos sensibles en texto plano (contrasenas, tokens)

### Enfoque tecnico
- Mantener Controllers ligeros.
- Centralizar registros en Services de auditoria.
- Definir eventos minimos obligatorios por modulo.
- Asegurar trazabilidad por sucursal cuando aplique.
- Usar borrado logico y convenciones del proyecto.

### Entregables del modulo (siguiente fase)
- Diseño funcional del modulo bitacora.
- Estructura tecnica (tablas, indices, relaciones).
- Catalogo de eventos obligatorios por modulo.
- UI de consulta y filtros.
- Integracion transversal en servicios existentes.

---

## 2) Instrucciones para integrar manualmente en /ai-context/project-context.md

> Copiar y pegar los bloques de esta seccion en el archivo:  
> `/Users/chimino/Desktop/lasuriana/ai-context/project-context.md`

### A. Agregar en "Principios funcionales"
Insertar este bullet dentro de la lista existente:

- Bitacora transversal obligatoria en todos los modulos

### B. Agregar en "Dominios o modulos principales previstos"
Agregar este modulo en el listado:

14. Bitacora y auditoria

### C. Agregar una nueva seccion completa despues de "Seguridad y Permisos"

## Bitacora y Auditoria (Regla Transversal)

La bitacora es obligatoria para todos los modulos del sistema.

### Reglas base

- Toda accion sensible debe registrar evento en bitacora.
- Todo modulo nuevo debe definir explicitamente que eventos registra.
- No se deben registrar datos sensibles en texto plano (contrasenas, tokens, secretos).
- Toda bitacora debe incluir, cuando aplique:
  - usuario
  - sucursal
  - modulo
  - accion
  - entidad afectada
  - identificador de entidad
  - fecha/hora
  - ip
  - user-agent
- La bitacora debe permitir trazabilidad operativa y auditoria.
- Las consultas de bitacora deben ser filtrables y eficientes.

### Eventos minimos obligatorios

- autenticacion:
  - login_exitoso
  - login_fallido
  - logout
- seguridad:
  - usuario_creado
  - usuario_editado
  - usuario_activado
  - usuario_inactivado
  - rol_creado
  - rol_editado
  - rol_activado
  - rol_inactivado
  - permisos_asignados

### Regla de implementacion por modulo

Antes de implementar cualquier modulo nuevo, se debe documentar:

1. Eventos que generara el modulo
2. Donde se registraran (servicio/capa)
3. Campos de trazabilidad incluidos
4. Criterios de consulta y filtros operativos

Ningun modulo se considera completo si no contempla su integracion con bitacora.

### Convenciones recomendadas

- Tabla sugerida para acciones: `tbl_bitacora_acciones_bac`
- Tabla sugerida para accesos: `tbl_bitacora_accesos_bac`
- Campos con prefijo obligatorio de tabla
- Indices en:
  - fecha
  - usuario
  - sucursal
  - modulo
  - accion
  - entidad

### Regla para agentes de IA

Cuando el usuario solicite un modulo nuevo, el agente debe incluir siempre:

- impacto en bitacora
- eventos a registrar
- propuesta de trazabilidad minima

Si no lo incluye, la propuesta se considera incompleta.

### D. Agregar en "Buenas Practicas para Agentes de IA" > "Antes de proponer codigo"
Agregar estos bullets:

- Confirmar que el modulo define eventos de bitacora obligatorios.
- Confirmar que las acciones sensibles quedan auditables.
- Confirmar que no se exponen datos sensibles en registros de auditoria.

---

## 3) Texto corto para usar en futuras tareas (prompt base)

Usar este texto cuando se pida un modulo nuevo:

"Este modulo debe incluir integracion obligatoria con bitacora y auditoria. Define eventos de acceso y acciones sensibles, campos de trazabilidad minima (usuario, sucursal, modulo, accion, entidad, fecha, ip, user-agent) y evita registrar datos sensibles en texto plano."


### Principios funcionales

- Offline-first por sucursal
- Catalogo compartido, operacion distribuida
- Inventario por almacen
- Roles y permisos configurables
- Auditoria en operaciones sensibles
- Crecimiento modular

---

## Arquitectura Utilizada

### Enfoque general

Se utilizara una arquitectura modular, limpia y mantenible sobre Laravel.

La aplicacion debe separarse funcionalmente por dominios y modulos, evitando mezclar responsabilidades.

### Criterios arquitectonicos

- Arquitectura limpia y profesional
- Separacion correcta entre:
  - Controllers
  - Models
  - Requests
  - Services
- No colocar logica de negocio en vistas
- No sobrecargar controladores
- La logica de negocio debe vivir en servicios o capas de aplicacion claramente definidas
- La validacion debe centralizarse en Requests
- El sistema debe poder crecer sin acoplar fuertemente los modulos

### Modelo operativo de arquitectura

- Capa central para catalogos y consolidacion
- Operacion local por sucursal
- Sincronizacion posterior entre nodos
- Modulos desacoplados por dominio funcional

### Dominios o modulos principales previstos

1. Usuarios y seguridad
2. Sucursales y almacenes
3. Catalogo comercial
4. Clientes
5. Ventas
6. Caja
7. Inventario
8. Traspasos
9. Compras
10. Facturacion
11. Facturas o documentos de proveedor
12. Sincronizacion
13. Reportes

---

## Tecnologias del Stack

### Backend

- Laravel

### Base de datos

- MySQL

### Frontend

- Bootstrap 5
- AJAX para operaciones principales
- DataTables

### Zona horaria oficial del sistema

- `America/Mexico_City`

---

## Convenciones de Codigo

### Convencion de tablas

Todas las tablas deben seguir este formato:

- `tbl_{nombre_tabla}_{prefijo}`

Ejemplos:

- `tbl_usuarios_usr`
- `tbl_roles_rol`
- `tbl_permisos_prm`
- `tbl_sucursales_scl`
- `tbl_almacenes_alm`
- `tbl_ventas_vts`

### Convencion de campos

Todos los campos deben iniciar con el prefijo de la tabla.

No se permiten campos sin prefijo.

Ejemplos:

- `usr_id`
- `usr_nombre`
- `usr_email`
- `usr_password`
- `usr_created_at`
- `usr_updated_at`

### Reglas de base de datos

- Todas las tablas deben tener llave primaria.
- Todas las tablas deben incluir control de fechas.
- Cuando aplique, incluir auditoria de usuario creador y actualizador.
- Las tablas pivote tambien deben respetar prefijos y convenciones.
- Toda relacion debe declararse con claridad.

### Borrado logico

El proyecto no usa borrado fisico en logica funcional.

Reglas:

- No usar `DELETE` para operaciones funcionales del sistema.
- Toda eliminacion debe ser logica.

Campos recomendados:

- `{prefijo}_deleted`
- `{prefijo}_deleted_at`

Todas las consultas deben excluir registros eliminados, salvo que el caso requiera lo contrario de forma explicita.

### Indices y rendimiento

- Todas las tablas deben tener indices correctamente definidos.
- Crear indices para:
  - claves foraneas
  - joins frecuentes
  - filtros comunes
  - busquedas
  - estados consultados con frecuencia
  - folios
  - UUIDs si se usan
- Evitar consultas sin indice.
- Evitar consultas innecesarias.
- Pensar siempre en rendimiento y escalabilidad.

---

## Estructura de Carpetas

La estructura exacta se ira consolidando conforme se cree el proyecto, pero debe respetar una organizacion profesional y mantenible.

Referencia esperada de alto nivel:

- `app/Http/Controllers`
- `app/Http/Requests`
- `app/Models`
- `app/Services`
- `database/migrations`
- `resources/views`
- `public/`
- `routes/`
- `ai-context/project-context.md`

Regla:

- Si se crean nuevas carpetas o modulos, deben seguir una organizacion clara, consistente y orientada a responsabilidades.

---

## Reglas Importantes del Proyecto

### Regla 1: Leer contexto antes de codificar

Todo agente debe leer este archivo antes de escribir o proponer codigo.

### Regla 2: No cambiar stack sin autorizacion

No cambiar tecnologias ni arquitectura base sin consultar al responsable del proyecto.

### Regla 3: No asumir decisiones tecnicas fuera de este contexto

Consultar antes de tomar decisiones que afecten:

- arquitectura
- rendimiento
- escalabilidad
- seguridad
- estructura de base de datos
- sincronizacion

### Regla 4: Primero disenar, luego programar

Antes de desarrollar cualquier modulo:

1. Definir objetivo
2. Definir alcance
3. Definir entidades
4. Definir reglas de negocio
5. Definir acciones del usuario
6. Definir permisos relacionados
7. Definir flujo funcional
8. Despues pasar a implementacion tecnica

### Regla 5: No mezclar responsabilidades

- No poner logica de negocio en vistas.
- No sobrecargar controladores.
- No meter validaciones dispersas fuera de Requests.
- No mezclar seguridad, inventario, ventas y sincronizacion en una sola capa sin separacion clara.

### Regla 6: Mantener consistencia funcional

Toda nueva funcionalidad debe ser coherente con:

- operacion offline por sucursal
- catalogo compartido
- inventario por almacen
- flujo piso a caja
- crecimiento modular

---

## Lineamientos de UI/UX

### Reglas generales

- Interfaz limpia, profesional y clara.
- No recargar la pagina para operaciones principales.
- Usar AJAX para interacciones importantes.
- No usar formularios tradicionales en operaciones principales.

### Feedback obligatorio

Cada peticion debe mostrar al usuario que esta siendo procesada.

Usar al menos uno:

- modal
- loader
- overlay global

No se permiten peticiones silenciosas.

### Mensajes

- Todos los mensajes al usuario deben estar en espanol.
- En caso de error, mostrar el error exacto cuando sea posible.
- Mostrar validaciones por campo si aplica.
- No usar `alert()` nativo del navegador.

### Modal global Bootstrap 5

Usar para:

- confirmaciones
- mensajes de exito
- errores
- advertencias

### DataTables

- Preparado para grandes volumenes
- Carga optimizada
- Usar server-side cuando sea necesario
- Evitar cargas completas innecesarias

---

## Seguridad y Permisos

El sistema debe manejar usuarios, roles y permisos variables.

No deben existir roles fijos quemados como unica opcion del sistema.

### Reglas base

- Los permisos deben pensarse por accion de negocio.
- Los roles deben poder componerse con permisos.
- La asignacion de acceso debe contemplar sucursal cuando aplique.
- Toda accion sensible debe ser auditable.

Ejemplos de acciones:

- ver usuarios
- crear usuario
- editar usuario
- crear venta
- cobrar venta
- cancelar venta
- ajustar inventario
- autorizar traspaso

---

## Buenas Practicas para Agentes de IA

Todo agente que participe en este repositorio debe seguir estas practicas:

### Antes de trabajar

- Leer este archivo completo
- Entender el objetivo del modulo a tocar
- Verificar si la tarea afecta arquitectura, base de datos, seguridad o sincronizacion

### Durante el trabajo

- Mantener coherencia con el contexto del proyecto
- Respetar naming conventions
- Respetar stack definido
- No improvisar arquitectura fuera de las reglas
- Mantener separacion de responsabilidades
- Pensar en escalabilidad y mantenimiento

### Antes de proponer codigo

- Confirmar que la solucion respeta el modelo offline por sucursal
- Confirmar que no rompe el inventario por almacen
- Confirmar que respeta catalogos compartidos
- Confirmar que usa borrado logico cuando aplique
- Confirmar que el flujo de UI mantiene AJAX y feedback visual

### Al crear modulos

Siempre documentar o definir primero:

- objetivo del modulo
- alcance
- entidades
- reglas de negocio
- acciones del usuario
- permisos base
- flujo funcional

Solo despues pasar a diseño tecnico o codigo.

---

## Nota de Implementacion Inicial

Este archivo es la referencia base del proyecto y debe considerarse obligatorio para cualquier agente de IA o colaborador tecnico que intervenga en el desarrollo.

Si en el futuro el contexto cambia, este archivo debe actualizarse antes de continuar con nuevas tareas importantes.
