# Ayuda de instalacion y configuracion

Esta guia esta pensada para administradores o equipos tecnicos que necesitan instalar, configurar y probar el plugin Line Modo en una tienda Adobe Commerce / Magento 2.

El plugin esta compuesto por dos modulos:

- `Line_Modo`: metodo de pago Line Modo.
- `Line_Promotions`: consulta de promociones activas para enviar codigos `ccCode` en el pago.

## Requisitos previos

Antes de comenzar, verificar que la tienda cuente con:

- Adobe Commerce / Magento 2 instalado.
- PHP compatible con el modulo: `~8.3`, `~8.4` o `~8.5`.
- Extension PHP `intl` habilitada.
- Acceso al panel administrador de Adobe Commerce.
- Acceso por consola al servidor donde esta instalada la tienda.
- Credenciales de Line para el ambiente que se va a utilizar:
  - public key;
  - secret key;
  - endpoints de API y checkout/frame;
  - datos de promociones, si aplica.

## Instalacion

### Instalacion via Composer

Si los paquetes estan disponibles en el repositorio Composer configurado para la tienda:

```bash
composer require line/module-promotions
composer require line/module-modo
```

Luego habilitar los modulos:

```bash
bin/magento module:enable Line_Promotions Line_Modo
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

### Instalacion manual

Si se instala manualmente desde el codigo fuente, ubicar los modulos dentro de la estructura de Magento:

```text
app/code/Line/Promotions
app/code/Line/Modo
```

Luego ejecutar:

```bash
bin/magento module:enable Line_Promotions Line_Modo
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuracion del metodo de pago

Ingresar al administrador de Adobe Commerce y navegar a:

```text
Tiendas > Configuracion > Ventas > Metodos de pago > Line Modo
```

Configurar los campos principales:

- `Enabled`: habilita o deshabilita el metodo de pago.
- `Environment`: selecciona `sandbox` o `production`.
- `Sandbox Endpoint`: endpoint de API para pruebas.
- `Sandbox Frame Endpoint`: endpoint del checkout/frame para pruebas.
- `Production Endpoint`: endpoint de API para produccion.
- `Production Frame Endpoint`: endpoint del checkout/frame para produccion.
- `Public Key`: clave publica del ambiente seleccionado.
- `Secret Key`: clave secreta del ambiente seleccionado.
- `Title`: nombre que vera el cliente en el checkout.
- `CC Code`: codigo manual de promocion, usado si no se resuelven promociones activas.
- `New Order Status`: estado inicial de la orden.
- `Payment has been accepted`: estado a aplicar cuando el pago se aprueba.
- `Payment has been rejected`: estado a aplicar cuando el pago se rechaza.
- `Generate Invoice on Payment`: permite generar factura cuando se recibe un pago exitoso.
- `Payment from Applicable Countries`: paises habilitados para usar el metodo.
- `Sort Order`: orden de visualizacion del metodo en checkout.
- `Debug`: habilita informacion adicional de diagnostico en logs.

Valores por defecto relevantes:

- ambiente inicial: `sandbox`;
- pais habilitado por defecto: Argentina;
- titulo por defecto: `Line - Modo`;
- estado inicial por defecto: `pending_payment`;
- estado de pago aprobado por defecto: `processing`;
- generacion automatica de factura: deshabilitada.

## Configuracion de notificaciones

Dentro de la configuracion de `Line Modo`, revisar el grupo `Notifications`.

Opciones disponibles:

- `No`: el modulo no envia notificaciones propias.
- `On every transaction`: envia una notificacion cada vez que se procesa una transaccion.
- `When total is paid`: envia una notificacion cuando la orden queda completamente pagada.

Si se habilitan notificaciones, seleccionar tambien el template de email correspondiente.

## Configuracion visual del checkout

Dentro de `Line Modo`, revisar el grupo `Checkout Widget Theme`.

Se pueden configurar:

- color primario;
- color de fondo;
- color de texto;
- color de borde;
- familia tipografica;
- tamano de fuente;
- radio de borde;
- radio de borde de botones;
- color de texto de botones;
- color de hover de botones.

Si no se completan estos campos, el modulo utiliza los valores por defecto.

## Configuracion de promociones

Ingresar al administrador de Adobe Commerce y navegar a:

```text
Tiendas > Configuracion > Ventas > Metodos de pago > Line Promotions
```

Configurar:

