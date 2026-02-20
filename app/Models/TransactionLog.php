<?php

namespace App\Models;

use App\Enums\TxnLogType;
use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'log_type' => TxnLogType::class,
            'data' => 'array',
        ];
    }
}
