# Funcionalidades del plugin Line Modo para Adobe Commerce

Este documento resume las funcionalidades incluidas en el plugin de pagos Line Modo para Adobe Commerce / Magento 2. El objetivo es describir el alcance funcional entregado, sus componentes principales y las capacidades disponibles para la operacion de la plataforma.

## Resumen general

El plugin permite incorporar Line Modo como metodo de pago dentro del checkout de Adobe Commerce. La integracion crea una solicitud de pago contra la API de Line, muestra la experiencia de pago embebida, consulta el estado de la operacion y actualiza la orden en Adobe Commerce segun el resultado informado por Line Modo.

El paquete esta compuesto por dos modulos:

- `Line_Modo`: modulo principal del metodo de pago.
- `Line_Promotions`: modulo complementario para obtener promociones activas y aplicar codigos `ccCode` al request de pago.

## Funcionalidades principales

### Metodo de pago Line Modo

- Registro de un nuevo metodo de pago en Adobe Commerce bajo el codigo `line_modo`.
- Visualizacion del metodo de pago dentro del checkout.
- Redireccion posterior a la colocacion de la orden para iniciar el flujo de pago.
- Integracion con la API de Line para generar una solicitud de pago.
- Almacenamiento de la informacion de pago recibida desde Line en la orden de Adobe Commerce.
- Soporte para flujo de pago embebido mediante el loader de checkout de Line.
- Configuracion del titulo visible del metodo de pago.
- Restriccion por paises permitidos, con Argentina configurado por defecto.

### Integracion con la API de Line

- Envio de solicitudes de pago al endpoint `/payments/request`.
- Consulta del estado de una solicitud de pago mediante `/payments/{payment_id}/status`.
- Autenticacion contra la API mediante clave secreta configurada en el administrador.
- Envio de informacion de la orden, incluyendo:
  - monto;
  - moneda;
  - identificador de orden;
  - referencia externa;
  - codigos de promocion `cc_code`.
- Manejo de errores de comunicacion con la API y registro en logs.

### Checkout embebido

- Carga del script externo de checkout de Line Modo.
- Renderizado del contenedor de pago en la pagina de exito de compra.
- Envio al loader de checkout de:
  - public key;
  - payment id;
  - URL de callback para mobile;
  - configuracion visual del componente.
- Escucha de eventos enviados por el iframe o componente de Line Modo.
- Consulta de estado desde frontend para detectar pagos aprobados, pendientes, rechazados, cancelados o expirados.
- Visualizacion de mensajes al usuario segun el estado del pago.
- Opcion de reintentar el pago cuando corresponde.

### Webhooks de pago

- Recepcion de eventos de webhook desde Line Modo.
- Validacion de firma HMAC-SHA256 mediante el header `X-Line-Signature`.
- Validacion de tolerancia temporal del timestamp del webhook.
- Resolucion de la orden de Adobe Commerce a partir de la referencia de pago.
- Prevencion de reprocesamiento mediante control de idempotencia por `eventId`.
- Procesamiento de pagos aprobados.
- Procesamiento de pagos rechazados.
- Registro de comentarios en el historial de la orden con informacion del pago.
- Registro de errores y eventos no manejados en logs.

### Estados de orden

- Configuracion del estado inicial de la orden.
- Configuracion del estado a aplicar cuando el pago es aprobado.
- Configuracion del estado a aplicar cuando el pago es rechazado.
- Actualizacion de estado y state de la orden segun la respuesta del pago.
- Registro de comentarios informativos en el historial de la orden.
- Deteccion de pagos parciales, pagos completos y sobrepagos en el flujo de procesamiento interno.

### Facturacion

- Opcion configurable para generar factura automaticamente cuando se recibe un pago exitoso.
- Asociacion del transaction id informado por Line Modo a la factura.
- Marcado de la factura como pagada.
- Envio del email de factura cuando se genera la factura desde el modulo.

### Reintento de pago

- Generacion de una URL segura para reintentar el pago de una orden.
- Validacion mediante clave derivada de la orden.
- Boton de reintento disponible en vistas relacionadas con la orden.
- Reinicio del flujo de pago para la orden seleccionada.

### Notificaciones por email

- Configuracion del comportamiento de notificaciones.
- Posibilidad de desactivar notificaciones propias del modulo.
- Posibilidad de notificar en cada transaccion.
- Posibilidad de notificar solo cuando el total de la orden queda pagado.
- Template de email incluido para informar actualizaciones relacionadas con el pago.

