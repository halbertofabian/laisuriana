# Especificación funcional de metas y comisiones

Fecha: 21 de septiembre de 2026.

Estado: reglas principales confirmadas por el usuario; detalles complementarios pendientes de validación. La corrección de redondeo aplica la regla confirmada de comisionar al alcanzar el 100%.

Fuente: `DOCUMENTO PARA SOFTMOR PARA COMISIONES.pdf`, cinco páginas, proporcionado por el usuario. Se contrastó con el módulo Comisiones V2 del proyecto. Este documento distingue requisitos del PDF, comportamiento existente y propuestas.

## Decisiones confirmadas por el usuario

El 21 de septiembre de 2026, el usuario confirmó expresamente:

1. El vendedor tiene derecho a comisión al alcanzar o superar el 100% de la meta.
2. La referencia histórica se divide entre los vendedores del mes que se está configurando.
3. Metas y comisiones consideran ventas netas de descuentos y devoluciones, excluyendo cancelaciones.

Estas decisiones prevalecen sobre los ejemplos contradictorios del PDF: los importes mostrados debajo del 100% no autorizan pagos por debajo de la meta.

## Objetivo y roles

Administración configura las metas mensuales por departamento y vendedor, revisa el cálculo y determina las tasas por desempeño. El vendedor consulta su avance porcentual sin recibir los importes de ventas, metas o comisiones en la respuesta de su consulta de avance.

El PDF describe Ropa y Telas y la suma de La I. Suriana e Isuriana. Las líneas deben poder cambiar de departamento por periodo sin duplicarse entre departamentos. El módulo actual permite varios almacenes, todos pertenecientes a una misma sucursal; falta confirmar que esta estructura corresponde a los dos almacenes del documento.

## Reglas y situación actual

| ID | Regla | Origen y situación |
| --- | --- | --- |
| R01 | Configuración mensual con departamentos, líneas, almacenes y participantes. | PDF; disponible en V2. |
| R02 | Referencia del mismo mes del año anterior, excluyendo autoservicio. | PDF; disponible. Actualmente autoservicio significa detalle de venta sin vendedor asignado. Confirmar cómo se registra en los datos históricos. |
| R03 | Dividir la referencia entre vendedores del departamento. | Confirmado: utilizar el equipo del periodo que se configura, como ya hace V2. |
| R04 | Aplicar inflación e incremento administrativo. | PDF; V2 dispone de un único incremento porcentual por departamento. Separar componentes y elegir suma o composición requiere definición. |
| R05 | Sumar ventas por vendedor entre almacenes. | PDF; V2 cuenta las líneas de su departamento asignado. Falta definir ventas cruzadas entre departamentos. |
| R06 | Comisión calculada sobre el 33% vendido, con tasa ordinaria de 0.9%. | PDF; disponible. 33% significa 0.33, no un tercio exacto. |
| R07 | Ajustar la tasa por desempeño y permitir comisión cero. | PDF; V2 exige motivo de ajustes y admite tasas de 0 a 1% en pasos de 0.1. El PDF menciona 0.5 a 1%, además de cero: validar si se permiten 0.1 a 0.4%. |
| R08 | Derecho a comisión condicionado al cumplimiento de meta. | Confirmado: ventas mayores o iguales a una meta positiva. La elegibilidad se decide antes del redondeo visual. |
| R09 | Avance personal limitado visualmente a 0-100%, sin importes. | PDF; disponible en servicio, escritorio y vendedor-app. Se conserva el porcentaje completo para administración. |
| R10 | Borrador, aprobación, corrección con motivo y cierre. | Flujo existente del sistema, no exigido expresamente en el PDF. El cierre conserva resultados y bloquea cambios de configuración. |
| R11 | Ventas netas de descuentos y devoluciones; excluir cancelaciones. | Confirmado por el usuario; coincide con el comportamiento existente. |

## Fórmulas

Variables: H = ventas históricas del departamento en almacenes seleccionados; A = autoservicio histórico; N = número de vendedores; g = incremento porcentual total; V = ventas del vendedor; M = meta individual; t = tasa individual expresada como porcentaje.

