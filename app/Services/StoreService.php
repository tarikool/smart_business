<?php

namespace App\Services;

use App\Enums\Directory;
use App\Helpers\CustomHelper;
use App\Models\BaseUnit;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUser;
use App\Models\User;
use App\Services\Transaction\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StoreService
{
    public function getBaseUnits($user)
    {
        return BaseUnit::withWhereHas('unitOptions', function ($query) use ($user) {
            $query->whereNull('user_id')
                ->orWhere('user_id', $user->id);
        })->get();
    }

    public function getProducts($user)
    {
        return Product::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->with(['productUser', 'baseUnit', 'category'])
            ->withExists('productUser')
            ->get();
    }

    public function getMyProducts($user)
    {
        return Product::query()
            ->select('id', 'name', 'category_id', 'photo', 'base_unit_id')
            ->whereHas('productUser')
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->with([
                'productUser:id,product_id,current_stock,max_stock,allow_production',
                'baseUnit:id,name,symbol',
                'category:id,name,is_machinery',
                'recentPrice:product_id,avg_buy,avg_sell,recent_buy,recent_sell,buy_date',
            ])
            ->get();
    }

    /**
     * @param  User  $user
     * @return ProductCategory[]|mixed
     */
    public function getCategories($user)
    {
        return ProductCategory::withExists('productUser')
            ->where('business_type_id', $user->userProfile->business_type_id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMyCategories($user)
    {
        return ProductCategory::query()
            ->whereHas('productUser')
            ->where('business_type_id', $user->userProfile->business_type_id)
            ->get();
    }

    /**
     * @param  Request  $request
     * @param  User  $user
     * @return mixed
     */
    public function saveUserProducts($request, $user)
    {
        DB::beginTransaction();
        try {
            $selectedProducts = Product::with('productUser')->find($request->selected_products);
            $newProducts = $this->createNewProducts($request->new_products, $user);
            $previousStocks = ProductUser::whereUserId($user->id)->get()->mapWithKeys(function ($item) {
                return [$item->product_id => $item->current_stock];
            });

            // To Sync with attributes
            $allProducts = $newProducts->merge($selectedProducts)->mapWithKeys(function ($product) {
                $productUser = $product->productUser;

                return [$product->id => [
                    'category_id' => $product->category_id,
                    'max_stock' => $productUser ? $productUser->max_stock
                        : ($product->max_stock ?: 0),
                    'allow_production' => $productUser ? $productUser->allow_production
                        : ($product->allow_production ?: false),
                ]];
            });

            $synced = $user->userProducts()->sync($allProducts);
            $this->resetStocks($synced['detached'], $previousStocks, $user);
            DB::commit();

            return $synced;
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function resetStocks($detachedProducts, $previousStocks, $user)
    {
        foreach ($detachedProducts as $productId) {
            StockService::resetProductStock(
                productId: $productId,
                userId: $user->id,
                qty: -$previousStocks[$productId],
                note: 'Product deleted'
            );
        }

    }

    /**
     * @param  User  $user
     * @return Collection|Product[]
     */
    public function createNewProducts($newProducts, $user)
    {
        return collect($newProducts)->map(function ($data, $i) use ($user) {
            $photo = CustomHelper::uploadImage(request()->file("new_products.$i.photo"), Directory::PRODUCTS);

            $product = Product::create([
                'name' => $data['name'],
                'user_id' => $user->id,
                'category_id' => $data['category_id'],
                'base_unit_id' => $data['base_unit_id'],
                'photo' => $photo,
            ]);

            $product->max_stock = $data['max_stock'];

            return $product;
        });

    }
}
