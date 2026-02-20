<?php

namespace App\Models;

use App\Casts\Decimal;
use Illuminate\Database\Eloquent\Model;

class UnitOption extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'multiplier' => Decimal::class.':6',
            'is_default' => 'boolean',
        ];
    }

    public function baseUnit()
    {
        return $this->belongsTo(BaseUnit::class);
    }
}
