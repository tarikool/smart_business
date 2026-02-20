<?php

namespace App\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait EnumTrait
{
    public static function keys()
    {
        return array_column(self::cases(), 'name');
    }

    public static function values()
    {
        return array_column(self::cases(), 'value');
    }

    public static function keyValues()
    {
        return array_column(self::cases(), 'value', 'name');
    }

    public static function getLabels()
    {
        $prefix = 'common.';
        $prefix .= Str::of(class_basename(__CLASS__))->plural()->snake();

        return Arr::map(self::values(), function ($value) use ($prefix) {
            return [
                'label' => __("$prefix.$value"),
                'value' => $value,
            ];
        });
    }

    public function label($locale = null)
    {
        $prefix = 'common.';
        $prefix .= Str::of(class_basename(__CLASS__))->plural()->snake();

        return __("$prefix.$this->value", locale: $locale);
    }
}
