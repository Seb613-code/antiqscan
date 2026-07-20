<x-layout>
    <section class="section-card-muted">
        <p class="meta-label">Votre espace</p>
        <h1 class="mt-2 text-3xl font-semibold">Votre bibliothèque</h1>
    </section>

    <div class="mt-8 grid gap-8 xl:grid-cols-[24rem_1fr]">
        <section class="section-card xl:sticky xl:top-6 xl:self-start">
            <p class="meta-label">Création d'une nouvelle fiche</p>
            <h2 class="mt-2 text-2xl font-semibold">Importer une page de titre</h2>

            @if (session('created_book_id'))
                <p class="mt-3 text-sm">
                    Fiche créée. Les champs extraits doivent maintenant être relus avant consultation du catalogue.
                    <a class="underline" href="{{ route('books.show', session('created_book_id')) }}">Relire la fiche</a>
                </p>
            @endif

            <form method="post" action="{{ route('books.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4" id="new-book-form">
                @csrf
                <input name="title_page" type="file" accept=".jpg,.jpeg,image/jpeg" required class="form-file" id="title-page-input">
                <button id="create-book-button" class="button-primary w-full">Créer la fiche</button>
            </form>
        </section>

        <div class="space-y-8">
            <section class="section-card">
                <p class="meta-label">À traiter</p>
                <h2 class="mt-2 text-2xl font-semibold">Fiches à relire</h2>

                <div class="mt-4 space-y-3">
                    @forelse ($reviewBooks as $book)
                        <div class="flex items-center justify-between rounded border p-3">
                            <span>
                                <span class="status-badge status-badge--review">À relire</span>
                                {{ $book->displayCitation() }}
                            </span>
                            <a class="button-secondary" href="{{ route('books.show', $book) }}">Relire la fiche</a>
                        </div>
                    @empty
                        <p class="text-sm text-stone-600">Aucune fiche à relire.</p>
                    @endforelse
                </div>
            </section>

            @if ($books->whereNotNull('user_validated_at')->isNotEmpty())
                <section class="section-card">
                    <p class="meta-label">Catalogue</p>
                    <h2 class="mt-2 text-2xl font-semibold">Fiches validées</h2>

                    <div class="mt-4 space-y-3">
                        @foreach ($books->whereNotNull('user_validated_at') as $book)
                            <div class="flex items-center justify-between rounded border p-3">
                                <span><span class="status-badge">Validée</span> {{ $book->displayCitation() }}</span>
                                <a class="button-secondary" href="{{ route('books.catalogue', $book) }}">Voir le catalogue</a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="section-card">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="meta-label">Bibliothèque</p>
                        <h2 class="mt-2 text-2xl font-semibold">Votre bibliothèque</h2>
                    </div>
                    <label class="text-sm">
                        Rechercher une fiche
                        <input id="library-search" class="form-input mt-1" placeholder="Auteur ou titre">
                    </label>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm" id="library-table">
                        <thead class="border-b">
                            <tr>
                                <th><button data-sort="author">Auteur</button></th>
                                <th><button data-sort="title">Titre</button></th>
                                <th><button data-sort="date">Date</button></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($books as $book)
                                @php($fields = $book->fields->keyBy('field_key'))
                                <tr class="border-b" data-author="{{ strtolower($fields->get('author')->value ?? '') }}" data-title="{{ strtolower($fields->get('title')->value ?? '') }}" data-date="{{ $fields->get('publication_date')->value ?? '' }}">
                                    <td class="py-3">{{ $fields->get('author')->value ?? '—' }}</td>
                                    <td>{{ $fields->get('title')->value ?? $book->displayCitation() }}</td>
                                    <td>{{ $fields->get('publication_date')->value ?? '—' }}</td>
                                    <td class="whitespace-nowrap">
                                        <a class="button-secondary" href="{{ route('books.show', $book) }}">Voir la fiche</a>
                                        <form class="inline" method="post" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Supprimer cette fiche ?')">
                                            @csrf
                                            @method('delete')
                                            <button class="button-danger">Supprimer la fiche</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-stone-600">Aucune fiche pour l’instant.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $books->links() }}</div>
            </section>
        </div>
    </div>

    <script>
        const search = document.getElementById('library-search');
        const table = document.getElementById('library-table');

        search?.addEventListener('input', () => {
            const query = search.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach((row) => {
                row.hidden = !`${row.dataset.author} ${row.dataset.title}`.includes(query);
            });
        });

        document.querySelectorAll('[data-sort]').forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.dataset.sort;
                const rows = [...table.tBodies[0].rows].sort((first, second) => (first.dataset[key] || '').localeCompare(second.dataset[key] || '', 'fr'));
                rows.forEach((row) => table.tBodies[0].append(row));
            });
        });
    </script>
</x-layout>
