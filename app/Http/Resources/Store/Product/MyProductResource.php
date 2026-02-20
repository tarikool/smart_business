<?php

namespace App\Http\Resources\Store\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyProductResource extends JsonResource
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
            'photo' => getPhotoUrl($this->photo),
            'base_unit_id' => $this->base_unit_id,
            'base_unit_name' => $this->baseUnit->name,
            'base_unit_symbol' => $this->baseUnit->symbol,
            'allow_production' => $this->productUser->allow_production,
            'is_machinery' => $this->category->is_machinery,
            'max_stock' => $this->productUser?->max_stock,
            'current_stock' => $this->productUser?->current_stock,
            'avg_buy' => $this->recentPrice?->avg_buy,
            'avg_sell' => $this->recentPrice?->avg_sell,
            'recent_buy' => $this->recentPrice?->recent_buy,
            'recent_sell' => $this->recentPrice?->recent_sell,
            'recent_buy_date' => $this->recentPrice?->buy_date?->format('Y-m-d'),
        ];
    }
}
