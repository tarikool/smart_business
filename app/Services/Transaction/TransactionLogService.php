<?php

namespace App\Services\Transaction;

use App\Enums\TxnLogType;
use App\Models\Transaction;

class TransactionLogService
{
    /**
     * @param  Transaction  $oldTxn
     * @param  Transaction  $newTxn
     * @param  TxnLogType  $logType
     * @param  array  $itemsLog
     * @param  string|null  $note
     * @return mixed
     */
    public function saveLog($oldTxn, $newTxn, $logType, $itemsLog = [], $returnAmt = 0, $note = null)
    {
        $data = $this->formatLogData($oldTxn, $newTxn, $itemsLog);
        $addAmt = $newTxn->netTotal - $oldTxn->netTotal;

        return $newTxn->logs()->create([
            'log_type' => $logType,
            'data' => $data,
            'return_amt' => $returnAmt,
            'add_amt' => max($addAmt, 0),
            'deduct_amt' => -min($addAmt, 0),
            'items_updated' => count($itemsLog),
            'note' => $note,
        ]);

    }

    public function formatLogData($oldTxn, $newTxn, $itemsLog)
    {
        return [
            'before' => $oldTxn->only('total', 'due', 'due_date', 'contact_id'),
            'after' => $newTxn->only('total', 'due', 'due_date', 'contact_id'),
            'items' => $itemsLog,
            'changed' => [
                'total' => $newTxn->total != $oldTxn->total,
                'due' => $newTxn->due != $oldTxn->due,
                'due_date' => $newTxn->due_date != $oldTxn->due_date,
                'contact_id' => $newTxn->contact_id != $oldTxn->contact_id,
                'items' => count($itemsLog) > 0,
            ],
        ];
    }

    public function formatItemLogData($oldTxnItem, $newTxnItem)
    {
        return [
            'id' => $newTxnItem->id,
            'product_id' => $newTxnItem->product_id,
            'product_name' => $newTxnItem->product->name,
            'before' => $oldTxnItem->only('quantity', 'unit_price'),
            'after' => $newTxnItem->only('quantity', 'unit_price'),
            'changed' => [
                'quantity' => $newTxnItem->quantity != $oldTxnItem->quantity,
                'unit_price' => $newTxnItem->unit_price != $oldTxnItem->unit_price,
            ],
        ];
    }
}
