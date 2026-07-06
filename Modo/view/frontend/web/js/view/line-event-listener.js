/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */

define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    return function (config) {
        var successUrl = config.successUrl || urlBuilder.build('checkout/onepage/success');
        var failureUrl = config.failureUrl || urlBuilder.build('checkout/onepage/failure');
        var retryUrl = config.retryUrl || '';
        var statusUrl = config.statusUrl || '';
        var lineOrigin = config.lineOrigin ? _parseOrigin(config.lineOrigin) : 'https://checkout.line.net.ar';
        var timeoutMs = config.timeoutMs || 600000; // 10 minutes default
        var processed = false;
        var pollingTimer = null;
        var expiryTimer = null;

        _startExpiryTimer();

        window.addEventListener('message', function (event) {
            var allowedOrigins = [lineOrigin, window.location.origin];
            if (allowedOrigins.indexOf(event.origin) === -1) return;
            if (processed) return;

            console.log('[LineModo] postMessage recibido - origin:', event.origin, '- data:', JSON.stringify(event.data));

            var response = event.data;
            var status = response && response.status ? response.status : '';
            var type = response && response.type ? response.type : '';

            switch (status || type) {
                case 'ACCEPTED':
                case 'PAID':
                case 'APPROVED':
                case 'payment.completed':
                case 'line-checkout:payment-success':
                    _markProcessed();
                    _handleSuccess();
                    break;

                case 'IN_PROGRESS':
                case 'PENDING':
                    _handlePending();
                    break;

                case 'line-checkout:ready':
                    // informational: widget loaded — no-op
                    break;

                case 'line-checkout:payment-started':
                    // informational: QR shown, payment not yet scanned — no-op
                    break;

                case 'REJECTED':
                case 'line-checkout:payment-failure':
                    _markProcessed();
                    _handleRejected();
                    break;

                case 'ERROR':
                    _markProcessed();
                    _handleError();
                    break;

                case 'CANCELLED':
                case 'line-checkout:payment-cancelled':
                case 'line-checkout:modal-closed':
                    _markProcessed();
                    _handleCancelled();
                    break;

                case 'EXPIRED':
                    _markProcessed();
                    _handleExpired();
                    break;

                case 'line-checkout:modal-opened':
                    // no-op: informational event
                    break;

                default:
                    _markProcessed();
                    _handleUnknown(status);
            }
        }, false);

        /**
         * Extracts the origin (scheme + host + port) from a URL string.
         *
         * @param {string} url
         * @return {string}
         */
        function _parseOrigin(url) {
            try {
                var parsed = new URL(url);
                return parsed.origin;
            } catch (e) {
                return url;
            }
        }

        /**
         * Prevents double-processing and clears timers.
         */
        function _markProcessed() {
            processed = true;
            _clearTimers();
        }

        /**
         * Clears polling and expiry timers.
         */
        function _clearTimers() {
            if (pollingTimer) {
                clearTimeout(pollingTimer);
                pollingTimer = null;
            }

            if (expiryTimer) {
                clearTimeout(expiryTimer);
                expiryTimer = null;
            }
        }

        /**
         * Starts the expiry timer. If no payment message arrives within
         * timeoutMs, the QR is considered expired.
         */
        function _startExpiryTimer() {
            expiryTimer = setTimeout(function () {
                if (!processed) {
                    _markProcessed();

                    if (statusUrl) {
                        _pollStatus(function (polledStatus) {
                            switch (polledStatus) {
                                case 'ACCEPTED': _handleSuccess(); break;
                                case 'REJECTED': _handleRejected(); break;
                                case 'ERROR': _handleError(); break;
                                case 'CANCELLED': _handleCancelled(); break;
                                default: _handleExpired();
                            }
                        });
                    } else {
                        _handleExpired();
                    }
                }
            }, timeoutMs);
        }

        /**
         * Polls the status endpoint once as a fallback.
         *
         * @param {Function} callback
         */
        function _pollStatus(callback) {
            if (!statusUrl) {
                callback('UNKNOWN');
                return;
            }

            $.ajax({
                url: statusUrl,
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    callback(data.status || 'UNKNOWN');
                },
                error: function () {
                    callback('UNKNOWN');
                }
            });
        }

        /**
         * Shows the success message using the status message block.
         */
        function _handleSuccess() {
            _hideCheckout();
            _showStatusMessage(
                '',
                $.mage.__('Your payment was successful. Thank you for your purchase!'),
                false
            );
        }

        /**
         * Shows a spinner/message while the payment is being processed.
         * Does NOT redirect — waits for a follow-up postMessage.
         */
        function _handlePending() {
            var $msg = $('#line-modo-status-message');
            if ($msg.is(':visible')) return;

            _showStatusMessage(
                '⏳',
                $.mage.__('Your payment is being processed. Please wait...'),
                false
            );
        }

        /**
         * Shows an inline rejection message with retry option.
         */
        function _handleRejected() {
            _hideCheckout();
            _showStatusMessage(
                '❌',
                $.mage.__('Your payment was rejected. Please check your balance or card limit and try again.'),
                true
            );
        }

        /**
         * Shows an inline error message with retry option.
         */
        function _handleError() {
            _hideCheckout();
            _showStatusMessage(
                '⚠️',
                $.mage.__('An error occurred while processing your payment. Please try again.'),
                true
            );
        }

        /**
         * Shows a cancellation message with retry option.
         */
        function _handleCancelled() {
            _hideCheckout();
            _showStatusMessage(
                '🚫',
                $.mage.__('You cancelled the payment. You can try again or return to the store.'),
                true
            );
        }

        /**
         * Shows an expiry message. Retry button reloads the page to
         * generate a new QR via the retry URL.
         */
        function _handleExpired() {
            _hideCheckout();
            _showStatusMessage(
                '⌛',
                $.mage.__('The QR code has expired. Click below to generate a new one.'),
                true,
                $.mage.__('Generate new QR')
            );
        }

        /**
         * Fallback for unexpected statuses.
         *
         * @param {string} status
         */
        function _handleUnknown(status) {
            _hideCheckout();
            _showStatusMessage(
                '⚠️',
                $.mage.__('An unexpected error occurred. Please try again or contact support.'),
                true
            );
        }

        /**
         * Hides the checkout container.
         */
        function _hideCheckout() {
            $('#line-checkout').fadeOut(200);
        }

        /**
         * Renders the status message block.
         *
         * @param {string} icon
         * @param {string} text
         * @param {boolean} showActions
         * @param {string|null} retryLabel
         */
        function _showStatusMessage(icon, text, showActions, retryLabel) {
            var $msg = $('#line-modo-status-message');

            $msg.find('.line-modo-status-icon').text(icon);
            $msg.find('.line-modo-status-text').text(text);

            if (showActions && retryUrl) {
                var label = retryLabel || $.mage.__('Try again');
                $msg.find('.line-modo-retry-btn span').text(label);
                $msg.find('.line-modo-status-actions').show();
            } else if (showActions) {
                $msg.find('.line-modo-status-actions').show();
                $msg.find('.line-modo-retry-btn').hide();
            } else {
                $msg.find('.line-modo-status-actions').hide();
            }

            $msg.fadeIn(300);
        }
    };
});
