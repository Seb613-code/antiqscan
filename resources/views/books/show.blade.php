<x-layout>
    @php
        $visibleFieldKeys = ['author', 'title', 'subtitle', 'place', 'publisher', 'publisher_address', 'publication_date', 'illustration_statement', 'edition_statement', 'visible_notes'];
        $manualFieldKeys = ['format', 'dimensions', 'pagination', 'binding', 'condition', 'copy_notes'];
        $visibleFields = $book->fields->whereIn('field_key', $visibleFieldKeys)->sortBy('id');
        $manualFields = $book->fields->whereIn('field_key', $manualFieldKeys)->sortBy('id');
        $isValidated = filled($book->user_validated_at);
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('books.index') }}" class="text-sm text-stone-600">← Retour à la bibliothèque</a>
        <a href="{{ route('books.catalogue', $book) }}" class="button-secondary">{{ $isValidated ? 'Voir le catalogue' : 'Aperçu de catalogue' }}</a>
    </div>

    @if(session('status'))
        <div class="mt-4 rounded-[1rem] border border-green-200 bg-green-50 p-3 text-sm text-green-900">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="mt-4 rounded-[1rem] border border-red-200 bg-red-50 p-3 text-sm text-red-900">{{ session('error') }}</div>
    @endif

    <section class="section-card mt-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <p class="meta-label">Fiche #{{ $book->id }}</p>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ $book->displayCitation() }}</h1>
                <div class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                    <span class="status-badge {{ $isValidated ? 'status-badge--validated' : 'status-badge--review' }}">{{ $isValidated ? 'Validée' : 'À relire' }}</span>
                    <span>Dernière modification le {{ $book->updated_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a class="button-secondary" href="{{ route('books.export.csv', $book) }}">Export CSV</a>
                <button type="submit" form="book-review-form" class="button-primary">Enregistrer les modifications</button>
            </div>
        </div>
    </section>

    @if($showAiRunDebug)
        @php($lastAiRun = $book->aiRuns->first())
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

    <div class="mt-6 grid gap-6 xl:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="space-y-6">
            <section class="section-card-muted">
                <h2 class="text-lg font-medium">Image</h2>
                <div class="mt-3 space-y-4">
                    @forelse($book->images as $image)
                        <figure class="title-page-zoom" data-zoom-scale="2">
                            <img src="{{ route('book-images.show', $image) }}" alt="Page de titre fiche {{ $book->id }}" height="300" style="height: 300px; max-height: 300px; width: auto; max-width: 100%;" class="title-page-zoom__image block rounded border object-contain">
                            <img src="{{ route('book-images.show', $image) }}" alt="" aria-hidden="true" class="title-page-zoom__magnified rounded border object-contain">
                            <figcaption class="title-page-zoom__hint">Survolez l’image pour l’agrandir.</figcaption>
                            <figcaption class="mt-2 text-sm text-stone-600">{{ $image->role }}</figcaption>
                        </figure>
                    @empty
                        <p class="text-sm text-stone-600">Aucune image associée.</p>
                    @endforelse
                </div>
            </section>

            <section class="section-card-muted">
                <p class="meta-label">État de travail</p>
                <div class="mt-3 space-y-3 text-sm text-stone-700">
                    <p>Relisez chaque champ avant la consultation finale.</p>
                    <p>Les sources restent candidates tant qu’elles ne sont pas validées.</p>
                    @if(filled($book->user_validated_at))
                        <p>Validation enregistrée le {{ $book->user_validated_at->format('d/m/Y H:i') }}.</p>
                    @endif
                </div>
            </section>
        </aside>

        <div class="space-y-6">
            <form method="post" action="{{ route('books.fields.update', $book) }}" class="space-y-6" id="book-review-form">
                @csrf
                @method('PUT')

                <section class="section-card">
                    <div class="space-y-2">
                        <p class="meta-label">Relecture</p>
                        <h2 class="text-2xl font-semibold tracking-tight">Informations bibliographiques repérées</h2>
                        <p class="text-sm text-stone-600">Auteur, titre, lieu, éditeur, date et autres mentions visibles sur la page de titre.</p>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach($visibleFields as $field)
                            @include('books.partials.field-row', ['field' => $field])
                        @endforeach
                    </div>
                </section>

                <section class="section-card">
                    <div class="space-y-2">
                        <p class="meta-label">Notice</p>
                        <h2 class="text-2xl font-semibold tracking-tight">Notice de catalogue</h2>
                        <p class="text-sm text-stone-600">Texte de synthèse à relire et corriger si nécessaire.</p>
                    </div>
                    <label class="mt-4 block text-sm">
                        <span class="mb-2 block font-medium text-stone-800">Notice</span>
                        <textarea name="catalogue_note" rows="7" class="form-textarea">{{ old('catalogue_note', $book->catalogue_note) }}</textarea>
                    </label>
                </section>

                <section class="section-card">
                    <div class="space-y-2">
                        <p class="meta-label">Description</p>
                        <h2 class="text-2xl font-semibold tracking-tight">Description matérielle</h2>
                        <p class="text-sm text-stone-600">Format, dimensions, collation, reliure, état et particularités d’exemplaire.</p>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach($manualFields as $field)
                            @include('books.partials.field-row', ['field' => $field])
                        @endforeach
                    </div>
                </section>
            </form>

            <section class="section-card">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="meta-label">Contrôle</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight">Sources à vérifier</h2>
                        <p class="mt-2 text-sm text-stone-600">Ces sources n’entrent dans la fiche finale qu’après validation humaine.</p>
                    </div>
                    <form method="post" action="{{ route('books.sources.search', $book) }}">
                        @csrf
                        <button type="submit" class="button-secondary">Chercher des sources</button>
                    </form>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse($book->sources as $source)
                        <article class="rounded-[1rem] border {{ $source->user_approved ? 'border-green-200 bg-green-50' : 'border-stone-200 bg-stone-50' }} p-4 text-sm">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div class="space-y-1">
                                    <p class="font-semibold text-stone-900">{{ $source->title ?: 'Source sans titre' }}</p>
                                    @if($source->citation)<p class="text-stone-700">{{ $source->citation }}</p>@endif
                                    @if($source->url)<a class="block break-all text-emerald-800 underline" href="{{ $source->url }}" target="_blank" rel="noopener">{{ $source->url }}</a>@endif
                                    @if($source->notes)<p class="text-xs text-stone-500">{{ $source->notes }}</p>@endif
                                </div>
                                @if($source->user_approved)
                                    <span class="status-badge status-badge--validated">Validée</span>
                                @else
                                    <form method="post" action="{{ route('books.sources.approve', [$book, $source]) }}">
                                        @csrf
                                        <button type="submit" class="button-primary">Valider cette source</button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="rounded-[1rem] border border-dashed border-stone-300 bg-stone-50 p-4 text-sm text-stone-600">Aucune source candidate pour l’instant.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-layout>
