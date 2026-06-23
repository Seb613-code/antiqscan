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

    public function renderMarkdown(iterable $fields): string
    {
        $lines = [];

        foreach ($this->renderArray($fields) as $field) {
            $lines[] = sprintf('**%s** — %s', $field['label'], $field['value']);
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
