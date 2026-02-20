<?php

namespace App\Services\Transaction;

use App\Events\TransactionCompleted;
use App\Http\Requests\Transaction\Purchase\MachineryPurchaseRequest;
use App\Http\Requests\Transaction\Purchase\ProductionRequest;
use App\Http\Requests\Transaction\Purchase\PurchaseRequest;
use App\Models\Transaction;
use App\Services\ContactService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(public PaymentService $paymentService, public ContactService $contactService) {}

    /**
     * @return Transaction
     *
     * @throws \Throwable
     */
    public function storeTransaction(PurchaseRequest|MachineryPurchaseRequest|ProductionRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $transaction = match (get_class($request)) {
                    PurchaseRequest::class,
                    MachineryPurchaseRequest::class => $this->purchaseProducts($request),
                    ProductionRequest::class => $this->storeProduction($request),
                };
                TransactionCompleted::dispatch($transaction, 'create');

                return $transaction->withoutRelations();
            });
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * @return Transaction
     */
    public function purchaseProducts(PurchaseRequest|MachineryPurchaseRequest $request)
    {
        $txnData = Arr::except($request->validated(), ['products', 'customer']);
        $customer = $this->contactService->getOrCreateCustomer(
            isNew: $request->is_new_customer,
            userId: $request->user_id,
            customerId: $request->customer_id,
            data: $request->customer
        );
        $txnData['contact_id'] = $customer->id;
        $transaction = Transaction::create($txnData);
        $this->storeTransactionItems($transaction, $request->validated('products'));
        $this->paymentService->storePayment($transaction, $transaction->totalPaid, $request->payment_method);

        return $transaction;
    }

    /**
     * @return Transaction
     */
    public function storeProduction(ProductionRequest $request)
    {
        $txnData = Arr::except($request->validated(), ['products']);
        $transaction = Transaction::create($txnData);
        $this->storeTransactionItems($transaction, $request->validated('products'));
        $this->paymentService->storePayment($transaction, $transaction->totalPaid);

        return $transaction;
    }

    /**
     * @param  Transaction  $transaction
     * @param  array  $products
     * @return Collection
     */
    public function storeTransactionItems($transaction, $products)
    {
        return collect($products)->map(function ($productData) use ($transaction) {
            $txnItem = $transaction->transactionItems()->create($productData);
            $stockHistory = StockService::recordFromTxn($txnItem, $productData['max_stock']);

            return $txnItem;
        });
    }
}
