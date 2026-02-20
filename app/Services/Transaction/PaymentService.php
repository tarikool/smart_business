<?php

namespace App\Services\Transaction;

use App\Enums\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * @param  Transaction  $transaction
     * @param  PaymentMethod  $method
     * @param  float  $amount
     * @return TransactionPayment
     */
    public function storePayment($transaction, $amount, $method = PaymentMethod::CASH)
    {
        if ($amount != 0) {
            return $transaction->payments()->create([
                'payment_method' => $method,
                'amount' => $amount,
            ]);
        }

    }

    /**
     * @param  Transaction  $transaction
     * @param  float  $amount
     * @return Transaction
     *
     * @throws \Throwable
     */
    public function payDueTransaction($transaction, $amount)
    {
        return DB::transaction(function () use ($transaction, $amount) {
            $transaction->due -= $amount;

            abort_if($transaction->due < 0, 422, 'Amount is out of range');
            $transaction->is_due = $transaction->due > 0;
            $transaction->save();
            $this->storePayment($transaction, $amount);

            return $transaction;
        });
    }
}
