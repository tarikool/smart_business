<?php

namespace App\Http\Resources\Transaction;

use App\Http\Resources\User\ContactResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'user_id' => $this->user_id,
            'contact_id' => $this->contact_id,
            'txn_type' => $this->txn_type,
            'txn_date' => $this->txn_date->format('Y-m-d H:i:s'),
            'is_fixed_discount' => $this->is_fixed_discount,
            'payment_method' => $this->whenLoaded('payments', fn () => $this->payments->value('payment_method')),
            'discount_value' => $this->discount_value,
            'discount_percentage' => $this->discount_percentage,
            'total' => $this->total,
            'net_total' => $this->net_total,
            'due' => $this->due,
            'is_due' => $this->is_due,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'note' => $this->note,
            'contact' => new ContactResource($this->contact),
            'transaction_items' => $this->whenLoaded('transactionItems', fn () => TransactionItemResource::collection($this->transactionItems)),
            'rental_items' => $this->whenLoaded('rentalItems', fn () => RentalItemResource::collection($this->rentalItems)),
            'advisory' => $this->whenLoaded('advisory', fn () => new AdvisoryResource($this->advisory)),
            'payments' => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)),
        ];

    }
}