- `Enable`: habilita o deshabilita la consulta de promociones.
- `API Key`: clave de autenticacion para la API de promociones.
- `Endpoint`: URL base de la API de promociones.
- `Merchant`: identificador de marketplace o comercio.
- `Merchant ID`: identificador numerico o interno del comercio.

Cuando el modulo esta habilitado, consulta promociones activas y extrae los codigos `ccCode`. Si encuentra codigos, los envia en el request de pago. Si no encuentra codigos o la API no responde correctamente, el pago puede usar el `CC Code` configurado manualmente en `Line Modo`.

## Configuracion del webhook

El plugin expone un endpoint para recibir webhooks de Line Modo:

```text
https://TU-DOMINIO/line/modo/webhook
```

Este endpoint debe configurarse en el panel o integracion de Line correspondiente al comercio.

El webhook valida la firma recibida en el header `X-Line-Signature`, por lo que es importante que el `Secret Key` configurado en Adobe Commerce coincida con el ambiente utilizado por Line.

## Primeros pasos de prueba

Para validar una instalacion nueva, se recomienda:

1. Habilitar `Line Modo` en ambiente `sandbox`.
2. Cargar las credenciales sandbox provistas por Line.
3. Verificar que los endpoints sandbox sean correctos.
4. Confirmar que el metodo este habilitado para Argentina o para el pais que corresponda.
5. Limpiar cache de Magento.
6. Crear una compra de prueba desde el checkout.
7. Seleccionar `Line - Modo` como metodo de pago.
8. Confirmar que la orden se crea correctamente.
9. Confirmar que se carga el checkout embebido de Line Modo.
10. Completar o simular el pago en sandbox.
11. Verificar que la orden cambie al estado configurado.
12. Verificar que el webhook quede registrado y no arroje errores.
13. Si esta habilitada la facturacion automatica, confirmar que se genere la factura.

## Flujo esperado para el comprador

1. El comprador agrega productos al carrito.
2. Ingresa al checkout.
3. Selecciona `Line - Modo` como metodo de pago.
4. Confirma la orden.
5. Visualiza el checkout embebido de Line Modo.
6. Completa el pago.
7. La tienda actualiza la orden segun el resultado del pago.

Si el pago es rechazado, cancelado o expira, el comprador puede ver un mensaje de estado y, cuando corresponde, utilizar la opcion de reintento.

## Logs utiles

Para diagnostico tecnico, revisar:

```text
var/log/line_modo.log
var/log/line_promotions.log
```

El primer archivo contiene informacion del metodo de pago, solicitudes a Line, webhooks y errores del flujo de pago.

El segundo archivo contiene informacion de la consulta de promociones y resolucion de codigos `ccCode`.

## Problemas frecuentes

### El metodo de pago no aparece en checkout

Revisar:

- que `Line Modo` este habilitado;
- que el pais de la direccion del comprador este permitido;
- que la cache de Magento haya sido limpiada;
- que los modulos esten habilitados con `bin/magento module:status`;
- que el checkout no este filtrando metodos de pago por reglas externas.

### El checkout embebido no carga

Revisar:

- que el `Frame Endpoint` corresponda al ambiente seleccionado;
- que la `Public Key` sea correcta;
- que el navegador no bloquee el script externo;
- que la configuracion CSP permita los dominios de Line;
- que no haya errores JavaScript en consola.

### La orden no cambia de estado

Revisar:

- que el webhook este configurado en Line;
- que la URL publica `/line/modo/webhook` sea accesible desde internet;
- que el `Secret Key` coincida con el ambiente utilizado;
- que el estado de pago aprobado o rechazado este correctamente configurado;
- que no existan errores en `var/log/line_modo.log`.

### No se aplican promociones

Revisar:

- que `Line Promotions` este habilitado;
- que el `API Key` de promociones sea correcto;
- que `Endpoint`, `Merchant` y `Merchant ID` sean validos;
- que existan promociones activas para el comercio;
- que `var/log/line_promotions.log` no registre errores.

## Recomendaciones para produccion

Antes de pasar a produccion:

- probar el flujo completo en sandbox;
- validar credenciales productivas;
- cambiar el ambiente a `production`;
- confirmar endpoints productivos;
- configurar el webhook productivo;
- revisar estados de orden;
- definir si se generaran facturas automaticamente;
- revisar notificaciones por email;
- deshabilitar `Debug` salvo que se necesite diagnostico temporal;
- realizar una compra real controlada de bajo monto, si el proceso operativo lo permite.

