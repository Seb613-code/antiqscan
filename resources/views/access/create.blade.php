<x-layout>
    <section class="mx-auto max-w-md section-card">
        <p class="meta-label">Accès privé</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-stone-900">Ouvrir AntiQScan</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Cette bibliothèque contient des images et notices de travail. Saisissez le mot de passe d’accès.</p>

        <form method="post" action="{{ route('access.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm text-stone-700">
                <span class="mb-2 block font-medium">Mot de passe</span>
                <input name="password" type="password" required autofocus autocomplete="current-password" class="form-input w-full">
            </label>
            @error('password')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            <button type="submit" class="button-primary w-full">Accéder à la bibliothèque</button>
        </form>
    </section>
</x-layout>
