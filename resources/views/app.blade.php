<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        {{-- Force light theme; ignore browser/system dark mode --}}
        <meta name="color-scheme" content="light">
        {{-- Lock zoom on mobile to avoid layout conflicts --}}
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Ensure dark class is never applied (always light theme) --}}
        <script>
            (function() {
                document.documentElement.classList.remove('dark');
            })();
        </script>

        {{-- Inline style: light background only --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @if(!empty($branding['header_icon_url']))
            @if(str_ends_with(parse_url($branding['header_icon_url'], PHP_URL_PATH), '.svg'))
                <link rel="icon" href="{{ $branding['header_icon_url'] }}" type="image/svg+xml">
            @else
                <link rel="icon" href="{{ $branding['header_icon_url'] }}" type="image/png">
            @endif
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @endif
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
