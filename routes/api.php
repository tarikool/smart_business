<?php

use App\Http\Controllers\Api\Store\MachineryController;
use App\Http\Controllers\Api\Store\ProductListController;
use App\Http\Controllers\Api\Store\StoreDataController;
use App\Http\Controllers\Api\Store\TagController;
use App\Http\Controllers\Api\User\ContactController;
use Illuminate\Support\Facades\Route;

require 'modules/auth.php';
require 'modules/config.php';
require 'modules/transactions.php';

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('store-data', StoreDataController::class);
    Route::get('my-store/store-data', [StoreDataController::class, 'myStoreData']);
    Route::apiResource('products', ProductListController::class)->only('index');
    Route::apiResource('machinery', MachineryController::class)->only('index', 'store');
    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('advisory/tags', TagController::class)->only('index', 'store');
});
