<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvisoryTransaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
