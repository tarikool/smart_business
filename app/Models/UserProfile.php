<?php

namespace App\Models;

use App\Casts\CoordinateCast;
use App\Models\Scopes\CoordinateScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([CoordinateScope::class])]
class UserProfile extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'coordinates' => CoordinateCast::class,
        ];
    }

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
