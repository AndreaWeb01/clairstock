@extends('layouts.guest')
@section('content')
<h1 class="h4 fw-bold mb-1">Mot de passe oublié</h1><p class="text-secondary mb-4">Indiquez votre e-mail et nous vous enverrons un lien de réinitialisation.</p>
<form method="POST" action="{{ route('password.email') }}">@csrf
    <div class="mb-4"><label class="form-label fw-semibold" for="email">Adresse e-mail</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus></div>
    <button class="btn btn-primary w-100">Envoyer le lien</button>
</form>
<div class="text-center mt-3"><a class="small text-decoration-none" href="{{ route('login') }}">Retour à la connexion</a></div>
@endsection
