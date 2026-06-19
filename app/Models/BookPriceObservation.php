<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookPriceObservation extends Model
{
    protected $fillable = [
        'book_id', 'book_source_id', 'amount', 'currency', 'condition_note',
        'observed_at', 'notes', 'included_in_estimate',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'observed_at' => 'date',
            'included_in_estimate' => 'boolean',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(BookSource::class, 'book_source_id');
    }
}
