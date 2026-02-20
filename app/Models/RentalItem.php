<?php

namespace App\Models;

use App\Casts\Decimal;
use App\Enums\RentType;
use Illuminate\Database\Eloquent\Model;

class RentalItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'rent_type' => RentType::class,
            'duration' => Decimal::class.':2',
            'unit_price' => Decimal::class.':4',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
