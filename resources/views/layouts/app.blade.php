<!DOCTYPE html>
<html lang="{{ auth()->user()->language ?? 'id' }}" class="{{ auth()->user()->theme === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#6366f1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
    <link rel="icon" href="{{ url('/favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ url('/icons/icon-192.png') }}">
    <title>@yield('title', 'Remindly')</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand brand-link" href="{{ route('dashboard') }}"><span class="brand-mark">✓</span><span>Remindly</span></a>
            <nav class="nav" aria-label="Navigasi utama">
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">⌂ <span>Dashboard</span></a>
                <a class="nav-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}">☑ <span>Tugas</span></a>
                <a class="nav-item {{ request()->routeIs('calendar') ? 'active' : '' }}" href="{{ route('calendar') }}">▦ <span>Kalender</span></a>
                <a class="nav-item {{ request()->routeIs('reminders.*') ? 'active' : '' }}" href="{{ route('reminders.index') }}">♢ <span>Pengingat</span></a>
                <a class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}">⚙ <span>Pengaturan</span></a>
            </nav>
            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-item" type="submit">↪ <span>Keluar</span></button></form>
            </div>
        </aside>
        <main class="main">
            <header class="topbar">
                <div class="greeting"><h1>Halo, {{ auth()->user()->name }}</h1><p>Kelola hidup Anda dengan lebih terorganisir.</p></div>
                <a class="icon-button notification-button" href="{{ route('reminders.index') }}" aria-label="Notifikasi">♢
                    @if(($unreadCount ?? 0) > 0)<span class="notification-badge">{{ $unreadCount }}</span>@endif
                </a>
            </header>
            @if(session('status'))<div class="success-line" role="status">{{ session('status') }}</div>@endif
            @yield('content')
        </main>
        <nav class="mobile-nav" aria-label="Navigasi mobile">
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" aria-label="Dashboard">⌂</a>
            <a class="{{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}" aria-label="Tugas">☑</a>
            <a class="{{ request()->routeIs('calendar') ? 'active' : '' }}" href="{{ route('calendar') }}" aria-label="Kalender">▦</a>
            <a class="{{ request()->routeIs('reminders.*') ? 'active' : '' }}" href="{{ route('reminders.index') }}" aria-label="Pengingat">♢</a>
            <a class="{{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}" aria-label="Pengaturan">⚙</a>
        </nav>
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('{{ url('/sw.js') }}'));
        }
    </script>
</body>
</html>
