<x-layout>
    <a href="{{ route('books.index') }}" class="text-sm text-stone-600">← Retour à la bibliothèque</a>

    @if(session('status'))
        <div class="mt-4 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-900">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="mt-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-900">{{ session('error') }}</div>
    @endif

    <section class="mt-6 rounded border bg-white p-5">
        <h1 class="text-xl font-semibold">Fiche #{{ $book->id }}</h1>
        <h2 class="mt-4 text-lg font-medium">Image</h2>
        <div class="mt-3 space-y-4">
            @forelse($book->images as $image)
                <figure>
                    <img src="{{ route('book-images.show', $image) }}" alt="Page de titre fiche {{ $book->id }}" height="300" style="height: 300px; max-height: 300px; width: auto; max-width: 100%;" class="block rounded border object-contain">
                    <figcaption class="mt-2 text-sm text-stone-600">{{ $image->role }}</figcaption>
                </figure>
            @empty
                <p class="text-sm text-stone-600">Aucune image associée.</p>
            @endforelse
        </div>
    </section>

    @if($showAiRunDebug)
        @php
            $lastAiRun = $book->aiRuns->first();
        @endphp
        <section class="mt-6 rounded border border-purple-300 bg-purple-50 p-5 text-sm text-purple-950">
            <h2 class="text-lg font-semibold">Diagnostic IA — phase de test</h2>
            @if($lastAiRun)
                <p class="mt-2 font-mono">run #{{ $lastAiRun->id }} — {{ $lastAiRun->status }} — {{ $lastAiRun->provider ?? '—' }} / {{ $lastAiRun->model ?? '—' }}</p>
                <p class="mt-1 font-mono">{{ $lastAiRun->input_tokens ?? 0 }} in / {{ $lastAiRun->output_tokens ?? 0 }} out</p>
                @if($lastAiRun->validation_errors)
                    <pre class="mt-3 whitespace-pre-wrap rounded border border-red-200 bg-red-50 p-3 text-xs text-red-900">{{ json_encode($lastAiRun->validation_errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            @else
                <p class="mt-2 rounded bg-white p-3">Aucun run IA enregistré pour ce dossier.</p>
            @endif
        </section>
    @endif

    <section class="mt-6 rounded border bg-white p-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-medium">Champs catalogue</h2>
                <p class="mt-1 text-sm text-stone-600">Remplis ou corrige, coche « validé », puis enregistre. Le bouton rapide valide seulement les champs remplis.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a class="rounded border px-3 py-2" href="{{ route('books.catalogue', $book) }}">Voir fiche catalogue</a>
                <a class="rounded border px-3 py-2" href="{{ route('books.export.markdown', $book) }}">Export Markdown</a>
                <a class="rounded border px-3 py-2" href="{{ route('books.export.json', $book) }}">Export JSON</a>
                <a class="rounded border px-3 py-2" href="{{ route('books.export.csv', $book) }}">Export CSV</a>
            </div>
        </div>

        @php
            $visibleFields = $book->fields->where('origin', 'ai_visible')->sortBy('id');
            $manualFields = $book->fields->where('origin', 'user_manual')->sortBy('id');
        @endphp

        <form method="post" action="{{ route('books.fields.update', $book) }}" class="mt-5 space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded border border-amber-200 bg-amber-50 p-4">
                <h3 class="font-medium">Champs visibles sur page de titre</h3>
                <p class="mt-1 text-sm text-stone-600">Exemples : auteur, titre, lieu, éditeur, date. À valider après contrôle visuel.</p>
                <div class="mt-3 space-y-3">
                    @foreach($visibleFields as $field)
                        @include('books.partials.field-row', ['field' => $field])
                    @endforeach
                </div>
            </div>

            <div class="rounded border border-blue-200 bg-blue-50 p-4">
                <h3 class="font-medium">Champs physiques manuels</h3>
                <p class="mt-1 text-sm text-stone-600">À saisir à la main : format, dimensions, pagination, reliure, état.</p>
                <div class="mt-3 space-y-3">
                    @foreach($manualFields as $field)
                        @include('books.partials.field-row', ['field' => $field])
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-2 md:flex-row md:justify-end">
                <button type="submit" name="action" value="validate_filled" class="rounded border border-black px-5 py-2 font-semibold text-black">Valider les champs remplis</button>
                <button type="submit" name="action" value="save" class="rounded bg-stone-900 px-5 py-2 text-white">Enregistrer les validations</button>
            </div>
        </form>
    </section>

    <section class="mt-6 grid gap-6 md:grid-cols-2">
        <div class="rounded border bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-medium">Sources</h2>
                    <p class="mt-2 text-sm text-stone-600">Cherche des sources candidates depuis les champs validés. Rien n’est ajouté à la fiche finale sans validation humaine.</p>
                </div>
                <form method="post" action="{{ route('books.sources.search', $book) }}">
                    @csrf
                    <button type="submit" class="rounded border border-black px-3 py-2 text-sm font-semibold">Chercher des sources</button>
                </form>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($book->sources as $source)
                    <div class="rounded border {{ $source->user_approved ? 'border-green-200 bg-green-50' : 'border-stone-200 bg-stone-50' }} p-3 text-sm">
                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="font-medium">{{ $source->title ?: 'Source sans titre' }}</p>
                                @if($source->citation)<p class="mt-1 text-stone-700">{{ $source->citation }}</p>@endif
                                @if($source->url)<a class="mt-1 block break-all text-blue-700 underline" href="{{ $source->url }}" target="_blank" rel="noopener">{{ $source->url }}</a>@endif
                                @if($source->notes)<p class="mt-1 text-xs text-stone-500">{{ $source->notes }}</p>@endif
                            </div>
                            @if($source->user_approved)
                                <span class="rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">validée</span>
                            @else
                                <form method="post" action="{{ route('books.sources.approve', [$book, $source]) }}">
                                    @csrf
                                    <button type="submit" class="rounded bg-stone-900 px-3 py-2 text-xs font-semibold text-white">Valider cette source</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="rounded bg-stone-50 p-3 text-sm text-stone-600">Aucune source candidate pour l’instant.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded border bg-white p-5">
            <h2 class="text-lg font-medium">Prix / estimation</h2>
            <p class="mt-2 text-sm text-stone-600">Aucune estimation pour l’instant. Cette zone restera séparée de la description bibliographique.</p>
        </div>
    </section>
</x-layout>
