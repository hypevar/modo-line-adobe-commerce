# Line_Promotions

## Descripción

Line Promotions obtiene los códigos de promoción activos (ccCodes) desde la API de Line y los expone para su uso en el request para generar el QR de pago.

## Instalación

```
composer require line/module-promotions
```

Luego activá el módulo.

```
bin/magento module:enable Line_Promotions
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuración

Navegá a **Tiendas > Configuración > Ventas > Métodos de pago > Line Promotions**.

| Campo           | Descripción |
|-----------------|---|
| **Habilitar**   | Habilita o deshabilita el módulo. |
| **API Key**     | Clave de autenticación para la API de Line. Se almacena encriptada. |
| **Endpoint**    | URL base de la API de Line. Ejemplo: `https://api.dashboard.line.net.ar` |
| **Merchant**    | Identificador del marketplace utilizado para construir la URL de la petición a la API. |
| **Merchant ID** | ID del marketplace utilizado para construir la URL de la petición a la API. |

La URL de la petición a la API se construye de la siguiente forma:

```
{endpoint}/payment/{merchant}/{merchant_id}/status/active
```
## Ejemplo de Payload y Respuesta

Input:
``` php [
     'brands' => [
         [
             'options' => [
                 ['ccCode' => null],
                 ['ccCode' => '3CSI-6CSI'],
                 ['ccCode' => '3CSI'],
             ]
         ]
     ]
 ]
 ```
 Output: ```['3CSI','6CSI']```

## Logs

```
var/log/line_promotions.log
```
