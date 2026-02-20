<?php

namespace App\Models;

use App\Casts\Decimal;
use Illuminate\Database\Eloquent\Model;

class ProductRecentPrice extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'buy_date' => 'date',
            'sell_date' => 'date',
            'recent_buy' => Decimal::class.':2',
            'recent_sell' => Decimal::class.':2',
            'avg_buy' => Decimal::class.':2',
            'avg_sell' => Decimal::class.':2',
            'total_buy_qty' => Decimal::class.':2',
            'total_sale_qty' => Decimal::class.':2',
        ];
    }
}
