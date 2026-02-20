<?php

namespace App\Services\Transaction;

use App\Enums\TxnLogType;
use App\Http\Requests\Transaction\TransactionUpdateRequest;
use App\Http\Requests\Transaction\UpdateContactRequest;
use App\Models\Transaction;
use App\Services\ContactService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TransactionUpdateService
{
    public function __construct(public TransactionLogService $logService, public PaymentService $paymentService, public ContactService $contactService) {}

    public function update(TransactionUpdateRequest $request, Transaction $transaction)
    {
        try {
            return DB::transaction(function () use ($request, $transaction) {
                $oldTxn = $transaction->replicate();
                $itemsLog = $this->updateItems($request, $transaction);

                abort_if(! $itemsLog, 422, 'No items to update.');

                $transaction = $this->updateTransaction($request, $transaction);
                $this->paymentService->storePayment($transaction, -$request->return_amount);
                $this->logService->saveLog(
                    oldTxn: $oldTxn, newTxn: $transaction,
                    logType: TxnLogType::UPDATE,
                    itemsLog: $itemsLog,
                    returnAmt: $request->return_amount,
                    note: $request->note
                );

                return $transaction;
            });
        } catch (QueryException $exception) {
            Log::critical('update-products-db-error', ['user_id' => auth()->id(), 'error' => $exception->getMessage()]);
            throw new \Exception('An unexpected error occurred while saving.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('update-products-error', ['user_id' => auth()->id(), 'error' => $exception->getMessage()]);
            throw new \Exception($exception->getMessage() ?: 'Internal server error.');
        }

    }

    /**
     * @return Transaction
     */
    public function updateTransaction(TransactionUpdateRequest $request, Transaction $transaction)
    {
        $paidAmountAfterReturn = $transaction->totalPaid - $request->return_amount;
        $discountAmount = getDiscount(false, $request->new_total, $transaction->discountPercentage);

        $transaction->discount_value = $transaction->is_fixed_discount ? $discountAmount : $transaction->discount_value;
        $transaction->total = $request->new_total;
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
    public function updateItems(TransactionUpdateRequest $request, Transaction $transaction)
    {
        $itemsLog = [];
        $txnItems = $transaction->transactionItems->load('product:id,name', 'unitOption');

        foreach ($request->items as $input) {
            $txnItem = $txnItems->where('id', $input['id'])->first();
            $oldTxnItem = $txnItem->replicate();

            if ($txnItem->quantity == $input['new_qty'] && $txnItem->unit_price == $input['new_unit_price']) {
                continue;
            }
            $txnItem->quantity = $input['new_qty'];
            $txnItem->unit_price = $input['new_unit_price'];
            $txnItem->save();

            if ($updateQty = $input['new_qty'] - $oldTxnItem->quantity) {
                StockService::recordUpdate($txnItem, $updateQty);
            }
            $itemsLog[] = $this->logService->formatItemLogData($oldTxnItem, $txnItem);
        }

        return $itemsLog;
    }

    /**
     * @param  UpdateContactRequest  $request
     * @param  Transaction  $transaction
     * @return void
     */
    public function updateContact($request, $transaction)
    {
        DB::transaction(function () use ($request, $transaction) {
            $contact = $this->contactService->updateContact(
                userId: $transaction->user_id,
                data: $request->validated()
            );

            if ($contact->id != $transaction->contact_id) {
                $oldTxn = $transaction->replicate();
                $transaction->contact_id = $contact->id;
                $transaction->save();

                $this->logService->saveLog(oldTxn: $oldTxn, newTxn: $transaction, logType: TxnLogType::UPDATE);
            }
        });

    }
}
