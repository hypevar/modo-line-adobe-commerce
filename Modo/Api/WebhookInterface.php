<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types=1);

namespace Line\Modo\Api;

interface WebhookInterface
{
    public const SIGNATURE_TOLERANCE_SECONDS = 300;
    public const EVENT_PAYMENT_COMPLETED = 'payment.completed';
    public const EVENT_PAYMENT_FAILED = 'payment.failed';
    public const EVENT_PAYMENT_CANCELLED = 'payment.cancelled';
    public const EVENT_PAYMENT_PROCESSING = 'payment.processing';

    public const PAYMENT_STATUS_SUCCESS = 'SUCCESS';
    public const PAYMENT_STATUS_FAILED = 'FAILED';

    public const IDEMPOTENCY_KEY = 'webhook_event_ids';
    public const REFERENCE_SEPARATOR = '||';

    public const ERROR_CODE_DESCRIPTIONS = [
        'INSUFFICIENT_FUNDS' => 'Insufficient funds',
        'CARD_DECLINED' => 'Card declined',
        'CARD_EXPIRED' => 'Expired card',
        'INVALID_CARD' => 'Invalid card',
        'FRAUD_SUSPECTED' => 'Suspected fraud',
        'LIMIT_EXCEEDED' => 'Limit exceeded',
        'USER_CANCELLED' => 'Cancelled by user',
        'TIMEOUT' => 'Timeout',
        'PROVIDER_ERROR' => 'Provider error',
    ];
}
