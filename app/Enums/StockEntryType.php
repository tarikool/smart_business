<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum StockEntryType: string
{
    use EnumTrait;

    /**
     * This enum is used as a column type in the database.
     *
     * Table: stock_ledger
     * Column: entry_type (ENUM type)
     *
     * Ensure migrations are updated while changing these values
     */
    //    IN
    case PURCHASE = 'purchase';
    case RETURN = 'return';
    case OPENING_BALANCE = 'opening_balance';
    case PRODUCTION = 'production';
    case TRANSFER_IN = 'transfer_in';

    //    OUT
    case SALE = 'sale';
    case DAMAGE = 'damage';
    case EXPIRED = 'expired';
    case TRANSFER_OUT = 'transfer_out';
    case LOSS = 'loss';
    case CONSUME = 'consume';

    // Both
    case ADJUST = 'adjust';

    public function signedQty($qty)
    {
        return match ($this) {
            self::PURCHASE,
            self::RETURN,
            self::OPENING_BALANCE,
            self::PRODUCTION,
            self::TRANSFER_IN => +$qty,

            self::SALE,
            self::DAMAGE,
            self::EXPIRED,
            self::TRANSFER_OUT,
            self::LOSS,
            self::CONSUME => -$qty,

            self::ADJUST => $qty, // caller decide
        };
    }
}
