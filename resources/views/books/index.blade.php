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

        <section class="section-card min-w-0">
            <p class="meta-label">À traiter</p>
            <h2 class="mt-2 text-2xl font-semibold">Fiches à relire</h2>

            <div class="mt-4 flex gap-3 overflow-x-auto pb-3">
                @forelse ($reviewBooks as $book)
                    <div class="flex min-w-[22rem] items-center justify-between gap-4 rounded border p-3">
                        <span>
                            <span class="status-badge status-badge--review">À relire</span>
                            {{ $book->displayCitation() }}
                        </span>
                        <a class="button-secondary shrink-0" href="{{ route('books.show', $book) }}">Relire la fiche</a>
                    </div>
                @empty
                    <p class="text-sm text-stone-600">Aucune fiche à relire.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="section-card mt-8 w-full">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="meta-label">Bibliothèque</p>
                <h2 class="mt-2 text-2xl font-semibold">Votre bibliothèque</h2>
            </div>
            <div class="flex flex-wrap items-end gap-3">
                <label class="text-sm">
                    Rechercher une fiche
                    <input id="library-search" class="form-input mt-1" placeholder="Auteur ou titre">
                </label>
                <button id="filters-toggle" class="button-secondary" type="button" aria-expanded="false" aria-controls="library-filters">Filtres</button>
                <button id="export-toggle" class="button-secondary" type="button" aria-expanded="false" aria-controls="library-export">Export</button>
            </div>
        </div>

        <fieldset id="library-filters" class="mt-4 hidden rounded border p-4">
            <legend class="px-1 text-sm font-medium">Filtres de recherche</legend>
            <div class="grid gap-4 sm:grid-cols-3">
                <label class="text-sm">
                    Statut
                    <select id="library-status" class="form-input mt-1">
                        <option value="">Toutes les fiches</option>
                        <option value="review">À relire</option>
                        <option value="validated">Validées</option>
                    </select>
                </label>
                <div>
                    <p class="text-sm">Date de publication</p>
                    <div class="mt-1 flex gap-2">
                        <label class="sr-only" for="library-date-from">Du</label>
                        <input id="library-date-from" class="form-input w-full" type="number" min="0" placeholder="Du">
                        <label class="sr-only" for="library-date-to">Au</label>
                        <input id="library-date-to" class="form-input w-full" type="number" min="0" placeholder="Au">
                    </div>
                </div>
                <div class="flex items-end">
                    <button id="filters-reset" class="button-secondary" type="button">Réinitialiser les filtres</button>
                </div>
            </div>
        </fieldset>

        <div id="library-export" class="mt-4 hidden rounded border p-4">
            <p class="text-sm text-stone-600">Sélectionnez une ou plusieurs fiches dans le tableau, puis choisissez le format.</p>
            <div class="mt-3 flex flex-wrap gap-3">
                <button class="button-secondary" type="submit" form="bulk-export-form" formaction="{{ route('books.export.bulk.csv') }}">Export CSV</button>
                <button class="button-primary" type="submit" form="bulk-export-form" formaction="{{ route('books.export.bulk.pdf') }}">Export PDF</button>
            </div>
            @error('book_ids')<p class="mt-3 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <form id="bulk-export-form" method="post">
            @csrf
        </form>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm" id="library-table">
                <thead class="border-b">
                    <tr>
                        <th><input id="select-all-books" type="checkbox" aria-label="Sélectionner toutes les fiches visibles"></th>
                        <th>
                            Auteur
                            <button class="ml-1" data-sort="author" data-direction="asc" type="button" aria-label="Trier par auteur, croissant">↑</button>
                            <button data-sort="author" data-direction="desc" type="button" aria-label="Trier par auteur, décroissant">↓</button>
                        </th>
                        <th>
                            Titre
                            <button class="ml-1" data-sort="title" data-direction="asc" type="button" aria-label="Trier par titre, croissant">↑</button>
                            <button data-sort="title" data-direction="desc" type="button" aria-label="Trier par titre, décroissant">↓</button>
                        </th>
                        <th>
                            Date
                            <button class="ml-1" data-sort="date" data-direction="asc" type="button" aria-label="Trier par date, croissant">↑</button>
                            <button data-sort="date" data-direction="desc" type="button" aria-label="Trier par date, décroissant">↓</button>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($books as $book)
                        @php($fields = $book->fields->keyBy('field_key'))
                        @php($publicationDate = $fields->get('publication_date')->value ?? '')
                        <tr class="border-b" data-author="{{ strtolower($fields->get('author')->value ?? '') }}" data-title="{{ strtolower($fields->get('title')->value ?? '') }}" data-date="{{ $publicationDate }}" data-status="{{ $book->user_validated_at ? 'validated' : 'review' }}">
                            <td class="py-3"><input class="book-export-checkbox" type="checkbox" name="book_ids[]" value="{{ $book->id }}" form="bulk-export-form" aria-label="Sélectionner {{ $book->displayCitation() }}"></td>
                            <td>{{ $fields->get('author')->value ?? '—' }}</td>
                            <td>{{ $fields->get('title')->value ?? $book->displayCitation() }}</td>
                            <td>{{ $publicationDate ?: '—' }}</td>
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
                        <tr><td colspan="5" class="py-6 text-stone-600">Aucune fiche pour l’instant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $books->links() }}</div>
    </section>

    <script>
        const search = document.getElementById('library-search');
        const table = document.getElementById('library-table');
        const filtersToggle = document.getElementById('filters-toggle');
        const filtersPanel = document.getElementById('library-filters');
        const statusFilter = document.getElementById('library-status');
        const dateFrom = document.getElementById('library-date-from');
        const dateTo = document.getElementById('library-date-to');
        const resetFilters = document.getElementById('filters-reset');
        const exportToggle = document.getElementById('export-toggle');
        const exportPanel = document.getElementById('library-export');
        const selectAllBooks = document.getElementById('select-all-books');

        const filterRows = () => {
            const query = search.value.toLowerCase().trim();
            const status = statusFilter.value;
            const minimumDate = Number(dateFrom.value) || null;
            const maximumDate = Number(dateTo.value) || null;

            table.querySelectorAll('tbody tr[data-author]').forEach((row) => {
                const publicationYear = Number.parseInt(row.dataset.date, 10);
                const matchesSearch = `${row.dataset.author} ${row.dataset.title}`.includes(query);
                const matchesStatus = !status || row.dataset.status === status;
                const matchesMinimumDate = !minimumDate || (!Number.isNaN(publicationYear) && publicationYear >= minimumDate);
                const matchesMaximumDate = !maximumDate || (!Number.isNaN(publicationYear) && publicationYear <= maximumDate);
                row.hidden = !(matchesSearch && matchesStatus && matchesMinimumDate && matchesMaximumDate);
            });
        };

        filtersToggle.addEventListener('click', () => {
            const isOpen = filtersToggle.getAttribute('aria-expanded') === 'true';
            filtersToggle.setAttribute('aria-expanded', String(!isOpen));
            filtersPanel.classList.toggle('hidden', isOpen);
        });

        exportToggle.addEventListener('click', () => {
            const isOpen = exportToggle.getAttribute('aria-expanded') === 'true';
            exportToggle.setAttribute('aria-expanded', String(!isOpen));
            exportPanel.classList.toggle('hidden', isOpen);
        });

        selectAllBooks.addEventListener('change', () => {
            table.querySelectorAll('tbody tr[data-author]').forEach((row) => {
                if (!row.hidden) {
                    row.querySelector('.book-export-checkbox').checked = selectAllBooks.checked;
                }
            });
        });

        [search, statusFilter, dateFrom, dateTo].forEach((input) => input.addEventListener('input', filterRows));
        statusFilter.addEventListener('change', filterRows);

        resetFilters.addEventListener('click', () => {
            search.value = '';
            statusFilter.value = '';
            dateFrom.value = '';
            dateTo.value = '';
            filterRows();
        });

        document.querySelectorAll('[data-sort]').forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.dataset.sort;
                const direction = button.dataset.direction === 'desc' ? -1 : 1;
                const rows = [...table.tBodies[0].rows].sort((first, second) => {
                    const firstValue = first.dataset[key] || '';
                    const secondValue = second.dataset[key] || '';
                    if (key === 'date') {
                        return (Number.parseInt(firstValue, 10) - Number.parseInt(secondValue, 10)) * direction;
                    }

                    return firstValue.localeCompare(secondValue, 'fr') * direction;
                });
                rows.forEach((row) => table.tBodies[0].append(row));
            });
        });
    </script>
</x-layout>
