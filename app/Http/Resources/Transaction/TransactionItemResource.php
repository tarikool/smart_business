<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'category_name' => $this->product?->category?->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'unit_option_id' => $this->unit_option_id,
            'unit_option_name' => $this->unitOption?->name,
        ];
    }
}
