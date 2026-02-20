<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Decimal implements CastsAttributes
{
    public function __construct(public $precision = 2) {}

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (strpos($value, '.') !== false) {
            return (float) rtrim(rtrim($value, '0'), '.');
        }

        return $value ? (float) $value : $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (strpos($value, '.') !== false) {
            return round($value, $this->precision);
        }

        return $value;
    }
}
