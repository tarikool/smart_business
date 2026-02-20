<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait HasTranslations
{
    public function __call($method, $parameters)
    {
        $colName = Str::of($method)->after('get')->snake();

        return parent::__call($method, $parameters);
    }
}
