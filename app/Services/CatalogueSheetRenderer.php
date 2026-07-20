<?php

namespace App\Services;

class CatalogueSheetRenderer
{
    public function renderArray(iterable $fields): array
    {
        $rows = [];

        foreach ($fields as $field) {
            if (! $field->is_validated || blank($field->value)) {
                continue;
            }

            $rows[] = [
                'key' => $field->field_key,
                'label' => $field->label,
                'value' => trim($field->value),
                'origin' => $field->origin,
            ];
        }

        return $rows;
    }

    public function renderCitation(iterable $fields): ?string
    {
        $byKey = collect($this->renderArray($fields))->keyBy('key');
        $author = trim((string) data_get($byKey->get('author'), 'value'));
        $title = trim((string) data_get($byKey->get('title'), 'value'));
        $date = trim((string) data_get($byKey->get('publication_date'), 'value'));

        $parts = array_filter([$author !== '' ? mb_strtoupper($author) : null, $title ?: null, $date ?: null]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    public function renderCatalogueSections(iterable $fields): array
    {
        $byKey = collect($this->renderArray($fields))->keyBy('key');

        $sections = [
            'Publication' => ['author', 'title', 'subtitle', 'edition_statement', 'place', 'publisher', 'publisher_address', 'publication_date', 'illustration_statement', 'visible_notes'],
            'Collation' => ['format', 'dimensions', 'pagination'],
            'Reliure, état et particularités' => ['binding', 'condition', 'copy_notes'],
        ];

        return collect($sections)
            ->map(fn (array $keys, string $heading) => [
                'heading' => $heading,
                'fields' => collect($keys)->map(fn (string $key) => $byKey->get($key))->filter()->values()->all(),
            ])
            ->filter(fn (array $section) => $section['fields'] !== [])
            ->values()
            ->all();
    }

    public function renderSourcesArray(iterable $sources): array
    {
        $rows = [];

        foreach ($sources as $source) {
            if (! $source->user_approved) {
                continue;
            }

            $rows[] = [
                'source_type' => $source->source_type,
                'title' => $source->title,
                'url' => $source->url,
                'citation' => $source->citation,
            ];
        }

        return $rows;
    }

    public function renderMarkdown(iterable $fields, iterable $sources = []): string
    {
        $lines = [];

        foreach ($this->renderArray($fields) as $field) {
            $lines[] = sprintf('**%s** — %s', $field['label'], $field['value']);
        }

        $sourceRows = $this->renderSourcesArray($sources);
        if ($sourceRows !== []) {
            $lines[] = '### Sources validées';
            foreach ($sourceRows as $source) {
                $lines[] = sprintf('- %s%s', $source['citation'] ?: $source['title'], $source['url'] ? ' — '.$source['url'] : '');
            }
        }

        return implode("\n\n", $lines);
    }

    public function renderCsv(iterable $fields): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['key', 'label', 'value', 'origin']);

        foreach ($this->renderArray($fields) as $field) {
            fputcsv($handle, [$field['key'], $field['label'], $field['value'], $field['origin']]);
        }

        rewind($handle);

        return stream_get_contents($handle);
    }
}
