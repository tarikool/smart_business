<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_machinery' => 'boolean',
        ];
    }

    public function productUser()
    {
        return $this->hasOne(ProductUser::class, 'category_id')
            ->where('user_id', auth()->id());
    }
}
