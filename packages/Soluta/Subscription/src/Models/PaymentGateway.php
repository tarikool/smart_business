<?php

namespace Soluta\Subscription\Models;

use App\Enums\GatewayType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'name' => GatewayType::class,
        ];
    }
}
