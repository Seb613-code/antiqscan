<x-layout>
    <a href="{{ route('books.show', $book) }}" class="text-sm text-stone-600">← Dossier</a>
    <article class="mt-4 rounded border bg-white p-6">
        <h1 class="text-2xl font-serif">Fiche catalogue</h1>
        <p class="mt-2 text-sm text-stone-500">Seuls les champs validés sont affichés.</p>
        <div class="prose mt-6 whitespace-pre-line font-serif leading-7">{{ $markdown ?: 'Fiche vide : valider les champs avant export.' }}</div>
    </article>
</x-layout>
