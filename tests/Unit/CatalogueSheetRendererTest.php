<?php

namespace Tests\Unit;

use App\Services\CatalogueSheetRenderer;
use PHPUnit\Framework\TestCase;

class CatalogueSheetRendererTest extends TestCase
{
    public function test_short_citation_has_no_trailing_comma(): void
    {
        $fields = [
            (object) ['field_key' => 'author', 'label' => 'Auteur', 'value' => 'Ribeiro', 'origin' => 'user_validated', 'is_validated' => true],
            (object) ['field_key' => 'title', 'label' => 'Titre', 'value' => "Histoire de l'isle de Ceylan", 'origin' => 'user_validated', 'is_validated' => true],
            (object) ['field_key' => 'publication_date', 'label' => 'Date', 'value' => '1701', 'origin' => 'user_validated', 'is_validated' => true],
        ];

        $citation = (new CatalogueSheetRenderer)->renderCitation($fields);

        $this->assertSame("RIBEIRO, Histoire de l'isle de Ceylan, 1701", $citation);
        $this->assertNotSame(',', substr($citation, -1));
    }
}
