<?php

namespace App\Services\Operacion;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketLogoService
{
    public function store(UploadedFile $file): string
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            return $file->store('tickets/personalizacion', 'public');
        }

        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (!$image) {
            return $file->store('tickets/personalizacion', 'public');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        $path = 'tickets/personalizacion/' . Str::uuid() . '.jpg';
        Storage::disk('public')->makeDirectory(dirname($path));
        imagejpeg($canvas, Storage::disk('public')->path($path), 92);

        imagedestroy($image);
        imagedestroy($canvas);

        return $path;
    }
}
