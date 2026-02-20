<?php

namespace App\Http\Controllers\Api\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\TransactionUpdateRequest;
use App\Http\Requests\Transaction\UpdateContactRequest;
use App\Models\Transaction;
use App\Services\Transaction\PaymentService;
use App\Services\Transaction\TransactionUpdateService;
use Illuminate\Http\Request;

class TransactionUpdateController extends Controller
{
    public function __construct(public TransactionUpdateService $updateService) {}

    public function update(TransactionUpdateRequest $request, Transaction $transaction)
    {
        $transaction = $this->updateService->update($request, $transaction);

        return $this->successResponse($transaction, 'Items successfully updated.');
    }

    public function updateContact(UpdateContactRequest $request, Transaction $transaction)
    {
        $this->updateService->updateContact($request, $transaction);

        return $this->successResponse(msg: 'Contact updated successfully.');
    }

    public function duePayment(Request $request, Transaction $transaction, PaymentService $paymentService)
    {
        abort_unless($transaction->is_due, 422, 'No balance due.');

        $request->validate(['amount' => 'required|numeric|gt:0|lte:'.$transaction->due]);

        $paymentService->payDueTransaction($transaction, $request->amount);

        return $this->successResponse(msg: 'Payment successful.');
    }
}
