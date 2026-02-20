<?php

namespace App\Services\Transaction;

use App\Enums\TxnType;
use App\Models\Transaction;

class TransactionListService
{
    public function getTransactions($user, ?TxnType $type = null)
    {
        $query = Transaction::query();

        $query->select(['id', 'contact_id', 'user_id', 'txn_type', 'txn_date', 'total', 'is_fixed_discount', 'discount_value', 'due', 'is_due']);

        $query->where('user_id', $user->id);
        $query->when($type, function ($query, $type) {
            $query->where('txn_type', $type);
        });

        $query->with(['contact:id,name,is_default']);
        $query->orderByDesc('txn_date');

        return $query->paginate();

    }

    public function getSummery($user, ?TxnType $type = null)
    {
        $incomeTypes = TxnType::purchaseTypes();

        return [
            'income' => 0,
            'expense' => 0,
            'balance' => 0,
        ];

    }
}
