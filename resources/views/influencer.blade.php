<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- PWA -->
        <meta name="theme-color" content="#14b8a6">
        <link rel="apple-touch-icon" href="/apk-logo.png">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="MANTOTA">
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
            }
        </script>
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $page['props']['seo']['title'] ?? config('app.name', 'MANTOTA') . ' — Créateur de contenu' }}">
        <meta property="og:description" content="{{ $page['props']['seo']['description'] ?? 'Espace créateur de contenu MANTOTA' }}">
        @if(!empty($page['props']['seo']['image']))
        <meta property="og:image" content="{{ $page['props']['seo']['image'] }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts — entry point créateur de contenu separe -->
        @routes
        @vite(['resources/js/influencer.js', "resources/js/influencer/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
