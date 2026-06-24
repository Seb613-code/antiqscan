<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageIntakeService
{
    private const MAX_DIMENSION = 1400;
    private const JPEG_QUALITY = 78;

    public function storeTitlePage(UploadedFile $file): array
    {
        $originalPath = $file->store('books/originals');
        $optimizedPath = $this->optimizeStoredImage($originalPath);

        return [
            'original_path' => $originalPath,
            'optimized_path' => $optimizedPath,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'width' => $this->dimensions($originalPath)[0] ?? null,
            'height' => $this->dimensions($originalPath)[1] ?? null,
        ];
    }

    private function dimensions(string $path): array
    {
        $absolutePath = Storage::path($path);

        if (! is_file($absolutePath)) {
            return [null, null];
        }

        $size = @getimagesize($absolutePath);

        return $size ? [$size[0], $size[1]] : [null, null];
    }

    public function optimizeStoredImage(string $originalPath): ?string
    {
        $absolutePath = Storage::path($originalPath);
        $source = $this->openImage($absolutePath);

        if (! $source) {
            return null;
        }

        [$width, $height] = $this->dimensions($originalPath);
        if (! $width || ! $height) {
            imagedestroy($source);

            return null;
        }

        $source = $this->applyExifOrientation($source, $absolutePath);
        if ($this->swapsDimensionsForExifOrientation($absolutePath)) {
            [$width, $height] = [$height, $width];
        }

        $scale = min(1, self::MAX_DIMENSION / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $optimizedPath = 'books/optimized/'.pathinfo($originalPath, PATHINFO_FILENAME).'.jpg';
        Storage::disk('local')->makeDirectory('books/optimized');
        $written = imagejpeg($target, Storage::path($optimizedPath), self::JPEG_QUALITY);

        imagedestroy($source);
        imagedestroy($target);

        return $written ? $optimizedPath : null;
    }

    private function openImage(string $absolutePath): \GdImage|false
    {
        $info = @getimagesize($absolutePath);
        $mime = $info['mime'] ?? null;

        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => @imagecreatefromwebp($absolutePath),
            default => false,
        };
    }

    private function applyExifOrientation(\GdImage $image, string $absolutePath): \GdImage
    {
        $orientation = $this->exifOrientation($absolutePath);

        $oriented = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, 270, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($oriented !== $image) {
            imagedestroy($image);
        }

        return $oriented ?: $image;
    }

    private function swapsDimensionsForExifOrientation(string $absolutePath): bool
    {
        return in_array($this->exifOrientation($absolutePath), [5, 6, 7, 8], true);
    }

    private function exifOrientation(string $absolutePath): ?int
    {
        if (! function_exists('exif_read_data')) {
            return null;
        }

        $info = @getimagesize($absolutePath);
        if (($info['mime'] ?? null) !== 'image/jpeg') {
            return null;
        }

        $exif = @exif_read_data($absolutePath);

        return isset($exif['Orientation']) ? (int) $exif['Orientation'] : null;
    }
}
