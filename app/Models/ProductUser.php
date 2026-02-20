<?php

namespace App\Models;

use App\Casts\Decimal;
use Illuminate\Database\Eloquent\Model;

class ProductUser extends Model
{
    protected $table = 'product_user';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'max_stock' => Decimal::class.':4',
            'current_stock' => Decimal::class.':4',
            'allow_production' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
