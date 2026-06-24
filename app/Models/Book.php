<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'working_title',
        'catalogue_note',
        'user_validated_at',
    ];

    protected function casts(): array
    {
        return [
            'user_validated_at' => 'datetime',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(BookImage::class)->orderBy('sort_order');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(BookField::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(BookSource::class);
    }

    public function priceObservations(): HasMany
    {
        return $this->hasMany(BookPriceObservation::class);
    }

    public function aiRuns(): HasMany
    {
        return $this->hasMany(AiRun::class);
    }

    public function titlePageImage(): ?BookImage
    {
        return $this->images->firstWhere('role', 'title_page') ?? $this->images->first();
    }

    public function displayCitation(): string
    {
        $fields = $this->fields->keyBy('field_key');
        $author = trim((string) ($fields->get('author')->value ?? 'Auteur à confirmer'));
        $title = trim((string) ($fields->get('title')->value ?? ($this->working_title ?? 'Titre à confirmer')));
        $date = trim((string) ($fields->get('publication_date')->value ?? 'Date à confirmer'));

        return mb_strtoupper($author).', '.$title.', '.$date.',';
    }
}
