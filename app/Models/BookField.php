<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookField extends Model
{
    protected $fillable = [
        'book_id', 'field_key', 'label', 'value', 'origin', 'confidence',
        'is_validated', 'is_editable', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:3',
            'is_validated' => 'boolean',
            'is_editable' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
