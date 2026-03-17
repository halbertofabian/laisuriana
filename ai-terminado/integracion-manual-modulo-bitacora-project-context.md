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
