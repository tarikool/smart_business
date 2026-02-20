<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseUnit extends Model
{
    protected $guarded = ['id'];

    public function unitOptions()
    {
        return $this->hasMany(UnitOption::class)
            ->withAttributes(['user_id' => auth()->id()],
                asConditions: false
            )->oldest();
    }

    public function defaultUnit()
    {
        return $this->hasOne(UnitOption::class)
            ->where('is_default', true)
            ->oldest();
    }
}
