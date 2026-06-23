<x-layout>
    <a href="{{ route('books.index') }}" class="text-sm text-stone-600">← Retour</a>
    <header class="mt-4 mb-6">
        <h1 class="text-2xl font-semibold">Dossier #{{ $book->id }}</h1>
        <p class="text-stone-600">Statut : {{ $book->status }}</p>
    </header>

    <section class="rounded border bg-white p-5">
        <h2 class="text-lg font-medium">Image</h2>
        <ul class="mt-3 list-disc pl-5 text-sm text-stone-700">
            @foreach($book->images as $image)
                <li>{{ $image->role }} — {{ $image->original_path }}</li>
            @endforeach
        </ul>
    </section>

    <section class="mt-6 rounded border bg-white p-5">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium">Champs catalogue</h2>
            <div class="space-x-3 text-sm">
                <a class="underline" href="{{ route('books.catalogue', $book) }}">Voir fiche catalogue</a>
                <a class="underline" href="{{ route('books.export.markdown', $book) }}">Export Markdown</a>
            </div>
        </div>

        @php
            $visibleFields = $book->fields->where('origin', 'ai_visible');
            $manualFields = $book->fields->where('origin', 'user_manual');
        @endphp

        <form method="post" action="{{ route('books.fields.update', $book) }}" class="mt-4 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <h3 class="font-medium">Champs visibles sur page de titre</h3>
                <div class="mt-3 space-y-3">
                    @foreach($visibleFields as $field)
                        @include('books.partials.field-row', ['field' => $field])
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="font-medium">Champs physiques manuels</h3>
                <div class="mt-3 space-y-3">
                    @foreach($manualFields as $field)
                        @include('books.partials.field-row', ['field' => $field])
                    @endforeach
                </div>
            </div>

            <button class="rounded bg-stone-900 px-4 py-2 text-white">Enregistrer</button>
        </form>
    </section>

    <section class="mt-6 rounded border bg-white p-5">
        <h2 class="text-lg font-medium">Sources</h2>
        <p class="mt-2 text-sm text-stone-600">Aucune source externe pour l’instant.</p>
    </section>

    <section class="mt-6 rounded border bg-white p-5">
        <h2 class="text-lg font-medium">Prix / estimation</h2>
        <p class="mt-2 text-sm text-stone-600">Aucune estimation pour l’instant.</p>
    </section>
</x-layout>
