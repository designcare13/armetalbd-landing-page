<?php

namespace App\Http\Controllers;

use Illuminate\Http\UploadedFile;

abstract class Controller
{
    /**
     * Store an uploaded image on the uploads disk and return its relative path.
     */
    protected function storeImage(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, config('landing.image_disk'));
    }
}
