<?php

namespace App\Services\Transaction;

use App\Enums\TxnLogType;
use App\Http\Requests\Transaction\ReturnRequest;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(public TransactionLogService $logService, public PaymentService $paymentService) {}

    public function return(ReturnRequest $request, Transaction $transaction)
    {
        try {
            return DB::transaction(function () use ($request, $transaction) {
                $oldTxn = $transaction->replicate();
                $itemsLog = $this->returnItems($request, $transaction);
                $transaction = $this->updateTransactionAfterReturn($request, $transaction);
                $this->paymentService->storePayment($transaction, -$request->return_amount);
                $this->logService->saveLog(
                    oldTxn: $oldTxn,
                    newTxn: $transaction, logType: TxnLogType::RETURN,
                    itemsLog: $itemsLog, returnAmt: $request->return_amount,
                    note: $request->note
                );

                return $transaction;
            });
        } catch (QueryException $exception) {
            Log::critical('return-products-db-error', ['user_id' => auth()->id(), 'error' => $exception->getMessage()]);
            throw new \Exception('An unexpected error occurred while saving.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('return-products-error', ['user_id' => auth()->id(), 'error' => $exception->getMessage()]);
            throw new \Exception($exception->getMessage() ?: 'Internal server error.');
        }

    }

    /**
     * @return Transaction
     */
    public function updateTransactionAfterReturn(ReturnRequest $request, Transaction $transaction)
    {
        $paidAmountAfterReturn = $transaction->totalPaid - $request->return_amount;
        $discountAmount = getDiscount(false, $request->total, $transaction->discountPercentage);

        $transaction->discount_value = $transaction->is_fixed_discount ? $discountAmount : $transaction->discount_value;
        $transaction->total -= $request->total;
        $transaction->due = $transaction->total - $discountAmount - $paidAmountAfterReturn;
        $transaction->is_due = $transaction->due > 0;

        abort_if($transaction->due < 0, 422, 'Due is out of range.');
        abort_if($transaction->total < 0, 422, 'Total is out of range.');

        $transaction->save();

        return $transaction;
    }

    /**
     * @return array
     */
    public function returnItems(ReturnRequest $request, Transaction $transaction)
    {
        $txnItems = TransactionItem::with('product:id,name', 'unitOption')
            ->find(Arr::pluck($request->items, 'id'));

        foreach ($request->items as $input) {
            $txnItem = $txnItems->where('id', $input['id'])->first();
            $oldTxnItem = $txnItem->replicate();
            $txnItem->quantity -= $input['return_qty'];

            abort_if($txnItem->quantity < 0, 422, 'Quantity out of range.');

            $txnItem->save();
            StockService::recordReturn($txnItem, $input['return_qty']);
            $itemsLog[] = $this->logService->formatItemLogData($oldTxnItem, $txnItem);
        }

        return $itemsLog;
    }
}
