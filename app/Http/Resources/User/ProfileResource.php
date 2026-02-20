<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'business_name' => $this->business_name,
            'business_address' => $this->business_address,
            'business_type_id' => $this->business_type_id,
            'business_type_name' => $this->businessType->name,
            'user_photo' => getPhotoUrl($this->user_photo),
            'cover_photo' => getPhotoUrl($this->cover_photo),
            'coordinates' => $this->coordinates,
        ];
    }
}
