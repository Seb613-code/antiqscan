<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AntiQScan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="page-shell">
    <header class="page-header">
        <div class="page-header__inner">
            <div class="page-brand">
                <p class="page-brand__eyebrow">Bibliothèque documentaire</p>
                <a href="{{ route('books.index') }}" class="page-brand__title">AntiQScan</a>
                <p class="page-brand__subtitle">Importez une page de titre, relisez les champs utiles et consultez une fiche catalogue validée.</p>
            </div>
            @auth
                <div class="flex items-center gap-3 text-sm text-stone-600">
                    <span>Connecté : <strong>{{ auth()->user()->name }}</strong></span>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="button-secondary" type="submit">Se déconnecter</button>
                    </form>
                </div>
            @else
                <p class="text-sm text-stone-600">Interface de consultation claire, sans jargon technique.</p>
            @endauth
        </div>
    </header>

    <main class="page-container">
        {{ $slot }}
    </main>
</body>
</html>
