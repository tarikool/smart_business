<?php

namespace App\Services\Transaction;

use App\Events\TransactionCompleted;
use App\Http\Requests\Transaction\Sale\AdvisoryRequest;
use App\Http\Requests\Transaction\Sale\MachineryRentRequest;
use App\Http\Requests\Transaction\Sale\SellRequest;
use App\Models\Transaction;
use App\Services\ContactService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(public PaymentService $paymentService, public ContactService $contactService) {}

    /**
     * @return Transaction
     *
     * @throws \Throwable
     */
    public function storeTransaction(SellRequest|MachineryRentRequest|AdvisoryRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $transaction = match (get_class($request)) {
                    SellRequest::class => $this->sellProducts($request),
                    MachineryRentRequest::class => $this->rentMachinery($request),
                    AdvisoryRequest::class => $this->storeAdvisory($request),
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
    public function sellProducts(SellRequest $request)
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
    public function rentMachinery(MachineryRentRequest $request)
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
        $this->paymentService->storePayment($transaction, $transaction->totalPaid);

        collect($request->validated('products'))->map(function ($productData) use ($transaction) {
            return $transaction->rentalItems()->create($productData);
        });

        return $transaction;
    }

    /**
     * @return Transaction
     */
    public function storeAdvisory(AdvisoryRequest $request)
    {
        $txnData = Arr::except($request->validated(), ['tags', 'category_id', 'advice']);
        $advisoryData = Arr::only($request->validated(), ['tags', 'category_id', 'advice']);
        $customer = $this->contactService->getOrCreateCustomer(
            isNew: $request->is_new_customer,
            userId: $request->user_id,
            customerId: $request->customer_id,
            data: $request->customer
        );
        $txnData['contact_id'] = $customer->id;
        $transaction = Transaction::create($txnData);
        $transaction->advisory()->create($advisoryData);
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
