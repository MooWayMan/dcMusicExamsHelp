<?php

// ──────────────────────────────────────────
// Public Routes (No Auth Required)
// ──────────────────────────────────────────

test('GET / returns 200', function () {
    $this->get('/')
        ->assertStatus(200);
});

test('GET /faq returns 200', function () {
    $this->get('/faq')
        ->assertStatus(200);
});

test('GET /for-teachers returns 200', function () {
    $this->get('/for-teachers')
        ->assertStatus(200);
});

test('GET /switch-to-centre-120 returns 200', function () {
    $this->get('/switch-to-centre-120')
        ->assertStatus(200);
});

test('GET /switch-to-centre-120 with utm params returns 200', function () {
    $this->get('/switch-to-centre-120?utm_source=google&utm_medium=cpc&utm_campaign=phase1_q2_2026&utm_content=hero')
        ->assertStatus(200);
});

test('GET /trinity-exam-information returns 200', function () {
    $this->get('/trinity-exam-information')
        ->assertStatus(200);
});

test('GET /trinity-exam-information with utm params returns 200', function () {
    $this->get('/trinity-exam-information?utm_source=google&utm_medium=cpc&utm_campaign=phase1_q2_2026&utm_content=info')
        ->assertStatus(200);
});

test('GET /for-parents returns 200', function () {
    $this->get('/for-parents')
        ->assertStatus(200);
});

test('GET /for-students returns 200', function () {
    $this->get('/for-students')
        ->assertStatus(200);
});

test('GET /books returns 200', function () {
    $this->get('/books')
        ->assertStatus(200);
});

test('GET /syllabus returns 200', function () {
    $this->get('/syllabus')
        ->assertStatus(200);
});

test('GET /syllabus with filter params returns 200', function () {
    $this->get('/syllabus?stream=Rock+%26+Pop&instrument=Guitar&grade=Grade+3')
        ->assertStatus(200);
});

test('GET /top-ten returns 200', function () {
    $this->get('/top-ten')
        ->assertStatus(200);
});

test('GET /top-ten with filter params returns 200', function () {
    $this->get('/top-ten?stream=Classical+%26+Jazz&instrument=Piano&grade=Grade+3')
        ->assertStatus(200);
});

test('GET /privacy returns 200', function () {
    $this->get('/privacy')
        ->assertStatus(200);
});

test('GET /cookies returns 200', function () {
    $this->get('/cookies')
        ->assertStatus(200);
});

test('GET /incentives returns 200', function () {
    $this->get('/incentives')
        ->assertStatus(200);
});

test('GET /recognition returns 200', function () {
    $this->get('/recognition')
        ->assertStatus(200);
});

test('GET /exam-guide returns 200', function () {
    $this->get('/exam-guide')
        ->assertStatus(200);
});

test('GET /exam-guide/ucas-points returns 200', function () {
    $this->get('/exam-guide/ucas-points')
        ->assertStatus(200);
});

test('GET /exam-guide/what-to-expect returns 200', function () {
    $this->get('/exam-guide/what-to-expect')
        ->assertStatus(200);
});

test('GET /exam-guide/digital-exams returns 200', function () {
    $this->get('/exam-guide/digital-exams')
        ->assertStatus(200);
});

test('GET /exam-guide/grades-explained returns 200', function () {
    $this->get('/exam-guide/grades-explained')
        ->assertStatus(200);
});

test('GET /exam-guide/syllabuses returns 200', function () {
    $this->get('/exam-guide/syllabuses')
        ->assertStatus(200);
});

test('GET /exam-fees returns 200', function () {
    $this->get('/exam-fees')
        ->assertStatus(200);
});

test('GET /for-teachers/faber-discounts redirects to /books', function () {
    $this->get('/for-teachers/faber-discounts')
        ->assertStatus(301)
        ->assertRedirect('/books');
});

test('GET /go/exam-day redirects (302) to the homepage with poster UTM tags', function () {
    $this->get('/go/exam-day')
        ->assertStatus(302)
        ->assertRedirect('/?utm_source=poster&utm_medium=print&utm_campaign=f2f_examday');
});

test('GET /go/thank-you redirects (302) to the homepage with card UTM tags', function () {
    $this->get('/go/thank-you')
        ->assertStatus(302)
        ->assertRedirect('/?utm_source=card&utm_medium=print&utm_campaign=f2f_thankyou');
});

test('GET /go/{unknown} returns 404', function () {
    $this->get('/go/does-not-exist')
        ->assertStatus(404);
});

test('GET /for-teachers/awards returns 200', function () {
    $this->get('/for-teachers/awards')
        ->assertStatus(200);
});

test('GET /contact returns 200', function () {
    $this->get('/contact')
        ->assertStatus(200);
});

test('GET /about returns 200', function () {
    $this->get('/about')
        ->assertStatus(200);
});

test('GET /terms returns 200', function () {
    $this->get('/terms')
        ->assertStatus(200);
});

// ──────────────────────────────────────────
// sitemap.xml — every <loc> must return 200 (no 301s, no 404s)
// ──────────────────────────────────────────

test('resources/seo/sitemap.xml is well-formed XML', function () {
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file(resource_path('seo/sitemap.xml'));
    expect($xml)->not->toBeFalse('sitemap.xml failed to parse');
    expect($xml->getName())->toBe('urlset');
});

