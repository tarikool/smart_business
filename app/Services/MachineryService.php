<?php

namespace App\Services;

use App\Enums\Directory;
use App\Helpers\CustomHelper;
use App\Http\Requests\Store\Product\MachineryRequest;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MachineryService
{
    /**
     * @return Collection|mixed
     *
     * @throws \Throwable
     */
    public function addMachines(MachineryRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $machines = collect($request->validated('machines'))->map(function ($data) {
                $data['photo'] = CustomHelper::uploadImage($data['photo'] ?? null, Directory::MACHINERY);
                $product = Product::create($data);
                $product->productUser()->create([
                    'max_stock' => $data['max_stock'],
                    'category_id' => $product->category_id,
                ]);

                return $product;
            });

            return $machines;
        });
    }
}
