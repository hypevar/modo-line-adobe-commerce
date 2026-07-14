# Line_Modo

Modulo Magento 2 / Adobe Commerce para incorporar Line Modo como metodo de pago en checkout.

## Descripcion

`Line_Modo` permite crear solicitudes de pago contra la API de Line, mostrar el checkout embebido de Line Modo, consultar el estado del pago y actualizar la orden segun el resultado recibido.

El modulo incluye:

- metodo de pago `line_modo`;
- configuracion de ambientes sandbox y production;
- credenciales por ambiente;
- checkout embebido;
- consulta de estado de pago;
- procesamiento de webhooks;
- estados configurables para pago aprobado y rechazado;
- generacion opcional de factura;
- notificaciones por email configurables;
- reintento de pago;
- personalizacion visual del widget de checkout.

## Instalacion

```bash
composer require hypevar/modo-line-adobe-commerce:dev-main
bin/magento module:enable Line_Modo
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuracion

Ingresar al administrador de Adobe Commerce:

```text
Tiendas > Configuracion > Ventas > Metodos de pago > Line Modo
```

Configurar:

- habilitacion del metodo;
- ambiente;
- endpoints de API y checkout/frame;
- public key y secret key;
- titulo visible en checkout;
- estados de orden;
- generacion de factura;
- paises habilitados;
- notificaciones;
- tema visual del checkout embebido.

## Webhook

Configurar en Line el endpoint publico:

```text
https://TU-DOMINIO/line/modo/webhook
```

El webhook valida la firma recibida mediante `X-Line-Signature`.

## Logs

```text
var/log/line_modo.log
```

## Documentacion relacionada

- `../AYUDA.md`
- `../FUNCIONALIDADES.md`

## Licencia

OSL-3.0
