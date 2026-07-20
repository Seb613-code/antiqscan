<x-layout>
<section class="mx-auto max-w-md section-card">
<p class="meta-label">Vérification requise</p><h1 class="mt-2 text-2xl font-semibold">Confirmez votre adresse e-mail</h1>
<p class="mt-3 text-sm text-stone-600">Un lien de confirmation vient d’être envoyé à votre adresse. Cliquez dessus pour accéder à votre bibliothèque.</p>
@if(session('status'))<p class="mt-3 text-sm text-emerald-700">{{ session('status') }}</p>@endif
<form method="post" action="{{ route('verification.send') }}" class="mt-5">@csrf<button class="button-secondary">Renvoyer le lien</button></form>
</section>
</x-layout>
