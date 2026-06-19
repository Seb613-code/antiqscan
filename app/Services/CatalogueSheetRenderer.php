<?php

namespace App\Services;

class CatalogueSheetRenderer
{
    public function renderMarkdown(iterable $fields): string
    {
        $lines = [];

        foreach ($fields as $field) {
            if (! $field->is_validated || blank($field->value)) {
                continue;
            }

            $lines[] = sprintf('**%s** — %s', $field->label, trim($field->value));
        }

        return implode("\n\n", $lines);
    }
}
