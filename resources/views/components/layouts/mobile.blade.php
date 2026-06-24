@props([
    'title' => config('app.name'),
    'heading' => 'Marpel',
    'subheading' => auth()->user()?->name,
    'topAction' => null,
    'nav' => true,
])

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f62fe">
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Marpel">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/marpel.svg">
    <link rel="icon" href="/icons/marpel.svg" type="image/svg+xml">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="mobile-shell">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:10px">
                <div class="brand-mark">M</div>
                <div>
                    <p class="screen-title">{{ $heading }}</p>
                    <p class="screen-subtitle">{{ $subheading }}</p>
                </div>
            </div>
            @auth
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="button secondary" style="min-height:42px;padding:8px 10px;font-size:14px" type="submit">Salir</button>
                </form>
            @endauth
        </header>

        @if (session('status'))
            <div class="job-card" style="border-color:#0f8a5f">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="job-card urgent">
                <strong>Revisa el formulario</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>

    @if ($nav)
        <nav class="bottom-nav" aria-label="Navegacion tecnico">
            <a href="{{ route('technician.dashboard') }}">Hoy</a>
            <a href="{{ route('technician.dashboard') }}#avisos">Avisos</a>
            <a href="{{ route('technician.dashboard') }}#partes">Partes</a>
        </nav>
    @endif
</body>
</html>
