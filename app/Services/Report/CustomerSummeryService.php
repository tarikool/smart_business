<?php

namespace App\Services\Report;

use App\Enums\TxnType;
use App\Models\CustomerSummery;
use App\Models\Transaction;

class CustomerSummeryService
{
    public function updateSummeryOnCreate(Transaction $transaction)
    {
        /** @var TxnType $txnType * */
        $txnType = $transaction->txn_type;

        $summery = CustomerSummery::firstOrNew([
            'user_id' => $transaction->user_id,
            'customer_id' => $transaction->contact_id,
        ]);

        if ($txnType->isBuy() && ! $txnType->isRent()) {
            $summery->total_buy += round($transaction->net_total);
            $summery->total_buy_discount += round($transaction->discountAmount);
            $summery->last_buy_at = $transaction->txn_date;
            $summery->due_payable += $transaction->due;
            $summery->total_buy_count++;
        }

        if ($txnType->isSale()) {
            $summery->total_sale += round($transaction->net_total);
            $summery->total_sale_discount += round($transaction->discountAmount);
            $summery->last_sale_at = $transaction->txn_date;
            $summery->due_receivable += $transaction->due;
            $summery->total_sale_count++;
        }

        if ($txnType->isRent()) {
            $summery->total_rent += round($transaction->net_total);
            $summery->last_rent_at = $transaction->txn_date;
            $summery->due_receivable += $transaction->due;
            $summery->total_rent_count++;
        }

        $summery->total_txn_count++;
        $summery->has_receivable = $summery->due_receivable > 0;
        $summery->has_payable = $summery->due_payable > 0;
        $summery->save();
    }

    public function updateSummeryOnUpdate($txnId)
    {
        logger('update');
    }
}
