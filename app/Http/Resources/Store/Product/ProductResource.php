<?php

namespace App\Http\Resources\Store\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category->name,
            'photo' => getPhotoUrl($this->photo),
            'base_unit_id' => $this->base_unit_id,
            'base_unit_name' => $this->baseUnit->name,
            'base_unit_symbol' => $this->baseUnit->symbol,
            'max_stock' => $this->productUser?->max_stock,
            'current_stock' => $this->productUser?->current_stock,
            'allow_production' => $this->product_user_exists ? $this->productUser->allow_production : $this->allow_production,
            'is_in_store' => $this->product_user_exists,
            'is_user_created' => $this->user_id == auth()->id(),
        ];
    }
}
