/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push({
            type: 'line_modo',
            component: 'Line_Modo/js/view/payment/method-renderer/line-modo-method'
        });
        return Component.extend({
            defaults: {
                template: ''
            }
        });
    }
);
