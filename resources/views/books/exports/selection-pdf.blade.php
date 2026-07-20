<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Export AntiQScan</title>
    <style>
        @page { margin: 20mm; }
        body { color: #292524; font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.45; }
        h1 { font-size: 18pt; margin: 0 0 4mm; }
        h2 { border-bottom: 1px solid #d6d3d1; font-size: 13pt; margin: 0 0 4mm; padding-bottom: 2mm; }
        h3 { font-size: 10pt; margin: 5mm 0 2mm; }
        .sheet { page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        .meta { color: #57534e; font-size: 8pt; margin-bottom: 7mm; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #e7e5e4; padding: 2mm; text-align: left; vertical-align: top; }
        th { width: 34%; color: #57534e; font-weight: normal; }
        ul { margin: 0; padding-left: 5mm; }
    </style>
</head>
<body>
    @foreach ($books as $catalogueBook)
        <article class="sheet">
            <p class="meta">AntiQScan — fiche catalogue exportée</p>
            <h1>{{ $catalogueBook['citation'] ?: 'Fiche à compléter' }}</h1>

            @forelse ($catalogueBook['sections'] as $section)
                <h2>{{ $section['heading'] }}</h2>
                <table>
                    @foreach ($section['fields'] as $field)
                        <tr><th>{{ $field['label'] }}</th><td>{{ $field['value'] }}</td></tr>
                    @endforeach
                </table>
            @empty
                <p>Aucun champ validé pour cette fiche.</p>
            @endforelse

            @if ($catalogueBook['sources'] !== [])
                <h3>Sources validées</h3>
                <ul>
                    @foreach ($catalogueBook['sources'] as $source)
                        <li>{{ $source['citation'] ?: $source['title'] }}@if ($source['url']) — {{ $source['url'] }}@endif</li>
                    @endforeach
                </ul>
            @endif
        </article>
    @endforeach
</body>
</html>
