<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageIntakeService
{
    public function storeTitlePage(UploadedFile $file): array
    {
        $originalPath = $file->store('books/originals');

        // V1 keeps the original image. Local optimization hook intentionally centralized
        // for later resize/compression without changing controllers or schema.
        return [
            'original_path' => $originalPath,
            'optimized_path' => null,
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
}
