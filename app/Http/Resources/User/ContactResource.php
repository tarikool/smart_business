<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
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
            'user_id' => $this->whenHas('user_id'), /* When showing transaction list */
            'name' => $this->name,
            'phone_number' => $this->whenHas('phone_number'),
            'contact_type' => $this->whenHas('contact_type'),
            'address' => $this->whenHas('address'),
            'is_default' => $this->is_default,
        ];
    }
}