test('every URL in sitemap.xml returns 200', function () {
    $xml = simplexml_load_file(resource_path('seo/sitemap.xml'));
    expect($xml)->not->toBeFalse();

    foreach ($xml->url as $url) {
        $loc = (string) $url->loc;
        $path = parse_url($loc, PHP_URL_PATH) ?: '/';

        $response = $this->get($path);

        expect($response->status())
            ->toBe(200, "Sitemap URL {$loc} returned {$response->status()} — it must be 200 (no redirects, no errors).");
    }
});

test('GET /sitemap.xml is served by Laravel with application/xml Content-Type', function () {
    // Regression guard: the Laravel route forces application/xml so crawlers
    // can't mistake the body for HTML. (The old GSC "Couldn't fetch" was once
    // blamed on Cloudflare/nginx; that was DISPROVEN — Cloudflare serves
    // Googlebot 200, BingBot fetched fine. It's a GSC-side quirk, not origin.)
    $response = $this->get('/sitemap.xml');

    expect($response->status())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/xml');
    expect($response->getContent())->toContain('<urlset');
    expect($response->getContent())->toContain('https://musicexams.help/');
});

// ──────────────────────────────────────────
// robots.txt (env-aware: blocks crawlers on non-prod)
// ──────────────────────────────────────────

test('GET /robots.txt returns 200 with plain text', function () {
    $this->get('/robots.txt')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

test('robots.txt blocks all crawlers when not in production', function () {
    // The test env is "testing", so the non-prod branch fires:
    // expect a blanket Disallow: / with no specific path rules.
    $body = $this->get('/robots.txt')->getContent();

    expect($body)
        ->toContain('User-agent: *')
        ->toContain('Disallow: /')
        ->not->toContain('Sitemap:');
});

// ──────────────────────────────────────────
// Breadcrumb ?from= parameter tests
// ──────────────────────────────────────────

test('GET /incentives?from=for-teachers returns 200', function () {
    $this->get('/incentives?from=for-teachers')
        ->assertStatus(200);
});

test('GET /incentives?from=for-students returns 200', function () {
    $this->get('/incentives?from=for-students')
        ->assertStatus(200);
});

test('GET /recognition?from=for-teachers returns 200', function () {
    $this->get('/recognition?from=for-teachers')
        ->assertStatus(200);
});

test('GET /recognition?from=for-students returns 200', function () {
    $this->get('/recognition?from=for-students')
        ->assertStatus(200);
});

test('GET /recognition?from=for-parents returns 200', function () {
    $this->get('/recognition?from=for-parents')
        ->assertStatus(200);
});

test('GET /recognition?from=trinity-exam-information returns 200', function () {
    $this->get('/recognition?from=trinity-exam-information')
        ->assertStatus(200);
});

test('GET /recognition?from=incentives returns 200', function () {
    $this->get('/recognition?from=incentives')
        ->assertStatus(200);
});

test('GET /for-teachers/awards?from=incentives returns 200', function () {
    $this->get('/for-teachers/awards?from=incentives')
        ->assertStatus(200);
});

test('GET /exam-fees?from=for-parents returns 200', function () {
    $this->get('/exam-fees?from=for-parents')
        ->assertStatus(200);
});

test('GET /for-teachers/faber-discounts?from=for-teachers redirects to /books', function () {
    $this->get('/for-teachers/faber-discounts?from=for-teachers')
        ->assertStatus(301)
        ->assertRedirect('/books');
});

test('GET /exam-guide/syllabuses?from=for-parents returns 200', function () {
    $this->get('/exam-guide/syllabuses?from=for-parents')
        ->assertStatus(200);
});

// ──────────────────────────────────────────
// Admin Routes — basic 200 smoke test
// ──────────────────────────────────────────

test('GET /admin/imports returns 200 for an authenticated admin', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)
        ->get('/admin/imports')
        ->assertStatus(200);
});

test('GET /admin/reconciliation returns 200 for an authenticated admin', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)
        ->get('/admin/reconciliation')
        ->assertStatus(200);
});

test('GET /admin/quarter-comparison returns 200 for an authenticated admin', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)
        ->get('/admin/quarter-comparison')
        ->assertStatus(200);
});

// ──────────────────────────────────────────
// Meta tags in global layout
// ──────────────────────────────────────────

test('homepage renders facebook-domain-verification meta tag', function () {
    // Confirms domain ownership for Meta Business Manager / iOS 14
    // Aggregated Event Measurement. Lives in resources/views/app.blade.php.
    $this->get('/')
        ->assertStatus(200)
        ->assertSee('<meta name="facebook-domain-verification" content="z78vgs8v7fhls7ae39xs3nf1jstrol"', escape: false);
});

test('homepage renders fb:pages meta tag with correct Page ID', function () {
    // Tells Meta's public "About this content" ad panel which Facebook
    // Page owns this domain — fixes the "Facebook Page: Not found"
    // message. Lives in resources/views/app.blade.php.
    $this->get('/')
        ->assertStatus(200)
        ->assertSee('<meta property="fb:pages" content="61573366599549"', escape: false);
});

// ──────────────────────────────────────────
// HTML sitemap (/sitemap) — internal-link discovery backstop.
// (Originally added for a suspected Cloudflare block on /sitemap.xml that
// was later DISPROVEN — kept for crawl discovery + human visitors.)
// ──────────────────────────────────────────

test('GET /sitemap returns 200', function () {
    $this->get('/sitemap')
        ->assertStatus(200);
});

test('GET /sitemap renders the Sitemap Inertia component', function () {
    // The actual URL list is hard-coded in resources/js/pages/Sitemap.vue.
    // If you add a new public route, also add it to the sections array
    // in that file so the sitemap stays in sync.
    $this->get('/sitemap')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Sitemap'));
});
