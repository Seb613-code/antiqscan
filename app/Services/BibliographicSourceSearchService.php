<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BibliographicSourceSearchService
{
    public function search(Book $book): int
    {
        $book->loadMissing(['fields']);

        $validated = $book->fields
            ->filter(fn ($field) => $field->is_validated && filled($field->value))
            ->keyBy('field_key');

        $title = trim((string) ($validated->get('title')->value ?? ''));
        $author = trim((string) ($validated->get('author')->value ?? ''));
        $date = trim((string) ($validated->get('publication_date')->value ?? ''));
        $publisher = trim((string) ($validated->get('publisher')->value ?? ''));

        if (blank($title)) {
            throw new RuntimeException('Recherche impossible : valide au moins le titre avant de chercher des sources.');
        }

        $provider = config('services.antiqscan_sources.provider', 'mock');
        $candidates = match ($provider) {
            'openlibrary' => $this->searchOpenLibrary($title, $author, $date, $publisher),
            default => $this->mockCandidates($title, $author, $date, $publisher),
        };

        $created = 0;
        foreach ($candidates as $candidate) {
            $exists = $book->sources()
                ->where('source_type', $candidate['source_type'])
                ->where('url', $candidate['url'])
                ->exists();

            if ($exists) {
                continue;
            }

            $book->sources()->create($candidate + ['user_approved' => false]);
            $created++;
        }

        return $created;
    }

    private function mockCandidates(string $title, string $author, string $date, string $publisher): array
    {
        $parts = array_filter([$author, $title, $publisher, $date]);

        return [[
            'source_type' => 'mock',
            'title' => 'Source candidate — '.$title,
            'url' => 'https://example.invalid/antiqscan/mock/'.sha1(implode('|', $parts)),
            'citation' => implode(', ', $parts),
            'notes' => 'Source candidate de test, à valider humainement avant export.',
        ]];
    }

    private function searchOpenLibrary(string $title, string $author, string $date, string $publisher): array
    {
        $docs = [];
        $queries = [
            array_filter(['title' => $title, 'author' => $author ?: null, 'publisher' => $publisher ?: null]),
            array_filter(['title' => $title, 'author' => $author ?: null]),
            ['q' => trim($title.' '.$author)],
            ['title' => $title],
        ];

        foreach ($queries as $query) {
            $response = Http::acceptJson()
                ->timeout(20)
                ->get('https://openlibrary.org/search.json', $query + ['limit' => 5]);

            if ($response->failed()) {
                throw new RuntimeException('Erreur OpenLibrary HTTP '.$response->status());
            }

            $docs = $response->json('docs', []);
            if ($docs !== []) {
                break;
            }
        }

        return collect($docs)
            ->take(5)
            ->map(function (array $doc) use ($date) {
                $key = $doc['key'] ?? null;
                $workUrl = $key ? 'https://openlibrary.org'.$key : null;
                $docTitle = $doc['title'] ?? null;
                $docAuthor = implode(', ', array_slice($doc['author_name'] ?? [], 0, 3));
                $docDate = $doc['first_publish_year'] ?? null;
                $publishers = implode(', ', array_slice($doc['publisher'] ?? [], 0, 2));

                return [
                    'source_type' => 'openlibrary',
                    'title' => $docTitle,
                    'url' => $workUrl,
                    'citation' => trim(implode(' — ', array_filter([$docAuthor, $docTitle, $publishers, $docDate]))),
                    'notes' => trim('Candidat OpenLibrary. Date validée recherchée : '.$date),
                ];
            })
            ->filter(fn (array $candidate) => filled($candidate['title']) && filled($candidate['url']))
            ->values()
            ->all();
    }
}