```text
Base histórica = H - A
Promedio = Base histórica / N
Meta sugerida actual = redondear(Promedio × (1 + g / 100), 2)
Cumplimiento real = V / M × 100
Base comisionable actual = redondear(máximo(0, V) × 0.33, 2)
Elegible actualmente = M > 0 y V >= M
Comisión actual = Elegible ? redondear(Base comisionable × t / 100, 2) : 0
```

No dividir entre cero. Sin referencia válida, V2 permite meta manual y exige metas positivas para aprobar. Esta alternativa manual existe en el proyecto; el PDF no define cómo operar sin histórico.

### Captura manual de ventas históricas

Disponible desde **Metas y comisiones > Capturar ventas históricas**. Permite guardar el total mensual por sucursal, almacén y línea de producto, con ventas netas totales, autoservicio neto incluido en ese total, referencia del documento y observaciones. Solo admite meses anteriores al actual e importes no negativos con hasta dos decimales. El autoservicio no puede superar el total.

Cada captura sustituye la referencia del POS para su combinación de mes, almacén y línea; no se suma a ella. Las combinaciones sin captura mantienen la referencia del POS. Una captura de cero también sustituye al POS. No genera ventas operativas, movimientos de caja o inventario, ni alimenta la comisión del mes actual.

El total capturado se usa al guardar la configuración de metas del mismo mes del año siguiente. Se calcula una meta sugerida; las metas comunes o individuales capturadas explícitamente conservan prioridad. Las metas publicadas no cambian automáticamente y los resultados cerrados no se recalculan.

El permiso requerido es `comisiones.configurar`. No se permiten duplicados; las correcciones requieren motivo y conservan el antes y después en bitácora. Un control de versión evita sobrescribir correcciones de otra persona. Los catálogos permiten elegir líneas y almacenes inactivos no eliminados, porque se trata de referencias de meses anteriores.

Validación de esta integración: 16 pruebas y 128 aserciones de Comisiones V2 e histórico. Se verificaron visualmente escritorio y móvil, el resumen en vivo y la validación del importe de autoservicio. La migración crea únicamente la tabla de referencias históricas.

Si inflación e incremento se capturan por separado, hay dos alternativas pendientes:

```text
Aditiva: Promedio × (1 + inflación / 100 + incremento / 100)
Compuesta: Promedio × (1 + inflación / 100) × (1 + incremento / 100)
```

No elegir una alternativa ni introducir porcentajes predeterminados sin validación.

## Corrección de redondeo realizada

La elegibilidad se decide comparando V y M directamente, antes del redondeo del porcentaje. Para una meta no alcanzada, el cumplimiento mostrado tiene como máximo 99.99%, aunque el redondeo ordinario arroje 100.00%. Escritorio y vendedor-app muestran dos decimales para no volver a redondear 99.99% a 100.0%.

La corrección se aplica a nuevas estimaciones y cierres. No recalcula resultados de periodos ya cerrados. No cambia el 33%, la tasa individual ni el tratamiento actual de ventas netas.

## Contradicciones y cuestionario de negocio

