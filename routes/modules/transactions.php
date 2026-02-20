<?php

use App\Http\Controllers\Api\Transactions\PurchaseController;
use App\Http\Controllers\Api\Transactions\ReturnController;
use App\Http\Controllers\Api\Transactions\SaleController;
use App\Http\Controllers\Api\Transactions\TransactionListController;
use App\Http\Controllers\Api\Transactions\TransactionUpdateController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('transactions/{type?}', [TransactionListController::class, 'index'])
        ->where('type', '[a-z_]+');
    Route::get('transactions/{transaction}', [TransactionListController::class, 'show'])
        ->where('transaction', '[0-9]+');
    Route::post('transactions/purchase', [PurchaseController::class, 'purchaseProducts']);
    Route::post('transactions/machinery_purchase', [PurchaseController::class, 'purchaseMachinery']);
    Route::post('transactions/production', [PurchaseController::class, 'production']);

    Route::post('transactions/sale', [SaleController::class, 'sellProducts']);
    Route::post('transactions/machinery_rent', [SaleController::class, 'rentMachinery']);
    Route::post('transactions/advisory', [SaleController::class, 'storeAdvisory']);

    Route::middleware(['check.owner:transaction'])->group(function () {
        Route::post('{transaction}/return', [ReturnController::class, 'store']);
        Route::put('{transaction}/items', [TransactionUpdateController::class, 'update']);
        Route::put('{transaction}/contact', [TransactionUpdateController::class, 'updateContact']);
        Route::post('{transaction}/payments', [TransactionUpdateController::class, 'duePayment']);
    });

});
