<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
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

        <title inertia>{{ config('app.name', 'MANTOTA') }}</title>

        <!-- SEO Primary -->
        <meta name="description" content="{{ $page['props']['seo']['description'] ?? 'MANTOTA — Reseau publicitaire 100% Performance au Benin et en Afrique. Connectez vendeurs et créateurs de contenu pour booster vos ventes.' }}">
        <meta name="keywords" content="{{ $page['props']['seo']['keywords'] ?? 'MANTOTA, marketing influence, Benin, Afrique, vendeurs, créateurs de contenu, publicite, e-commerce, campagne, performance, reseau publicitaire' }}">
        <meta name="author" content="MANTOTA">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ url()->current() }}">
        <link rel="alternate" hreflang="fr" href="{{ url()->current() }}">

        <!-- Open Graph -->
        <meta property="og:type" content="{{ $page['props']['seo']['og_type'] ?? 'website' }}">
        <meta property="og:title" content="{{ $page['props']['seo']['title'] ?? config('app.name', 'MANTOTA') . ' — Reseau publicitaire 100% Performance' }}">
        <meta property="og:description" content="{{ $page['props']['seo']['description'] ?? 'Vendez plus, plus vite. Connectez vendeurs et créateurs de contenu au Benin et en Afrique.' }}">
        <meta property="og:image" content="{{ $page['props']['seo']['image'] ?? url('/images/mantota-og.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:site_name" content="MANTOTA">
        <meta property="og:locale" content="fr_FR">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $page['props']['seo']['title'] ?? config('app.name', 'MANTOTA') . ' — Reseau publicitaire 100% Performance' }}">
        <meta name="twitter:description" content="{{ $page['props']['seo']['description'] ?? 'Vendez plus, plus vite. Connectez vendeurs et créateurs de contenu au Benin et en Afrique.' }}">
        <meta name="twitter:image" content="{{ $page['props']['seo']['image'] ?? url('/images/mantota-og.png') }}">

        <!-- Structured Data JSON-LD -->
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "Organization",
            "name": "MANTOTA",
            "url": "{{ config('app.url') }}",
            "logo": "{{ url('/images/logo-dark.png') }}",
            "description": "Reseau publicitaire 100% Performance au Benin et en Afrique. Connectez vendeurs et créateurs de contenu pour booster vos ventes.",
            "foundingDate": "2024",
            "areaServed": {
                "@@type": "Place",
                "name": "Afrique de l'Ouest"
            },
            "sameAs": [],
            "contactPoint": {
                "@@type": "ContactPoint",
                "contactType": "customer service",
                "email": "contact@mantota.com",
                "availableLanguage": "French"
            }
        }
        </script>
        @if(!empty($page['props']['seo']['jsonld']))
        <script type="application/ld+json">{!! json_encode($page['props']['seo']['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
