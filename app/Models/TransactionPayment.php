<?php

namespace App\Models;

use App\Casts\Decimal;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class TransactionPayment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => Decimal::class.':4',
            'payment_method' => PaymentMethod::class,
        ];
    }
}
