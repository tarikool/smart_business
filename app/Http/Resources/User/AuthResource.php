<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function __construct($resource, public $token)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'identifier' => $this->email ?: $this->phone_number,
                'country_id' => $this->country_id,
                'status' => $this->status,
                'is_business_setup_complete' => (bool) $this->userProfile?->is_business_setup_complete,
                'is_store_setup_complete' => (bool) $this->userProfile?->is_store_setup_complete,
            ],
        ];
    }
}
