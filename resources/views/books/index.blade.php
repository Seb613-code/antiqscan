<x-layout>
    <header class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight">AntiQScan</h1>
        <p class="mt-2 text-stone-600">Fiches de catalogue sobres à partir d’une page de titre.</p>
    </header>

    <section class="rounded border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-medium">Création d'une nouvelle fiche</h2>
        <p class="mt-1 text-sm text-stone-600">Chargez une photo JPG de la page de titre. Limite : moins de 10 Mo.</p>

        @if(session('created_book_id'))
            <div class="mt-4 rounded border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-sm font-medium text-emerald-900">Fiche créée.</p>
                <a href="{{ route('books.show', session('created_book_id')) }}" class="mt-3 inline-flex rounded bg-stone-900 px-4 py-2 text-sm font-semibold text-white">Voir la fiche</a>
            </div>
        @endif

        @if(session('status'))
            <p class="mt-4 rounded border border-stone-200 bg-stone-50 p-3 text-sm text-stone-700">{{ session('status') }}</p>
        @endif

        <form method="post" action="{{ route('books.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4" id="new-book-form">
            @csrf
            <input name="title_page" type="file" accept=".jpg,.jpeg,image/jpeg" required class="block w-full rounded border p-2" id="title-page-input">
            <div id="title-page-preview-wrap" class="hidden">
                <p class="mb-2 text-sm text-stone-600">Miniature de la page de titre</p>
                <img id="title-page-preview" alt="Miniature de la page de titre" width="100" height="100" style="width: 100px; height: 100px; max-width: 100px; max-height: 100px;" class="block rounded border object-cover">
            </div>
            <button id="create-book-button" class="rounded bg-stone-900 px-4 py-2 text-white disabled:cursor-not-allowed disabled:bg-stone-300" disabled>Créer la fiche</button>
        </form>
        @error('title_page')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
    </section>

    <section class="mt-8 rounded border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-medium">Votre bibliothèque</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-stone-600">
                    <tr>
                        <th class="px-3 py-2 font-medium">N°</th>
                        <th class="px-3 py-2 font-medium">Miniature</th>
                        <th class="px-3 py-2 font-medium">Titre</th>
                        <th class="px-3 py-2 font-medium">Création / modification</th>
                        <th class="px-3 py-2 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                @forelse($books as $book)
                    @php($image = $book->titlePageImage())
                    <tr>
                        <td class="whitespace-nowrap px-3 py-3 font-medium">#{{ $book->id }}</td>
                        <td class="px-3 py-3">
                            @if($image)
                                <img src="{{ route('book-images.show', $image) }}" alt="Page de titre fiche {{ $book->id }}" width="50" height="50" style="width: 50px; height: 50px; max-width: 50px; max-height: 50px;" class="block rounded border object-cover">
                            @else
                                <div class="h-[50px] w-[50px] rounded border bg-stone-100"></div>
                            @endif
                        </td>
                        <td class="px-3 py-3">{{ $book->displayCitation() }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-stone-600">
                            Créée le {{ $book->created_at->format('d/m/Y H:i') }}<br>
                            Modifiée le {{ $book->updated_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('books.show', $book) }}" class="rounded border px-3 py-2 text-sm">Modifier la fiche</a>
                                <form method="post" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Supprimer cette fiche ?');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="rounded border border-red-700 px-3 py-2 text-sm font-semibold text-red-700">Supprimer la fiche</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-4 text-stone-600">Aucune fiche pour l’instant.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $books->links() }}</div>
    </section>

    <script>
        const input = document.getElementById('title-page-input');
        const button = document.getElementById('create-book-button');
        const previewWrap = document.getElementById('title-page-preview-wrap');
        const preview = document.getElementById('title-page-preview');

        input?.addEventListener('change', () => {
            const file = input.files?.[0];
            button.disabled = true;
            previewWrap.classList.add('hidden');

            if (!file || file.type !== 'image/jpeg' || file.size >= 10 * 1024 * 1024) {
                return;
            }

            preview.src = URL.createObjectURL(file);
            previewWrap.classList.remove('hidden');
            button.disabled = false;
        });
    </script>
</x-layout>
