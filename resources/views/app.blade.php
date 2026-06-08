<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Manrope:wght@200..800&display=swap"
        rel="stylesheet">

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? 'system' }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    <link rel="icon" href="/images/new-egypt-logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/images/new-egypt-logo.png">

    @fonts

    @viteReactRefresh
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    <x-inertia::head>
        @isset($meta)
            <title>{{ $meta['title'] }} — {{ __('site.brand_name') }}</title>
            <meta name="description" content="{{ $meta['description'] }}">

            <meta property="og:type" content="{{ $meta['type'] }}">
            <meta property="og:site_name" content="{{ __('site.brand_name') }}">
            <meta property="og:title" content="{{ $meta['title'] }}">
            <meta property="og:description" content="{{ $meta['description'] }}">
            @if ($meta['image'])
                <meta property="og:image" content="{{ $meta['image'] }}">
            @endif
            <meta property="og:url" content="{{ $meta['url'] }}">
            @if ($meta['published'])
                <meta property="article:published_time" content="{{ $meta['published'] }}">
            @endif

            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $meta['title'] }}">
            <meta name="twitter:description" content="{{ $meta['description'] }}">
            @if ($meta['image'])
                <meta name="twitter:image" content="{{ $meta['image'] }}">
            @endif
        @else
            <title>{{ __('site.brand_name') }}</title>
        @endisset
    </x-inertia::head>
</head>

<body class="font-sans antialiased">
    <x-inertia::app />
</body>

</html>
