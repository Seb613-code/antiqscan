<x-layout>
    <a href="{{ route('books.index') }}" class="text-sm text-stone-600">← Retour aux dossiers</a>

    <header class="mt-4 mb-6 rounded border bg-white p-5">
        <p class="text-sm uppercase tracking-wide text-stone-500">Dossier #{{ $book->id }}</p>
        <h1 class="mt-1 text-2xl font-semibold">Construire une fiche catalogue</h1>
        <p class="mt-2 text-stone-600">Statut : {{ $book->status }}</p>
    </header>

    <section class="grid gap-3 md:grid-cols-4">
        <div class="rounded border bg-white p-4">
            <p class="font-medium">1. Image importée</p>
            <p class="mt-1 text-sm text-stone-600">Vérifie que la page de titre est le bon fichier.</p>
        </div>
        <div class="rounded border bg-white p-4">
            <p class="font-medium">2. Extraction visible</p>
            <p class="mt-1 text-sm text-stone-600">Le mock remplit seulement ce qui est lisible sur l’image.</p>
        </div>
        <div class="rounded border bg-white p-4">
            <p class="font-medium">3. Validation humaine</p>
            <p class="mt-1 text-sm text-stone-600">Corrige les champs puis coche « validé ».</p>
        </div>
        <div class="rounded border bg-white p-4">
            <p class="font-medium">4. Fiche finale</p>
            <p class="mt-1 text-sm text-stone-600">La fiche finale affiche seulement les champs validés.</p>
        </div>
    </section>

    <section class="mt-6 rounded border-2 border-amber-500 bg-amber-50 p-5">
        <h2 class="text-xl font-semibold">Étape 2 — lancer l’extraction</h2>
        <p class="mt-2 text-sm text-stone-700">Clique ici pour remplir automatiquement les champs visibles. Pour l’instant, le fournisseur actif est le mock Mouchot.</p>
        <form method="post" action="{{ route('books.extract.mock', $book) }}" class="mt-4">
            @csrf
            <button type="submit" class="w-full rounded border border-black px-4 py-3 text-lg font-semibold text-black md:w-auto">Lancer l’extraction</button>
        </form>
    </section>

    <section class="mt-6 rounded border bg-white p-5">
        <h2 class="text-lg font-medium">Image</h2>
        <ul class="mt-3 list-disc pl-5 text-sm text-stone-700">
            @foreach($book->images as $image)
                <li>{{ $image->role }} — {{ $image->original_path }}</li>
            @endforeach
        </ul>
    </section>

    <section class="mt-6 rounded border bg-white p-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-medium">Champs catalogue</h2>
                <p class="mt-1 text-sm text-stone-600">Remplis ou corrige, coche « validé », puis enregistre. Rien de non validé ne sort dans la fiche.</p>
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

            <div class="flex justify-end">
                <button class="rounded bg-stone-900 px-5 py-2 text-white">Enregistrer les validations</button>
            </div>
        </form>
    </section>

    <section class="mt-6 grid gap-6 md:grid-cols-2">
        <div class="rounded border bg-white p-5">
            <h2 class="text-lg font-medium">Sources</h2>
            <p class="mt-2 text-sm text-stone-600">Aucune source externe pour l’instant. Cette zone servira aux références bibliographiques.</p>
        </div>

        <div class="rounded border bg-white p-5">
            <h2 class="text-lg font-medium">Prix / estimation</h2>
            <p class="mt-2 text-sm text-stone-600">Aucune estimation pour l’instant. Cette zone restera séparée de la description bibliographique.</p>
        </div>
    </section>
</x-layout>
