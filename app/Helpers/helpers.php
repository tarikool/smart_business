<?php

use App\Models\BaseUnit;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Storage;

if (! function_exists('enumLabels')) {
    function enumLabels(array $values)
    {
        //        return $enumClass = (new \ReflectionClass($values[0]))->getName();
        //
        //        return get_class($values[0]);
    }
}

if (! function_exists('getPhotoUrl')) {
    function getPhotoUrl(?string $photoPath): string
    {
        if ($photoPath) {
            if (filter_var($photoPath, FILTER_VALIDATE_URL)) {
                return $photoPath;
            }

            if (Storage::exists($photoPath)) {
                return asset('storage/'.$photoPath);
            }
        }

        return asset('images/default.png');
    }
}

if (! function_exists('getEnumsForDB')) {
    function getEnumsForDB(array $values)
    {
        $arr = array_map(fn ($str) => "'$str'", $values);

        return implode(',', $arr);
    }
}

if (! function_exists('getDiscount')) {
    function getDiscount(bool $isFixed, $total, $discountValue)
    {
        if ($isFixed) {
            return $discountValue;
        }

        return ($total * $discountValue) / 100;
    }
}

if (! function_exists('getMachineryCatgory')) {
    function getMachineryCatgory($user = null)
    {
        $user = $user ?: auth()->user();

        return ProductCategory::where(
            [
                'business_type_id' => $user->userProfile->business_type_id,
                'is_machinery' => true,
            ]
        )->firstOrFail();
    }
}

if (! function_exists('getMachineryBaseUnit')) {
    function getMachineryBaseUnit()
    {
        return BaseUnit::where('name', 'Piece')
            ->with('defaultUnit')
            ->firstOrFail();
    }
}

if (! function_exists('recentAvg')) {
    function recentAvg($lastAvg, $counter, $unitPrice, $quantity)
    {
        $newTotal = ($lastAvg * $counter) + ($unitPrice * $quantity);

        return $newTotal / ($counter + $quantity);
    }
}
