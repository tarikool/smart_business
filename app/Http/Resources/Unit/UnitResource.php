<?php

namespace App\Http\Resources\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
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
            'symbol' => $this->symbol,
            // To prevent lazy load
            'unit_options' => $this->whenLoaded('unitOptions', function () {
                return UnitOptionResource::collection($this->unitOptions);
            }),
        ];
    }
}
