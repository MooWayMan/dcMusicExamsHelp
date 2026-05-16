<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'light') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Meta domain verification — links musicexams.help to the
             musicExams.help Facebook Page so the (i) "About this content"
             panel on ads no longer says "Facebook Page: Not found". Also
             required for full Aggregated Event Measurement signal on iOS
             14+ pixel events. Added 16 May 2026. --}}
        <meta name="facebook-domain-verification" content="z78vgs8v7fhls7ae39xs3nf1jstrol" />

        {{-- Opt out of Chrome's "force dark mode" — the site has its own design tokens --}}
        <meta name="color-scheme" content="light">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "light" }}';

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
                color-scheme: light;
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        {{-- Google Analytics (GA4) - only loads when cookie consent given --}}
        <script>
            (function() {
                if (localStorage.getItem('cookie-consent') === 'accepted') {
                    var s = document.createElement('script');
                    s.async = true;
                    s.src = 'https://www.googletagmanager.com/gtag/js?id=G-TZJ8ZCZW3W';
                    document.head.appendChild(s);
                    window.dataLayer = window.dataLayer || [];
                    function gtag(){dataLayer.push(arguments);}
                    gtag('js', new Date());
                    gtag('config', 'G-TZJ8ZCZW3W');
                    window.gtag = gtag;
                }
            })();
        </script>

        {{-- Meta Pixel - only loads when cookie consent given. --}}
        {{-- Pixel ID 2164549404093546 = musicExams.help website dataset --}}
        {{-- (live ad account 26629640546692642, NOT the personal one). --}}
        <script>
            (function() {
                if (localStorage.getItem('cookie-consent') === 'accepted') {
                    !function(f,b,e,v,n,t,s)
                    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                    n.queue=[];t=b.createElement(e);t.async=!0;
                    t.src=v;s=b.getElementsByTagName(e)[0];
                    s.parentNode.insertBefore(t,s)}(window, document,'script',
                    'https://connect.facebook.net/en_US/fbevents.js');
                    fbq('init', '2164549404093546');
                    fbq('track', 'PageView');
                }
            })();
        </script>
        <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id=2164549404093546&ev=PageView&noscript=1"
        /></noscript>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-accent focus:px-4 focus:py-2 focus:text-white focus:shadow-lg">
            Skip to main content
        </a>
        <x-inertia::app />
    </body>
</html>
