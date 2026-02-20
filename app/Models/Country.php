<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'available_languages' => 'array',
        ];
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
