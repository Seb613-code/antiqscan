<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRun extends Model
{
    protected $fillable = [
        'book_id', 'run_type', 'provider', 'model', 'image_hash', 'cache_key',
        'cached_from_ai_run_id', 'input_tokens', 'output_tokens', 'estimated_cost',
        'status', 'prompt', 'response', 'validation_errors', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:6',
            'prompt' => 'array',
            'response' => 'array',
            'validation_errors' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
