<?php

namespace App\Exceptions;

use Exception;

/**
 * Lỗi nghiệp vụ của RefundService.
 *
 * Dùng exception riêng (thay vì ValidationException) vì service được gọi từ
 * cả controller lẫn console command — caller tự quyết định cách hiển thị.
 */
class RefundException extends Exception
{
    public const SOURCE_NOT_FOUND = 'SOURCE_NOT_FOUND';

    public const NOT_REFUNDABLE_STATE = 'NOT_REFUNDABLE_STATE';

    public const NOTHING_TO_REFUND = 'NOTHING_TO_REFUND';

    public const QUANTITY_EXCEEDED = 'QUANTITY_EXCEEDED';

    public const AMOUNT_EXCEEDED = 'AMOUNT_EXCEEDED';

    public const UNPAID_COD = 'UNPAID_COD';

    public const UNPAID_ORDER = 'UNPAID_ORDER';

    public const DUPLICATE_REFUND = 'DUPLICATE_REFUND';

    public const INVALID_TRANSITION = 'INVALID_TRANSITION';

    public const DETAIL_MISMATCH = 'DETAIL_MISMATCH';

    public const INVALID_REASON = 'INVALID_REASON';

    public readonly string $errorCode;

    public function __construct(string $errorCode, string $message)
    {
        $this->errorCode = $errorCode;

        parent::__construct($message);
    }
}
