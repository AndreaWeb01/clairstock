<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Connexion' }} — {{ $businessSettings['business_name'] ?? 'ClairStock' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('clairstock.svg') }}">
    <style>body{min-height:100vh;background:linear-gradient(140deg,#f4f6fc,#edf2ff);display:grid;place-items:center;font-family:Inter,system-ui,sans-serif;color:#17233b}.auth-card{width:min(100% - 2rem,460px);background:#fff;border:1px solid #e7ebf2;border-radius:22px;padding:2.2rem;box-shadow:0 18px 55px #263b6b12}.brand{font-weight:800;letter-spacing:-.04em;font-size:1.35rem;color:#17233b;text-decoration:none}.brand-mark{display:inline-grid;place-items:center;background:#3157d5;color:#fff;width:36px;height:36px;border-radius:11px;margin-right:.55rem}.brand-mark svg{width:24px;height:24px}.form-control{padding:.72rem .85rem}.btn-primary{--bs-btn-bg:#3157d5;--bs-btn-border-color:#3157d5;--bs-btn-hover-bg:#2548bb;--bs-btn-hover-border-color:#2548bb}</style>
</head>
<body>
<main class="auth-card">
    <div class="text-center mb-4"><a class="brand" href="{{ route('home') }}">@if(!empty($businessSettings['business_logo_path']))<img src="{{ asset('storage/'.$businessSettings['business_logo_path']) }}" alt="" class="brand-mark" style="object-fit:cover">@else<span class="brand-mark">@include('components.clairstock-mark')</span>@endif{{ $businessSettings['business_name'] ?? 'ClairStock' }}</a><p class="text-secondary small mt-3 mb-0">La gestion claire de votre commerce</p></div>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
</body>
</html>
