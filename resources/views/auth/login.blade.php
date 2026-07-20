<x-layout>
    <section class="mx-auto max-w-md section-card">
        <p class="meta-label">Connexion</p>
        <h1 class="mt-2 text-2xl font-semibold">Retrouver votre bibliothèque</h1>
        <form method="post" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <input class="form-input" name="email" type="email" required placeholder="E-mail" value="{{ old('email') }}">
            <input class="form-input" name="password" type="password" required placeholder="Mot de passe">
            @if($errors->any())<p class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
            <button class="button-primary w-full">Se connecter</button>
        </form>
        <p class="mt-4 text-sm">Pas encore de compte ? <a class="underline" href="{{ route('register.create') }}">Créer un compte</a></p>
    </section>
</x-layout>
