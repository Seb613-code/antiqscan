<?php

namespace App\Jobs;

use App\Models\Book;
use App\Models\AiRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExtractVisibleTitlePageFields implements ShouldQueue
{
    use Queueable;

    public function __construct(public Book $book) {}

    public function handle(): void
    {
        AiRun::create([
            'book_id' => $this->book->id,
            'run_type' => 'visible_title_page_extraction',
            'status' => 'pending_configuration',
            'prompt' => [
                'rule' => 'Extract only text and bibliographic facts visible on the supplied title page image. Do not enrich, infer, or praise.',
                'allowed_fields' => ['author', 'title', 'subtitle', 'publisher', 'place', 'date', 'edition_statement', 'visible_notes'],
            ],
        ]);
    }
}
