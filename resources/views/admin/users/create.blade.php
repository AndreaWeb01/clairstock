@extends('layouts.app', ['title' => 'Nouvel utilisateur'])
@section('content')
<div class="mb-4"><a href="{{ route('admin.users.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Retour aux utilisateurs</a><h1 class="h3 fw-bold mt-2 mb-1">Ajouter un utilisateur</h1><p class="muted mb-0">Créez un accès pour un membre de votre équipe.</p></div>
<div class="panel p-4" style="max-width:760px"><form method="POST" action="{{ route('admin.users.store') }}">@csrf
    <div class="row g-3"><div class="col-md-6"><label class="form-label fw-semibold" for="name">Nom</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" required autofocus></div>
    <div class="col-md-6"><label class="form-label fw-semibold" for="email">Adresse e-mail</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required></div>
    <div class="col-md-6"><label class="form-label fw-semibold" for="role_id">Rôle</label><select class="form-select" id="role_id" name="role_id" required><option value="">Choisir un rôle</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->label }}</option>@endforeach</select></div>
    <div class="col-12"><hr><p class="small muted mb-0">Le mot de passe initial doit contenir au moins 10 caractères.</p></div>
    <div class="col-md-6"><label class="form-label fw-semibold" for="password">Mot de passe initial</label><input class="form-control" id="password" name="password" type="password" required autocomplete="new-password"></div>
    <div class="col-md-6"><label class="form-label fw-semibold" for="password_confirmation">Confirmer le mot de passe</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div>
    <div class="col-12"><button class="btn btn-primary">Créer l’utilisateur</button></div></div>
</form></div>
@endsection
