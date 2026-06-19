<x-layout>
    <a href="{{ route('books.index') }}" class="text-sm text-stone-600">← Retour</a>
    <header class="mt-4 mb-6">
        <h1 class="text-2xl font-semibold">Dossier #{{ $book->id }}</h1>
        <p class="text-stone-600">Statut : {{ $book->status }}</p>
    </header>

    <section class="rounded border bg-white p-5">
        <h2 class="text-lg font-medium">Images</h2>
        <ul class="mt-3 list-disc pl-5 text-sm text-stone-700">
            @foreach($book->images as $image)
                <li>{{ $image->role }} — {{ $image->original_path }}</li>
            @endforeach
        </ul>
    </section>

    <section class="mt-6 rounded border bg-white p-5">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium">Champs extraits / manuels</h2>
            <a class="text-sm underline" href="{{ route('books.catalogue', $book) }}">Voir fiche catalogue</a>
        </div>
        <form method="post" action="{{ route('books.fields.update', $book) }}" class="mt-4 space-y-3">
            @csrf
            @method('PUT')
            @forelse($book->fields as $field)
                <div class="grid gap-2 rounded border p-3 md:grid-cols-12">
                    <input name="fields[{{ $field->id }}][label]" value="{{ $field->label }}" class="rounded border p-2 md:col-span-3">
                    <textarea name="fields[{{ $field->id }}][value]" class="rounded border p-2 md:col-span-6">{{ $field->value }}</textarea>
                    <label class="text-sm md:col-span-2"><input type="checkbox" name="fields[{{ $field->id }}][is_validated]" value="1" @checked($field->is_validated)> validé</label>
                    <p class="text-xs text-stone-500 md:col-span-1">{{ $field->origin }} {{ $field->confidence }}</p>
                </div>
            @empty
                <p class="text-stone-600">Aucun champ extrait. L’étape IA sera branchée après configuration fournisseur.</p>
            @endforelse
            <button class="rounded bg-stone-900 px-4 py-2 text-white">Enregistrer</button>
        </form>
    </section>
</x-layout>
