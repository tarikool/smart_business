<?php

namespace App\Models;

use App\Casts\Decimal;
use App\Enums\TxnType;
use App\Models\Traits\HasTxnAttributes;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasTxnAttributes;

    protected $guarded = ['id'];

    protected $appends = ['total_paid', 'discount_percentage', 'discount_amount', 'net_total'];

    protected function casts(): array
    {
        return [
            'total' => Decimal::class.':4',
            'due' => Decimal::class.':4',
            'discount_value' => Decimal::class.':4',
            'is_due' => 'boolean',
            'is_fixed_discount' => 'boolean',
            'txn_date' => 'datetime',
            'due_date' => 'date',
            'txn_type' => TxnType::class,

        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class, 'txn_id')
            ->withAttributes([
                'txn_id' => $this->id,
                'user_id' => $this->user_id,
                'txn_type' => $this->txn_type,
                'txn_date' => $this->txn_date,
            ], asConditions: false);
    }

    public function rentalItems()
    {
        return $this->hasMany(RentalItem::class, 'txn_id')
            ->withAttributes([
                'txn_id' => $this->id,
                'user_id' => $this->user_id,
            ], asConditions: false);
    }

    public function advisory()
    {
        return $this->hasOne(AdvisoryTransaction::class, 'txn_id')
            ->withAttributes([
                'txn_id' => $this->id,
                'user_id' => $this->user_id,
            ], asConditions: false);
    }

    public function logs()
    {
        return $this->hasMany(TransactionLog::class, 'txn_id')
            ->withAttributes(['user_id' => $this->user_id], asConditions: false);
    }

    public function payments()
    {
        return $this->hasMany(TransactionPayment::class, 'txn_id')
            ->withAttributes(['user_id' => $this->user_id], asConditions: false);
    }
}
