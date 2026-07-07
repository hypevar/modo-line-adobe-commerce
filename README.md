# Line Modo Adobe Commerce

Repositorio de modulos Magento 2 / Adobe Commerce para integrar Line Modo como metodo de pago y consultar promociones activas de Line.

## Modulos incluidos

- `Modo`: metodo de pago Line Modo para checkout.
- `Promotions`: consulta de promociones activas y resolucion de codigos `ccCode`.

## Descripcion general

El plugin permite que una tienda Adobe Commerce incorpore Line Modo como metodo de pago. El flujo contempla la creacion de una solicitud de pago, la carga del checkout embebido, la consulta o recepcion del estado del pago, la actualizacion de la orden y la posibilidad de generar factura segun configuracion.

El modulo de promociones agrega la consulta de promociones activas desde Line para enviar codigos `ccCode` en el request de pago.

## Instalacion rapida

Si los paquetes estan disponibles via Composer:

```bash
composer require line/module-promotions
composer require line/module-modo
bin/magento module:enable Line_Promotions Line_Modo
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Para instalacion manual, ubicar los modulos en:

```text
app/code/Line/Promotions
app/code/Line/Modo
```

Luego ejecutar los comandos de habilitacion y actualizacion de Magento.

## Documentacion

- [Ayuda de instalacion y configuracion](AYUDA.md)
- [Funcionalidades del plugin](FUNCIONALIDADES.md)
- [Modulo Line_Modo](Modo/README.md)
- [Modulo Line_Promotions](Promotions/README.md)

## Requisitos

- Adobe Commerce / Magento 2.
- PHP `~8.3`, `~8.4` o `~8.5`.
- Extension PHP `intl`.
- Credenciales de Line para el ambiente correspondiente.

## Logs

- `var/log/line_modo.log`
- `var/log/line_promotions.log`

## Licencia

OSL-3.0. Ver [LICENSE](LICENSE).
