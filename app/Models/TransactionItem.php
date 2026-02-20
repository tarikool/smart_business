<?php

namespace App\Models;

use App\Casts\Decimal;
use App\Enums\TxnType;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'txn_type' => TxnType::class,
            'txn_date' => 'date',
            'quantity' => Decimal::class.':4',
            'unit_price' => Decimal::class.':4',
        ];
    }

    public function productUser()
    {
        return $this->belongsTo(ProductUser::class, 'product_id', 'product_id')
            ->where('user_id', $this->user_id ?: auth()->id());

    }

    public function unitOption()
    {
        return $this->belongsTo(UnitOption::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
