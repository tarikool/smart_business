<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\Product\StoreRequest;
use App\Http\Requests\Store\Product\UpdateRequest;
use App\Http\Resources\Store\Product\MyProductResource;
use App\Http\Resources\Store\Product\ProductResource;
use App\Http\Resources\Store\ProductCategoryResource;
use App\Http\Resources\Unit\UnitResource;
use App\Models\BaseUnit;
use App\Services\StoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreDataController extends Controller
{
    public $user;

    public function __construct(public StoreService $storeService, Request $request)
    {
        $this->user = $request->user();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = $this->storeService->getProducts($this->user);
        $categories = $this->storeService->getCategories($this->user);
        $baseunits = BaseUnit::get();

        return $this->successResponse([
            'units' => UnitResource::collection($baseunits),
            'categories' => ProductCategoryResource::collection($categories),
            'products' => ProductResource::collection($products),
        ]);
    }

    /**
     * @return JsonResponse
     *                      Return only products, cats available in store
     */
    public function myStoreData()
    {
        $products = $this->storeService->getMyProducts($this->user);
        $categories = $this->storeService->getMyCategories($this->user);

        return $this->successResponse([
            'categories' => ProductCategoryResource::collection($categories),
            'products' => MyProductResource::collection($products),
        ], 'Only available in my store');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $this->storeService->saveUserProducts($request, $this->user);

        $this->user->userProfile()->update(['is_store_setup_complete' => true]);

        return $this->successResponse(msg: 'Product list saved successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, $userId)
    {
        $this->storeService->saveUserProducts($request, $this->user);

        return $this->successResponse(msg: 'Product list updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
