@extends('layouts.guest')
@section('content')
<h1 class="h4 fw-bold mb-1">Content de vous revoir</h1><p class="text-secondary mb-4">Connectez-vous à votre espace de gestion.</p>
<form method="POST" action="{{ route('login.store') }}">@csrf
    <div class="mb-3"><label class="form-label fw-semibold" for="email">Adresse e-mail</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
    <div class="mb-3"><label class="form-label fw-semibold" for="password">Mot de passe</label><input class="form-control" id="password" name="password" type="password" required autocomplete="current-password"></div>
    <div class="d-flex align-items-center justify-content-between mb-4"><label class="form-check small mb-0"><input class="form-check-input" type="checkbox" name="remember"><span class="form-check-label">Se souvenir de moi</span></label><a class="small text-decoration-none" href="{{ route('password.request') }}">Mot de passe oublié ?</a></div>
    <button class="btn btn-primary btn-lg w-100">Se connecter</button>
</form>
@endsection
