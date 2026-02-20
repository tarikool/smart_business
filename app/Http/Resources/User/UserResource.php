<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'identifier' => $this->email ?: $this->phone_number,
            'user_type' => $this->user_type,
            'country_id' => $this->country_id,
            'total_customers' => $this->customers()->count(),
            'total_income' => 0,
            'total_expense' => 0,
            'user_profile' => new ProfileResource($this->userProfile),
        ];
    }
}