### Configuracion administrativa

El modulo agrega configuraciones dentro del panel de Adobe Commerce para administrar el comportamiento del metodo de pago.

Configuraciones incluidas:

- habilitar o deshabilitar el metodo de pago;
- seleccion de ambiente `sandbox` o `production`;
- endpoint de API para sandbox;
- endpoint de checkout/frame para sandbox;
- endpoint de API para produccion;
- endpoint de checkout/frame para produccion;
- public key y secret key para sandbox;
- public key y secret key para produccion;
- titulo del metodo de pago;
- codigo `cc_code` manual;
- estado inicial de la orden;
- estado para pago aprobado;
- estado para pago rechazado;
- generacion automatica de factura;
- paises habilitados;
- orden de visualizacion;
- modo debug;
- configuracion de notificaciones por email;
- configuracion visual del checkout embebido.

### Personalizacion visual

El plugin permite configurar atributos visuales para adaptar el checkout embebido a la identidad de la tienda.

Opciones disponibles:

- color primario;
- color de fondo;
- color de texto;
- color de borde;
- familia tipografica;
- tamano de fuente;
- radio de borde general;
- radio de borde de botones;
- color de texto de botones;
- color de hover de botones.

### Promociones activas

El modulo `Line_Promotions` permite consultar promociones activas desde la API de Line y utilizarlas en la generacion del pago.

Funcionalidades incluidas:

- configuracion independiente para habilitar o deshabilitar promociones;
- configuracion de API key;
- configuracion de endpoint;
- configuracion de merchant;
- configuracion de merchant id;
- consulta de promociones activas;
- extraccion de codigos `ccCode` desde la respuesta de la API;
- separacion de codigos compuestos por guion;
- eliminacion de codigos duplicados;
- envio de los codigos resueltos al request de pago;
- fallback al `cc_code` configurado manualmente si no hay promociones activas disponibles.

### Logs y diagnostico

- Log especifico para el modulo de pagos: `var/log/line_modo.log`.
- Log especifico para el modulo de promociones: `var/log/line_promotions.log`.
- Registro de solicitudes de pago enviadas a Line.
- Registro de respuestas invalidas o errores de API.
- Registro de webhooks recibidos cuando el modo debug esta activo.
- Registro de errores de validacion de firma de webhook.
- Registro de promociones consultadas y codigos resueltos.

## Flujo funcional de pago

1. El cliente selecciona Line Modo como metodo de pago en el checkout.
2. Adobe Commerce crea la orden.
3. El plugin solicita a Line la generacion de una intencion de pago.
4. La respuesta de Line se guarda como informacion adicional del pago en la orden.
5. El cliente visualiza el checkout embebido de Line Modo.
6. Line Modo informa el avance del pago mediante eventos frontend y/o webhook.
7. El plugin consulta o recibe el estado final del pago.
8. La orden se actualiza como aprobada o rechazada segun corresponda.
9. Si esta configurado, el plugin genera la factura automaticamente.
10. Si corresponde, se envia una notificacion por email.

## Compatibilidad y requisitos

- Adobe Commerce / Magento 2.
- PHP compatible con las versiones declaradas por el modulo: `~8.3`, `~8.4` o `~8.5`.
- Extension PHP `intl`.
- Modulos base de Magento requeridos:
  - `magento/framework`;
  - `magento/module-payment`;
  - `magento/module-config`.
- Acceso a las APIs de Line correspondientes al ambiente configurado.
- Credenciales validas de Line para sandbox y/o produccion.

## Alcance de la entrega

La entrega incluye:

- modulo de metodo de pago Line Modo;
- modulo de promociones Line;
- configuracion administrativa;
- integracion API;
- flujo de checkout embebido;
- procesamiento de webhooks;
- procesamiento de estados de orden;
- soporte para facturacion automatica configurable;
- soporte para reintento de pago;
- templates y assets frontend;
- traducciones al espanol de Argentina;
- logs dedicados para operacion y diagnostico.

## Consideraciones operativas

Antes de utilizar el plugin en produccion se recomienda:

- configurar correctamente las credenciales productivas;
- validar los endpoints de API y checkout/frame;
- confirmar los estados de orden deseados para pago aprobado y rechazado;
- probar el flujo completo en sandbox;
- validar la recepcion de webhooks desde Line;
- revisar la configuracion de generacion de facturas;
- revisar el comportamiento de notificaciones por email;
- confirmar que las promociones activas devuelvan los `ccCode` esperados.

