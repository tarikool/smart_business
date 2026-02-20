<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionSummeryResource extends JsonResource
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
            'txn_type' => $this->txn_type,
            'txn_date' => $this->txn_date->format('Y-m-d H:i:s'),
            'net_total' => $this->net_total,
            'due' => $this->due,
            'is_due' => $this->is_due,
            'customer_name' => $this->getCustomerName(),
        ];

    }

    public function getCustomerName()
    {
        if ($this->contact?->is_default) {
            return 'Walk-In Customer';
        }

        return $this->contact?->name;
    }
}
