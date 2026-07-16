<x-layout>
    <section class="section-card-muted">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl space-y-3">
                <p class="meta-label">Parcours</p>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">Votre bibliothèque</h1>
                <p class="text-sm leading-6 text-stone-700">Importez une page de titre, relisez les champs extraits puis consultez une fiche documentaire proprement validée.</p>
                <div class="flex flex-wrap gap-2 text-sm font-medium text-stone-700">
                    <span class="status-badge">1. Importer</span>
                    <span class="status-badge">2. Relire</span>
                    <span class="status-badge">3. Consulter</span>
                </div>
            </div>
            <div class="rounded-[1.25rem] border border-stone-200 bg-white/80 px-4 py-3 text-sm text-stone-600">
                Les fiches validées seules exposent le catalogue final et ses sources approuvées.
            </div>
        </div>
    </section>

    <div class="mt-8 grid gap-8 xl:grid-cols-[minmax(0,24rem)_minmax(0,1fr)]">
        <section class="section-card xl:sticky xl:top-6 xl:self-start">
            <p class="meta-label">Création d'une nouvelle fiche</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight">Importer une page de titre</h2>
            <p class="mt-2 text-sm leading-6 text-stone-600">Chargez une photo JPG de la page de titre. Limite : moins de 10 Mo. L’aperçu reste local à votre navigateur.</p>

            @if(session('created_book_id'))
                <div class="mt-4 rounded-[1rem] border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm font-semibold text-emerald-950">Fiche créée.</p>
                    <p class="mt-2 text-sm text-emerald-900">Les champs extraits doivent maintenant être relus avant consultation du catalogue.</p>
                    <a href="{{ route('books.show', session('created_book_id')) }}" class="button-primary mt-3">Relire la fiche</a>
                </div>
            @endif

            @if(session('status'))
                <p class="mt-4 rounded-[1rem] border border-stone-200 bg-stone-50 p-3 text-sm text-stone-700">{{ session('status') }}</p>
            @endif
            @if(session('error'))
                <p class="mt-4 rounded-[1rem] border border-red-200 bg-red-50 p-3 text-sm text-red-900">{{ session('error') }}</p>
            @endif

            <form method="post" action="{{ route('books.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4" id="new-book-form">
                @csrf
                <label class="block text-sm text-stone-700">
                    <span class="mb-2 block font-medium">Image JPG de la page de titre</span>
                    <input name="title_page" type="file" accept=".jpg,.jpeg,image/jpeg" required class="form-file" id="title-page-input">
                </label>
                <div id="title-page-preview-wrap" class="hidden rounded-[1rem] border border-dashed border-stone-300 bg-stone-50 p-4">
                    <p class="mb-2 text-sm font-medium text-stone-700">Miniature de la page de titre</p>
                    <img id="title-page-preview" alt="Miniature de la page de titre" width="100" height="100" style="width: 100px; height: 100px; max-width: 100px; max-height: 100px;" class="block rounded border object-cover">
                </div>
                <button id="create-book-button" class="button-primary w-full disabled:cursor-not-allowed disabled:opacity-40" disabled>Créer la fiche</button>
            </form>
            @error('title_page')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </section>

        <section class="section-card">
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="meta-label">Bibliothèque</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight">Fiches en cours</h2>
                </div>
                <p class="text-sm text-stone-600">La prochaine action utile est affichée directement sur chaque fiche.</p>
            </div>

            <div class="library-card-grid mt-6">
                @forelse($books as $book)
                    @php
                        $image = $book->titlePageImage();
                        $isValidated = filled($book->user_validated_at);
                        $statusLabel = $isValidated ? 'Validée' : 'À relire';
                        $primaryLabel = $isValidated ? 'Voir le catalogue' : 'Relire la fiche';
                        $primaryRoute = $isValidated ? route('books.catalogue', $book) : route('books.show', $book);
                    @endphp
                    <article class="section-card-muted flex h-full flex-col gap-4">
                        <div class="flex items-start gap-4">
                            @if($image)
                                <img src="{{ route('book-images.show', $image) }}" alt="Page de titre fiche {{ $book->id }}" width="50" height="50" style="width: 50px; height: 50px; max-width: 50px; max-height: 50px;" class="block rounded border object-cover">
                            @else
                                <div class="h-[50px] w-[50px] rounded border bg-stone-100"></div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="meta-label">Fiche #{{ $book->id }}</span>
                                    <span class="status-badge {{ $isValidated ? 'status-badge--validated' : 'status-badge--review' }}">{{ $statusLabel }}</span>
                                </div>
                                <p class="mt-2 text-base font-semibold leading-6 text-stone-900">{{ $book->displayCitation() }}</p>
                                <p class="mt-2 text-sm text-stone-600">Dernière modification le {{ $book->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>

                        <div class="mt-auto flex flex-wrap gap-2">
                            <a href="{{ $primaryRoute }}" class="button-primary">{{ $primaryLabel }}</a>
                            @if($isValidated)
                                <a href="{{ route('books.show', $book) }}" class="button-secondary">Modifier la fiche</a>
                            @endif
                            <form method="post" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Supprimer cette fiche ?');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="button-danger">Supprimer la fiche</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[1rem] border border-dashed border-stone-300 bg-stone-50 px-4 py-8 text-sm text-stone-600">Aucune fiche pour l’instant.</div>
                @endforelse
            </div>

            <div class="mt-6">{{ $books->links() }}</div>
        </section>
    </div>

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
