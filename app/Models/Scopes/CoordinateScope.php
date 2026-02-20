<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CoordinateScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $query = 'CASE WHEN coordinates IS NULL THEN NULL
        ELSE json_build_array(ST_X(coordinates), ST_Y(coordinates))
        END AS coordinates';

        $builder->beforeQuery(function (\Illuminate\Database\Query\Builder $builder) use ($query) {
            $builder->selectRaw($query);
        });
    }
}
