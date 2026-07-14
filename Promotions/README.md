# Line_Promotions

Modulo Magento 2 / Adobe Commerce para consultar promociones activas de Line y exponer codigos `ccCode` al modulo de pago Line Modo.

## Descripcion

`Line_Promotions` obtiene promociones activas desde la API de Line, extrae codigos `ccCode`, elimina duplicados y los entrega al modulo `Line_Modo` para incluirlos en el request de generacion de pago.

## Instalacion

```bash
composer require hypevar/modo-line-adobe-commerce:dev-main
bin/magento module:enable Line_Promotions
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuracion

Ingresar al administrador de Adobe Commerce:

```text
Tiendas > Configuracion > Ventas > Metodos de pago > Line Promotions
```

Campos principales:

| Campo | Descripcion |
| --- | --- |
| Enable | Habilita o deshabilita el modulo. |
| API Key | Clave de autenticacion para la API de Line. Se almacena encriptada. |
| Endpoint | URL base de la API de Line. |
| Merchant | Identificador del comercio o marketplace. |
| Merchant ID | Identificador del comercio utilizado en la consulta. |

## Endpoint consultado

La URL de consulta se construye con el siguiente formato:

```text
{endpoint}/payment/marketplace/{merchant}/{merchant_id}/status/active
```

## Ejemplo de extraccion de codigos

Input:

```php
[
    'brands' => [
        [
            'options' => [
                ['ccCode' => null],
                ['ccCode' => '3CSI-6CSI'],
                ['ccCode' => '3CSI'],
            ],
        ],
    ],
]
```

Output:

```php
['3CSI', '6CSI']
```

## Logs

```text
var/log/line_promotions.log
```

## Documentacion relacionada

- `../AYUDA.md`
- `../FUNCIONALIDADES.md`

## Licencia

OSL-3.0
