<?php

namespace App\Enums;

enum StatusChangeReason: string
{
    // Completion reasons
    case PAYMENT_RECEIVED = 'payment_received';
    case MANUAL_APPROVAL = 'manual_approval';
    case AUTOMATIC_PROCESSING = 'automatic_processing';

    // Cancellation reasons
    case USER_REQUEST = 'user_request';
    case PAYMENT_FAILED = 'payment_failed';
    case FRAUD_DETECTION = 'fraud_detection';

    // Refund reasons
    case DISPUTE_RESOLUTION = 'dispute_resolution';
    case ERROR_CORRECTION = 'error_correction';

    // General reasons
    case SYSTEM_UPDATE = 'system_update';

    /**
     * Get completion reasons.
     */
    public static function completionReasons(): array
    {
        return [
            self::PAYMENT_RECEIVED,
            self::MANUAL_APPROVAL,
            self::AUTOMATIC_PROCESSING,
        ];
    }

    /**
     * Get cancellation reasons.
     */
    public static function cancellationReasons(): array
    {
        return [
            self::USER_REQUEST,
            self::PAYMENT_FAILED,
            self::FRAUD_DETECTION,
        ];
    }

    /**
     * Get refund reasons.
     */
    public static function refundReasons(): array
    {
        return [
            self::USER_REQUEST,
            self::DISPUTE_RESOLUTION,
            self::ERROR_CORRECTION,
        ];
    }
}
