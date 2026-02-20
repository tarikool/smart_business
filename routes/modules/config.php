<?php

use App\Http\Controllers\Api\Store\ConfigDataController;
use App\Http\Controllers\Api\Store\ProductCategoryController;
use App\Http\Controllers\Api\Store\UnitOptionController;

Route::middleware(['auth:sanctum'])->prefix('config')->group(function () {
    Route::get('business-types', [ConfigDataController::class, 'businessTypes']);
    Route::get('base-units', [ConfigDataController::class, 'baseUnits']);
    Route::get('product-categories', [ConfigDataController::class, 'productCategories']);
    Route::get('constants', [ConfigDataController::class, 'constants']);

    Route::apiResource('{baseUnit}/unit-options', UnitOptionController::class)->only('store');
    Route::apiResource('product-categories', ProductCategoryController::class)->only('store');
});
