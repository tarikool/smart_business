<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasTxnAttributes
{
    public function discountPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->total > 0) {
                    return $this->is_fixed_discount
                        ? $this->discount_value * 100 / $this->total
                        : $this->discount_value;
                }

                return 0;
            }
        );
    }

    public function discountAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->total * $this->discount_percentage * 0.01;
            }
        );
    }

    public function totalPaid(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->total - $this->discountAmount - $this->due;
            }
        );
    }

    public function netTotal(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->total - $this->discountAmount;
            }
        );
    }
}
