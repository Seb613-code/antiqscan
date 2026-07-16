<x-layout>
    @php($isValidated = filled($book->user_validated_at))
    @php($catalogueNote = $isValidated ? $book->catalogue_note : null)

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('books.show', $book) }}" class="text-sm text-stone-600">← Modifier la fiche</a>
        <a href="{{ route('books.index') }}" class="text-sm text-stone-600">Retour à la bibliothèque</a>
    </div>

    <article class="document-sheet mt-4">
        <div class="flex flex-col gap-3 border-b border-stone-200 pb-5 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="meta-label">{{ $isValidated ? 'Voir le catalogue' : 'Aperçu de catalogue' }}</p>
                <p class="mt-2 text-sm uppercase tracking-[0.22em] text-stone-500">{{ $isValidated ? 'Fiche catalogue validée' : 'Brouillon non validé' }}</p>
                <h1 class="mt-3 text-3xl leading-tight text-stone-900">{{ $citation ?: 'Fiche en cours de rédaction' }}</h1>
            </div>
            <div class="text-sm text-stone-600">
                <p>{{ $isValidated ? 'Document de consultation' : 'Document d’aperçu' }}</p>
                @if(filled($book->user_validated_at))
                    <p>Validation du {{ $book->user_validated_at->format('d/m/Y H:i') }}</p>
                @else
                    <p>Validation utilisateur en attente.</p>
                @endif
            </div>
        </div>

        @if($sections === [] && blank($catalogueNote) && $sources === [])
            <p class="mt-6 text-stone-600">Fiche vide : enregistrez au moins un champ avant de la consulter.</p>
        @endif

        @foreach($sections as $section)
            <section class="document-rule mt-8">
                <h2 class="text-base font-semibold uppercase tracking-[0.16em] text-stone-600">{{ $section['heading'] }}</h2>
                <dl class="mt-4 space-y-3 text-[1rem] leading-7">
                    @foreach($section['fields'] as $field)
                        <div>
                            <dt class="inline font-semibold text-stone-900">{{ $field['label'] }}.</dt>
                            <dd class="inline text-stone-800"> {{ $field['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach

        @if(filled($catalogueNote))
            <section class="document-rule mt-8">
                <h2 class="text-base font-semibold uppercase tracking-[0.16em] text-stone-600">Notice</h2>
                <div class="mt-4 whitespace-pre-line leading-7 text-stone-800">{{ $catalogueNote }}</div>
            </section>
        @endif

        @if($sources !== [])
            <section class="document-rule mt-8">
                <h2 class="text-base font-semibold uppercase tracking-[0.16em] text-stone-600">Sources</h2>
                <ul class="mt-4 list-disc space-y-2 pl-5 leading-7 text-stone-800">
                    @foreach($sources as $source)
                        <li>
                            {{ $source['citation'] ?: $source['title'] }}
                            @if($source['url'])
                                — <a class="text-emerald-800 underline" href="{{ $source['url'] }}" target="_blank" rel="noopener">Consulter la source</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
</x-layout>
