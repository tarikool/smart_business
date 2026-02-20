<?php

namespace App\Models;

use App\Casts\Decimal;
use Illuminate\Database\Eloquent\Model;

class CustomerSummery extends Model
{
    protected $table = 'transaction_summery_customers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'total_buy' => 'integer',
            'total_buy_discount' => 'integer',
            'total_buy_count' => 'integer',
            'last_buy_at' => 'date',

            'total_sale' => 'integer',
            'total_sale_discount' => 'integer',
            'total_sale_count' => 'integer',
            'last_sale_at' => 'date',

            'total_rent' => 'integer',
            'total_rent_count' => 'integer',
            'last_rent_at' => 'date',

            'due_payable' => Decimal::class.':2',
            'due_receivable' => Decimal::class.':2',
            'has_receivable' => 'boolean',
            'has_payable' => 'boolean',
        ];
    }
}
