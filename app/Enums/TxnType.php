<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum TxnType: string
{
    use EnumTrait;

    /**
     * This enum is used as a column type in the database.
     *
     * Table: transactions
     * Column: txn_type (ENUM type)
     *
     * Ensure migrations are updated while changing these values
     */
    case PURCHASE = 'purchase';
    case MACHINERY_PURCHASE = 'machinery_purchase';
    case PRODUCTION = 'production';

    //    Sell transaction types
    case SALE = 'sale';
    case MACHINERY_SALE = 'machinery_sale';
    case MACHINERY_RENT = 'machinery_rent';
    case ADVISORY = 'advisory';

    public static function purchaseTypes()
    {
        return [
            self::PURCHASE,
            self::MACHINERY_PURCHASE,
            self::PRODUCTION,
        ];
    }

    public static function saleTypes()
    {
        return [
            self::SALE,
            self::MACHINERY_SALE,
            self::MACHINERY_RENT,
            self::ADVISORY,
        ];
    }

    public function entryType()
    {
        return match ($this) {
            self::PURCHASE,
            self::MACHINERY_PURCHASE,
            self::PRODUCTION => StockEntryType::PURCHASE,

            self::SALE,
            self::MACHINERY_SALE,
            self::MACHINERY_RENT,
            self::ADVISORY => StockEntryType::SALE,
        };
    }

    public function isBuy(): bool
    {
        return in_array($this, self::purchaseTypes());
    }

    public function isSale(): bool
    {
        return in_array($this, self::saleTypes());
    }

    public function isRent(): bool
    {
        return $this == self::MACHINERY_RENT;
    }
}