1. **Elegibilidad resuelta:** se paga al alcanzar o superar 100%. En las páginas 3 y 4, Rosi tiene 84.63% y $851; Jazmín, 75.13% y $756. Esos ejemplos no cumplen la regla confirmada y no serán referencias para pagar comisión.
2. **Divisor resuelto; prorrateo pendiente:** se utiliza el equipo del mes que se configura. Falta definir si cada participante se cuenta completo o proporcionalmente a los días trabajados; actualmente se cuenta completo.
3. **Base neta resuelta; impuestos y fecha pendientes:** descontar descuentos y devoluciones y excluir cancelaciones. Falta confirmar tratamiento de impuestos y si se reconoce por fecha de cobro, emisión o entrega. V2 usa la fecha de cobro para las ventas.
4. **Incrementos:** ¿inflación e incremento se suman o componen? ¿Se capturan por departamento y mes, o se heredan de una configuración anual?
5. **Desempeño:** ¿qué tasas se permiten y quién las autoriza? Pasar de 0.9 a 1% representa 0.1 puntos porcentuales, no un punto porcentual como dice el ejemplo.
6. **Telas:** ¿tiene una tasa ordinaria diferente de Ropa o solo una meta diferente? El documento no establece otra tasa base.
7. **Devoluciones:** ¿afectan el mes de devolución o el de la venta original? ¿Cómo ajustar una comisión ya cerrada o pagada?
8. **Movimientos del equipo:** ¿qué ocurre con altas, bajas, ausencias, cambios de departamento y ventas de líneas de otro departamento durante el mes?
9. **Visibilidad:** ¿cada vendedor ve solo su avance, como actualmente, o también porcentajes de compañeros? ¿En qué aplicaciones debe estar disponible?
10. **Redondeo:** ¿se redondea a centavos al final o también en pasos intermedios? El ejemplo de $306,214.03 da $1,010.51 al 1% y $909.46 al 0.9% con redondeo convencional, frente a $1,010.50 y $909.45 del PDF.
11. **Almacenes e histórico:** confirmar identidad de almacenes y disponibilidad del año anterior. Las tablas están rotuladas con 2025 y el texto ejemplifica agosto de 2026: validar a qué año pertenecen ventas y metas.

## Criterios de aceptación

Los casos de elegibilidad siguientes corresponden a la regla confirmada por el usuario de alcanzar o superar el 100%.

| Caso | Entrada | Resultado esperado |
| --- | --- | --- |
| Un centavo por debajo | V=$999.99, M=$1,000.00, t=0.9% | Comisión $0.00; avance 99.99%; estado en progreso. |
| Meta exacta | V=$1,000.00, M=$1,000.00, t=0.9% | Comisión $2.97; meta alcanzada. |
| Un centavo por encima | V=$1,000.01, M=$1,000.00, t=0.9% | Comisión $2.97; meta alcanzada. |
| Meta alta casi alcanzada | V=$338,593.44, M=$338,593.45 | Comisión $0.00; avance 99.99%. |
| Tasa cero | V=M=$1,000.00, t=0% | Meta alcanzada; comisión $0.00. |
| Meta inválida | M <= 0 | No generar comisión; impedir aprobación de una configuración con esa meta. |
| Ventas no positivas | V <= 0, M > 0 | Comisión $0.00; avance 0%. |
| Ejemplo del PDF | V=$433,007.73, M=$338,593.45, t=0.9% | Avance administrativo 127.88%; comisión $1,286.03; avance personal limitado a 100%. |
| Cierre bajo la meta | V=$999.99, M=$1,000.00 | Guardar comisión cero y 99.99%; no anunciar meta alcanzada en el avance cerrado. |
| Privacidad | Consulta de avance personal autorizada | Solo porcentaje, estado y mensaje; no incluir ventas, meta monetaria, tasa o comisión. |
| Referencia sin autoservicio | H=$1,200, A=$200, N=2, g=10% | Meta sugerida $550.00, según fórmula actual. |

## Validación técnica y siguientes pasos

- La prueba de un centavo faltante falló con el código anterior, reproduciendo una comisión incorrecta de $2.97.
- Tras la corrección, pasaron las pruebas unitarias y de integración de Comisiones V2: 10 pruebas, 74 aserciones, con SQLite en memoria.
- La revisión de tipos TypeScript de vendedor-app pasó. El cambio de su pantalla requiere generar y distribuir una nueva versión para llegar a instalaciones existentes.
- Elegibilidad, divisor y base de ventas fueron confirmados por el usuario. Quedan por resolver incrementos, prorrateo, impuestos, excepciones y corte de devoluciones.
- Extender el módulo V2 con las reglas aprobadas y validar un mes completo contra un ejemplo autorizado por administración. Los ejemplos contradictorios del PDF no deben usarse como resultados esperados sin aclaración.
