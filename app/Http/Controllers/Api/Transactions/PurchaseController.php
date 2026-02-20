<?php

namespace App\Http\Controllers\Api\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\Purchase\MachineryPurchaseRequest;
use App\Http\Requests\Transaction\Purchase\ProductionRequest;
use App\Http\Requests\Transaction\Purchase\PurchaseRequest;
use App\Http\Resources\Transaction\TransactionResource;
use App\Services\Transaction\PurchaseService;

class PurchaseController extends Controller
{
    public function __construct(public PurchaseService $purchaseService) {}

    public function purchaseProducts(PurchaseRequest $request)
    {
        $transaction = $this->purchaseService->storeTransaction($request);

        return $this->successResponse(new TransactionResource($transaction));
    }

    public function purchaseMachinery(MachineryPurchaseRequest $request)
    {
        $transaction = $this->purchaseService->storeTransaction($request);

        return $this->successResponse(new TransactionResource($transaction), 'Machinery purchased successfully.');
    }

    public function production(ProductionRequest $request)
    {
        $transaction = $this->purchaseService->storeTransaction($request);

        return $this->successResponse(new TransactionResource($transaction), 'Production stored successfully.');
    }
}
