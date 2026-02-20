<?php

namespace App\Models;

use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'contact_type' => ContactType::class,
            'is_default' => 'boolean',
        ];
    }
}
