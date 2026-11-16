<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Tableau de bord' }} — {{ $businessSettings['business_name'] ?? 'ClairStock' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root{--ink:#17233b;--muted:#718096;--brand:#3157d5;--canvas:#f4f6fa;--line:#e7ebf2}
        body{background:var(--canvas);color:var(--ink);font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
        .app-shell{min-height:100vh}.sidebar{width:254px;background:#fff;border-right:1px solid var(--line);position:fixed;inset:0 auto 0 0;z-index:1030;display:flex;flex-direction:column;padding:1.4rem 1rem}
        .brand{font-weight:800;letter-spacing:-.04em;font-size:1.3rem;color:var(--ink);text-decoration:none;padding:.2rem .6rem 1.6rem}.brand-mark{display:inline-grid;place-items:center;background:var(--brand);color:white;width:34px;height:34px;border-radius:11px;margin-right:.5rem}
        .nav-caption{text-transform:uppercase;letter-spacing:.1em;font-size:.68rem;color:#9aa4b5;font-weight:700;padding:.6rem .75rem}.sidebar .nav-link{color:#637087;border-radius:10px;padding:.7rem .8rem;font-size:.9rem;font-weight:550;display:flex;gap:.8rem;align-items:center}.sidebar .nav-link:hover,.sidebar .nav-link.active{background:#eef2ff;color:var(--brand)}.sidebar .nav-link i{font-size:1.05rem;width:20px;text-align:center}.nav-soon{font-size:.65rem;margin-left:auto;color:#9aa4b5}
        .main-wrap{margin-left:254px;min-height:100vh}.topbar{height:72px;background:rgba(255,255,255,.92);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 2rem;position:sticky;top:0;z-index:1020}.content{padding:1.8rem 2rem 2.5rem;max-width:1500px;margin:0 auto}.panel{background:white;border:1px solid var(--line);border-radius:16px;box-shadow:0 2px 10px rgba(26,39,67,.025)}.stat-card{padding:1.25rem}.stat-icon{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;background:#eef2ff;color:var(--brand);font-size:1.15rem}.muted{color:var(--muted)}.form-control,.form-select{border-color:#dfe5ef;padding:.7rem .85rem}.form-control:focus,.form-select:focus{border-color:#8ea4f4;box-shadow:0 0 0 .2rem rgba(49,87,213,.12)}.btn-primary{--bs-btn-bg:var(--brand);--bs-btn-border-color:var(--brand);--bs-btn-hover-bg:#2548bb;--bs-btn-hover-border-color:#2548bb}.table>:not(caption)>*>*{padding:.9rem .85rem;border-bottom-color:#edf0f5}.mobile-brand{display:none}
        @media(max-width:991.98px){.sidebar{transform:translateX(-100%);transition:transform .2s ease;box-shadow:0 12px 30px #17233b20}.sidebar.show{transform:translateX(0)}.main-wrap{margin-left:0}.topbar{padding:0 1rem}.content{padding:1.3rem 1rem 2rem}.mobile-brand{display:inline-flex}.desktop-search{display:none}}
    </style>
    @stack('styles')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">@if(!empty($businessSettings['business_logo_path']))<img src="{{ asset('storage/'.$businessSettings['business_logo_path']) }}" alt="Logo {{ $businessSettings['business_name'] ?? 'du commerce' }}" class="brand-mark" style="object-fit:cover">@else<span class="brand-mark"><i class="bi bi-box-seam"></i></span>@endif{{ $businessSettings['business_name'] ?? 'ClairStock' }}</a>
        <div class="nav-caption">Espace de travail</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i>Tableau de bord</a>
            <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><i class="bi bi-box-seam"></i>Produits</a>
            <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}"><i class="bi bi-tags"></i>Catégories</a>
            <a class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}" href="{{ route('stock.index') }}"><i class="bi bi-layers"></i>Stock</a>
            <a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}"><i class="bi bi-cart3"></i>Ventes</a>
            <a class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}"><i class="bi bi-bag-check"></i>Achats</a>
            <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><i class="bi bi-people"></i>Clients</a>
            <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><i class="bi bi-truck"></i>Fournisseurs</a>
            @if(auth()->user()->isAdmin())<a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><i class="bi bi-wallet2"></i>Dépenses</a>@endif
            @foreach ([['Rapports','bar-chart-line']] as [$label,$icon])
                <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}"><i class="bi bi-{{ $icon }}"></i>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="nav-caption mt-3">Administration</div>
        <nav class="nav flex-column gap-1">
            @if (auth()->user()->isAdmin())
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-person-gear"></i>Utilisateurs</a>
                <a class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings') }}"><i class="bi bi-gear"></i>Paramètres</a>
                <a class="nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}" href="{{ route('admin.activity-logs.index') }}"><i class="bi bi-clock-history"></i>Journal d’activité</a>
            @endif
        </nav>
        <div class="mt-auto border-top pt-3">
            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}"><i class="bi bi-person-circle"></i>Mon profil</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link w-100 border-0 bg-transparent text-start"><i class="bi bi-box-arrow-left"></i>Déconnexion</button></form>
        </div>
    </aside>
    <div class="main-wrap">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" type="button" onclick="document.getElementById('sidebar').classList.toggle('show')" aria-label="Ouvrir le menu"><i class="bi bi-list fs-5"></i></button>
                <div class="desktop-search input-group" style="width:300px"><span class="input-group-text bg-white border-end-0"><i class="bi bi-search muted"></i></span><input class="form-control border-start-0" placeholder="Rechercher…" aria-label="Rechercher"></div>
            </div>
            <div class="d-flex align-items-center gap-3"><span class="d-none d-sm-inline small muted">{{ now()->format($dateFormat ?? 'd/m/Y') }}</span><div class="vr"></div><a class="text-decoration-none d-flex align-items-center gap-2 text-dark" href="{{ route('profile.edit') }}"><span class="rounded-circle bg-primary-subtle text-primary fw-bold d-grid" style="width:36px;height:36px;place-items:center">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span class="d-none d-md-inline small fw-semibold">{{ auth()->user()->name }}</span></a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn btn-outline-secondary btn-sm text-nowrap"><i class="bi bi-box-arrow-right me-sm-1"></i><span class="d-none d-sm-inline">Déconnexion</span><span class="visually-hidden d-sm-none">Déconnexion</span></button></form></div>
        </header>
        <main class="content">
            @if (session('status'))<div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger border-0 shadow-sm"><div class="fw-semibold mb-1">Certaines informations sont à corriger.</div><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
@stack('scripts')
</body>
</html>
