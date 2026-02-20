<?php

namespace App\Helpers;

use App\Enums\Directory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CustomHelper
{
    public static function formatDate($date, $format = 'Y-m-d')
    {
        return \Carbon\Carbon::parse($date)->format($format);
    }

    /**
     * @param  UploadedFile  $newImage
     * @param  string|Directory  $directory
     * @return mixed
     */
    public static function uploadImage($newImage, $directory = 'uploads', $oldImage = null)
    {
        if (! $newImage || ! $newImage->isValid()) {
            return null;
        }

        if ($oldImage) {
            Storage::delete($oldImage);
        }

        $originalName = $newImage->getClientOriginalName();
        $extension = $newImage->getClientOriginalExtension();
        $fileName = time().'_'.pathinfo($originalName, PATHINFO_FILENAME).'.'.$extension;

        if ($directory instanceof Directory) {
            $directory = $directory->value;
        }

        return $newImage->storeAs($directory, $fileName);
    }

    public static function deleteFile($path): bool
    {
        if ($path) {
            return Storage::delete($path);
        }

        return false;
    }
}
