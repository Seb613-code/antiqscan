<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookSource extends Model
{
    protected $fillable = [
        'book_id', 'source_type', 'title', 'url', 'citation', 'notes', 'user_approved',
    ];

    protected function casts(): array
    {
        return ['user_approved' => 'boolean'];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
