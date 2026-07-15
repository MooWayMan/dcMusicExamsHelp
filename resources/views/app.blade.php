<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'light') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Meta domain verification — confirms domain ownership to Meta
             Business Manager. Required for full Aggregated Event Measurement
             signal on iOS 14+ pixel events. Added 16 May 2026.
             NOTE: this tag does NOT fix the "Facebook Page: Not found"
             message in the public "About this content" ad panel — that
             needs fb:pages below. --}}
        <meta name="facebook-domain-verification" content="z78vgs8v7fhls7ae39xs3nf1jstrol" />

        {{-- Meta page-to-website link — explicit pointer from this website
             back to the musicExams.help Facebook Page. This is the tag
             Meta's public "About this content" panel reads to declare
             "this domain belongs to that Page" and stop saying "Facebook
             Page: Not found for musicexams.help". Page ID is the numeric
             Page ID, not the vanity name. Added 18 May 2026. --}}
        <meta property="fb:pages" content="61573366599549" />

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

        {{-- Google Analytics (GA4) with Consent Mode v2.
             gtag.js now loads on EVERY page, NOT just for accepters. Consent
             defaults to DENIED, so until the visitor accepts the banner no
             analytics/ads cookies are written and no identifiers are stored —
             GDPR/PECR compliant. In the denied state Google still sends
             cookieless pings, which let Google Ads MODEL conversions from the
             visitors who decline cookies. That recovers the measurement gap
             found in the 15 Jun ads audit (paid Google clicks were converting
             but the decliners were invisible, so Ads reported 0).
             If the visitor previously accepted, we boot straight to granted.
             The banner's accept()/decline() then fire gtag('consent','update')
             (see useCookieConsent.ts). --}}
        <script>
            (function() {
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                window.gtag = gtag;

                // Tracking network calls (GA4 + Meta Pixel) load in PRODUCTION
                // ONLY, so dev + staging (*.laravel.cloud) never pollute the
                // analytics/ads data or count internal traffic. The gtag() shim
                // above is ALWAYS defined, so consumer JS (useAnalytics /
                // useCookieConsent) can keep calling it as a harmless no-op
                // off-production. Added 15 Jul 2026 (roadmap: internal-traffic
                // exclusion). Pair with the GA4 school-IP / hostname filters.
                window.__TRACKING_ENABLED__ = @json(app()->isProduction());
                if (!window.__TRACKING_ENABLED__) return;

                var granted = localStorage.getItem('cookie-consent') === 'accepted';
                var state = granted ? 'granted' : 'denied';

                // Consent defaults MUST be set before the library config runs.
                gtag('consent', 'default', {
                    ad_storage: state,
                    ad_user_data: state,
                    ad_personalization: state,
                    analytics_storage: state,
                    wait_for_update: 500
                });

                // Redact ad click identifiers + pass gclid through the URL
                // while consent is denied, so Ads modelling still works.
                gtag('set', 'ads_data_redaction', !granted);
                gtag('set', 'url_passthrough', true);

                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtag/js?id=G-TZJ8ZCZW3W';
                document.head.appendChild(s);

                gtag('js', new Date());
                gtag('config', 'G-TZJ8ZCZW3W');
            })();
        </script>

        {{-- Meta Pixel - only loads when cookie consent given. --}}
        {{-- Pixel ID 2164549404093546 = musicExams.help website dataset --}}
        {{-- (live ad account 26629640546692642, NOT the personal one). --}}
        {{-- Production only (like GA above) so dev/staging never fire the pixel. --}}
        @production
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
        @endproduction

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
