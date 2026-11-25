<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#6366f1">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
    <link rel="icon" href="{{ url('/favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ url('/icons/icon-192.png') }}">
    <title>@yield('title', 'Remindly')</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <main class="auth-layout">
        <section class="auth-card card">
            <a class="brand auth-brand brand-link" href="{{ route('login') }}">
                <span class="brand-mark" aria-hidden="true">✓</span>
                <span>Remindly</span>
            </a>
            @yield('content')
        </section>
    </main>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('{{ url('/sw.js') }}'));
        }
    </script>
</body>
</html>
