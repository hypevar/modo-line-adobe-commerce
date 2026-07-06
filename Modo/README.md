# Line_Modo

## Description

Line allows you to process credit card payments securely and efficiently.

## Installation

Use [composer](https://getcomposer.org/) to install Line_Modo.

```
composer require line/module-modo
```

Then you'll need to activate the module.

```
bin/magento module:enable Line_Modo
bin/magento setup:upgrade
```

## Configuration

### Notification status

**No:** No notification will be sent by the module. The standard Adobe Commerce notification flow will handle all
communications.

**On every transaction:** An email with relevant transfer information will be sent every time a webhook is triggered —
even multiple times for the same order.

**When total is paid:** A notification will be sent only once, when the order reaches the status configured under
"Payment has been accepted". It informs that the full payment has been received.

## Uninstall

```
bin/magento module:uninstall Line_Modo
```

If you used Composer for installation Magento will remove the files and database information.

## License

[OSL-3.0](http://opensource.org/licenses/osl-3.0.php)
