@extends('layouts.guest')
@section('content')
<h1 class="h4 fw-bold mb-1">Choisir un nouveau mot de passe</h1><p class="text-secondary mb-4">Créez un mot de passe sécurisé pour votre compte.</p>
<form method="POST" action="{{ route('password.store') }}">@csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <div class="mb-3"><label class="form-label fw-semibold" for="email">Adresse e-mail</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required></div>
    <div class="mb-3"><label class="form-label fw-semibold" for="password">Nouveau mot de passe</label><input class="form-control" id="password" name="password" type="password" required autocomplete="new-password"></div>
    <div class="mb-4"><label class="form-label fw-semibold" for="password_confirmation">Confirmer le mot de passe</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div>
    <button class="btn btn-primary w-100">Réinitialiser le mot de passe</button>
</form>
@endsection
