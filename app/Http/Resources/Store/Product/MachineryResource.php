<?php

namespace App\Http\Resources\Store\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MachineryResource extends JsonResource
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
        ];
    }
}
