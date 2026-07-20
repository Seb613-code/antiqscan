<x-layout>
    <section class="mx-auto max-w-md section-card">
        <p class="meta-label">Créer un compte</p>
        <h1 class="mt-2 text-2xl font-semibold">Ouvrir votre bibliothèque</h1>
        <form method="post" action="{{ route('register.store') }}" class="mt-6 space-y-4">
            @csrf
            <input class="form-input" name="name" required placeholder="Nom" value="{{ old('name') }}">
            <input class="form-input" name="email" type="email" required placeholder="E-mail" value="{{ old('email') }}">
            <input class="form-input" name="password" type="password" required placeholder="Mot de passe (12 caractères minimum)">
            <input class="form-input" name="password_confirmation" type="password" required placeholder="Confirmation du mot de passe">
            @if($errors->any())<p class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
            <button class="button-primary w-full">Créer mon compte</button>
        </form>
        <p class="mt-4 text-sm">Déjà inscrit ? <a class="underline" href="{{ route('login') }}">Se connecter</a></p>
    </section>
</x-layout>
