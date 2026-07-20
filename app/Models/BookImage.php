<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookImage extends Model
{
    protected $fillable = [
        'book_id', 'role', 'sort_order', 'original_path', 'optimized_path',
        'mime_type', 'size_bytes', 'width', 'height',
    ];

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->whereKey($value)->whereHas('book', fn ($query) => $query->where('user_id', auth()->id()))->first();
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
