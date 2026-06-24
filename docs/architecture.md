# Arquitectura funcional

## Entidades

```text
Cliente
  -> Instalaciones
      -> Equipos
      -> Avisos
      -> Revisiones
      -> Partes
      -> Presupuestos
      -> Facturas
  -> Contratos

Materiales
  -> Lineas de partes
  -> Lineas de presupuestos

Tipos de equipo
  -> Equipos

Canales de integracion
  -> Eventos de integracion futuros
```

## Equipo como nucleo

El equipo es el eje operativo. Cada equipo tiene codigo interno unico `EQ-000001`, tipo administrable, categoria libre, marca, modelo, fechas de revision y periodicidad.

Desde su historial se consultan:

- Avisos.
- Revisiones.
- Partes.
- Materiales utilizados.
- Fotos.
- Presupuestos.
- Facturacion vinculada.

## Tipos de equipo

Los tipos de equipo son administrables desde Filament. Permiten:

- Crear nuevos tipos.
- Editar tipos existentes.
- Desactivar tipos.
- Asignar icono.
- Configurar periodicidad por defecto.
- Configurar descripcion.

El tipo `Personalizado` debe existir siempre para cubrir nuevos servicios sin tocar codigo.

## Contratos

Un contrato puede pertenecer a:

- Un cliente completo.
- Una instalacion concreta.

Incluye:

- Tipo de contrato.
- Vigencia.
- Periodicidad de facturacion.
- Cuota mensual.
- Coberturas.
- Inclusion de urgencias.
- Inclusion de mantenimiento preventivo.
- Lineas por tipo de equipo o concepto.

Un cliente se considera abonado cuando tiene al menos un contrato activo dentro de fechas.

## Materiales

Catalogo basico:

- SKU.
- Nombre.
- Unidad.
- Precio coste.
- Precio venta.
- Stock.
- Stock minimo.
- Estado activo/inactivo.

Al cerrar un parte, los materiales usados descuentan stock.

## Reglas de negocio MVP

- Un aviso puede generar un parte.
- Una revision puede generar un parte.
- Un parte puede consumir materiales.
- Un parte puede quedar cerrado como solucionado, pendiente de material, requiere presupuesto o no localizado.
- Si un parte procede de revision, al cerrarlo se recalcula la proxima revision del equipo.
- Si un parte requiere presupuesto, el aviso queda en estado `pending_quote`.
- Si una revision se cierra con incidencia, se genera un aviso.
- Si una revision o aviso requiere presupuesto, se genera un presupuesto en borrador.
- Las asociaciones cliente, instalacion, equipo y contrato se validan en modelo antes de guardar.

## Automatizaciones futuras

La arquitectura deja preparadas estas tablas:

- `integration_sources`
- `integration_events`

Canales iniciales:

- WhatsApp.
- Telegram.
- Lucas.
- Jarvis.
- Formularios web.
- Correo electronico.
- API externa.
