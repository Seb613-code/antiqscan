<x-layout>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('books.show', $book) }}" class="text-sm text-stone-600">← Modifier la fiche</a>
        <a href="{{ route('books.index') }}" class="text-sm text-stone-600">Retour à la bibliothèque</a>
    </div>

    <article class="mt-4 rounded border bg-white p-6 font-serif">
        <p class="text-sm uppercase tracking-wide text-stone-500">Fiche catalogue</p>
        <h1 class="mt-2 text-2xl leading-tight">{{ $citation ?: 'Fiche en cours de rédaction' }}</h1>

        @if($sections === [] && blank($book->catalogue_note) && $sources === [])
            <p class="mt-6 text-stone-600">Fiche vide : enregistrez au moins un champ avant de la consulter.</p>
        @endif

        @foreach($sections as $section)
            <section class="mt-8 border-t border-stone-200 pt-5">
                <h2 class="text-base font-semibold">{{ $section['heading'] }}</h2>
                <dl class="mt-3 space-y-2 text-[1rem] leading-7">
                    @foreach($section['fields'] as $field)
                        <div>
                            <dt class="inline font-semibold">{{ $field['label'] }}.</dt>
                            <dd class="inline"> {{ $field['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach

        @if(filled($book->catalogue_note))
            <section class="mt-8 border-t border-stone-200 pt-5">
                <h2 class="text-base font-semibold">Notice</h2>
                <div class="mt-3 whitespace-pre-line leading-7">{{ $book->catalogue_note }}</div>
            </section>
        @endif

        @if($sources !== [])
            <section class="mt-8 border-t border-stone-200 pt-5">
                <h2 class="text-base font-semibold">Sources</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 leading-7">
                    @foreach($sources as $source)
                        <li>
                            {{ $source['citation'] ?: $source['title'] }}
                            @if($source['url'])
                                — <a class="text-blue-700 underline" href="{{ $source['url'] }}" target="_blank" rel="noopener">Consulter la source</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
</x-layout>
