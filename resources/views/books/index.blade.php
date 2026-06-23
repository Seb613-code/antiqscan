<x-layout>
    <header class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight">AntiQScan</h1>
        <p class="mt-2 text-stone-600">Fiches de catalogue sobres à partir d’une page de titre.</p>
    </header>

    <section class="rounded border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-medium">Page de titre</h2>
        <p class="mt-1 text-sm text-stone-600">V1 : une seule image. L’extraction IA restera limitée à ce qui est visible.</p>
        <form method="post" action="{{ route('books.store') }}" enctype="multipart/form-data" class="mt-4 flex gap-3">
            @csrf
            <input name="title_page" type="file" accept="image/*" required class="block flex-1 rounded border p-2">
            <button class="rounded bg-stone-900 px-4 py-2 text-white">Créer</button>
        </form>
        @error('title_page')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
    </section>

    <section class="mt-8">
        <h2 class="text-xl font-medium">Dossiers</h2>
        <div class="mt-3 divide-y rounded border bg-white">
            @forelse($books as $book)
                <div class="p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="font-medium">#{{ $book->id }} — {{ $book->working_title ?? 'Titre à confirmer' }}</p>
                            <p class="text-sm text-stone-500">Statut : {{ $book->status }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('books.show', $book) }}" class="rounded border px-3 py-2 text-sm">Ouvrir le dossier</a>
                            <form method="post" action="{{ route('books.extract.mock', $book) }}">
                                @csrf
                                <button type="submit" class="rounded border border-black px-3 py-2 text-sm font-semibold text-black">Lancer l’extraction</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="p-4 text-stone-600">Aucun dossier pour l’instant.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $books->links() }}</div>
    </section>
</x-layout>
