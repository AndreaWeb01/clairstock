@extends('layouts.app', ['title' => 'Mon profil'])
@section('content')
<div class="mb-4"><h1 class="h3 fw-bold mb-1">Mon profil</h1><p class="muted mb-0">Gérez les informations de votre compte.</p></div>
<div class="panel p-4" style="max-width:760px"><form method="POST" action="{{ route('profile.update') }}">@csrf @method('PUT')
    <div class="row g-3"><div class="col-md-6"><label class="form-label fw-semibold" for="name">Nom</label><input class="form-control" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required></div>
    <div class="col-md-6"><label class="form-label fw-semibold" for="email">Adresse e-mail</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required></div>
    <div class="col-12"><hr><h2 class="h6 fw-bold">Changer le mot de passe</h2><p class="small muted">Laissez ces champs vides si vous ne souhaitez pas le modifier.</p></div>
    <div class="col-md-6"><label class="form-label" for="current_password">Mot de passe actuel</label><input class="form-control" id="current_password" type="password" name="current_password" autocomplete="current-password"></div>
    <div class="col-md-6"><label class="form-label" for="password">Nouveau mot de passe</label><input class="form-control" id="password" type="password" name="password" autocomplete="new-password"></div>
    <div class="col-md-6"><label class="form-label" for="password_confirmation">Confirmer le mot de passe</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
    <div class="col-12"><button class="btn btn-primary">Enregistrer les modifications</button></div></div>
</form></div>
@endsection
