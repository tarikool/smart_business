<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'allow_production' => 'boolean',
        ];
    }

    public function productUser()
    {
        return $this->hasOne(ProductUser::class)
            ->withAttributes([
                'user_id' => auth()->id(),
            ], asConditions: true);
    }

    public function baseUnit()
    {
        return $this->belongsTo(BaseUnit::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function recentPrice()
    {
        return $this->hasOne(ProductRecentPrice::class)->where('user_id', auth()->id());
    }
}
