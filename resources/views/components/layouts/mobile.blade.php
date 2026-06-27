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
    @php
        $marpelNotifications = auth()->check()
            ? auth()->user()->unreadNotifications()->latest()->limit(5)->get()
            : collect();
    @endphp

    <main class="mobile-shell">
        <header class="topbar">
            <div class="topbar-brand">
                <div class="brand-mark">M</div>
                <div>
                    <p class="screen-title">{{ $heading }}</p>
                    <p class="screen-subtitle">{{ $subheading }}</p>
                </div>
            </div>
            @auth
                <div class="topbar-actions">
                    <details class="notification-menu">
                        <summary aria-label="Notificaciones">
                            <span class="notification-icon">!</span>
                            @if ($marpelNotifications->isNotEmpty())
                                <span class="notification-count">{{ $marpelNotifications->count() }}</span>
                            @endif
                        </summary>
                        <div class="notification-panel">
                            <strong>Notificaciones</strong>
                            @forelse ($marpelNotifications as $notification)
                                @php($workOrderId = $notification->data['work_order_id'] ?? null)
                                @if ($workOrderId)
                                    <a href="{{ route('technician.work-orders.show', $workOrderId) }}">
                                        <span>{{ $notification->data['title'] ?? 'Nuevo parte asignado' }}</span>
                                        <small>{{ $notification->data['body'] ?? 'Toca para abrir el parte.' }}</small>
                                    </a>
                                @endif
                            @empty
                                <p>Sin notificaciones nuevas.</p>
                            @endforelse
                        </div>
                    </details>

                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="button secondary compact" type="submit">Salir</button>
                    </form>
                </div>
            @endauth
        </header>

        @if (session('status'))
            <div class="flash-card success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash-card danger">
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
            <a class="{{ request()->routeIs('technician.dashboard') ? 'active' : '' }}" href="{{ route('technician.dashboard') }}">Hoy</a>
            <a class="{{ request()->routeIs('technician.notices.*') ? 'active' : '' }}" href="{{ route('technician.notices.index') }}">Avisos</a>
            <a class="{{ request()->routeIs('technician.work-orders.show', 'technician.work-orders.update', 'technician.work-orders.signature', 'technician.work-orders.signature.store') ? 'active' : '' }}" href="{{ route('technician.dashboard') }}#partes">Partes</a>
            <a class="{{ request()->routeIs('technician.work-orders.closed') ? 'active' : '' }}" href="{{ route('technician.work-orders.closed') }}">Cerrados</a>
        </nav>
    @endif
</body>
</html>
