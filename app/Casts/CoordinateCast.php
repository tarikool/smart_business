<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CoordinateCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (! $value) {
            return null;
        }

        if (json_validate($value)) {
            $coordinate = json_decode($value, true);

            return ['lat' => $coordinate[1], 'long' => $coordinate[0]];
        }

        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_array($value)) {
            ['lat' => $lat, 'long' => $long] = $value;

            return DB::raw("ST_GeomFromText('POINT($long $lat)', 4326)");
        }

        return $value;
    }
}
