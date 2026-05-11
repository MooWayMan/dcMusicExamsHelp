# Dev Rules & Workflow

> Read this file when doing development work. Not needed for every conversation.

## Tech Stack
- Laravel (full-stack) with PHP
- Vue 3 + TypeScript + Inertia.js (SPA-like routing)
- Tailwind CSS v4 with `@theme inline` directive
- Laravel Sail (Docker) for local dev: `sail` (aliased to `./vendor/bin/sail`)
- Laravel Cloud for deployment (auto-deploys from `main` branch)
- GitHub repo: MooWayMan/dcMusicExamsHelp (private)
- Template repo: MooWayMan/dcTemplate (private, clean copy for new projects)

## Local Development
```bash
cd ~/Dcode/musicexamshelp
sail up -d
sail npm run dev
```
Two terminal tabs always open: npm run dev + git/commands. Don't tell Paul to start dev server.

## Deployment

### Branch flow (dev → staging → main → prod)
1. All work on `dev` branch
2. Push to GitHub → Laravel Cloud auto-deploys `dev` to **staging**
3. Smoke test on staging URL: `https://dcmusicexamshelp-staging-kbe3t6.laravel.cloud`
4. If green: merge `dev` into `main`
5. Push `main` → Laravel Cloud auto-deploys to **prod** (`musicexams.help`)
6. **Never push directly to `main`** — always go via `dev` + staging

### Staging environment guarantees
- `APP_ENV=staging`, `APP_DEBUG=true`
- `MAIL_MAILER=log` — no real emails ever sent from staging
- `MAILCHIMP_API_KEY` and `MAILCHIMP_LIST_ID` blank — no list comms
- Separate database (`newtest` in `mooway_database_cluster`) — staging cannot affect prod data
- `/robots.txt` route serves `Disallow: /` on non-prod (Google blocked from indexing staging URL)
- Basic auth currently NOT enabled (relies on URL obscurity); add via middleware if staging starts getting scanned

### Misc
- Laravel Cloud auto-runs migrations on deploy (both staging and prod)
- Seeding is manual only
- Prod live URL: `dcmusicexamshelp-main-g97abz.laravel.cloud` (custom: `musicexams.help`)
- Commands tab pre-fills `php artisan` — only give the part after it
- Never use `tinker --execute` on Cloud — build artisan commands instead

## GitHub
- PAT (classic) needs `workflow` scope for pushing .github/workflows files
- Token name: "to my mac push"
- Avoid `git add .` — add specific files instead

## Key File Locations
| File | Purpose |
|---|---|
| `resources/css/app.css` | Brand tokens, Tailwind theme, light/dark mode |
| `resources/js/components/reusables/` | All constructor components |
| `resources/js/components/BookingModal.vue` | Booking pathway chooser (3 Trinity systems) |
| `resources/js/composables/usePageAnimation.ts` | Portable page animation composable |
| `resources/js/pages/ConstructorsDemo.vue` | Demo page showing all components |
| `resources/js/components/UserInfo.vue` | Has SSR null guard (user?.avatar) |
| `tests/Feature/RoutesTest.php` | All public page route tests |

## Booking System (CRITICAL)
Trinity uses THREE separate booking websites. All "Book Your Exam" buttons open BookingModal — NEVER link directly to a single booking URL.

| Exam Type | URL | Code 120? |
|---|---|---|
| Digital (all) | `https://booking.trinitycollege.com/?larCode=120` | YES |
| Rock & Pop F2F | `https://my-trinity.trinitycollege.com/music/grades-and-diplomas/` | NO (auto) |
| Classical & Jazz F2F | `https://musicbooking.trinitycollege.co.uk/OEWeb/loadExamDtl.do` | NO (auto) |

Full reference: `Claude/Research/trinity-booking-systems.md`

## Database
- PostgreSQL — always use `ilike` not `like` for search (case-sensitive gotcha)
- Export from prod TablePlus → make .sql → migrate:fresh + paste into local
- Never destructive commands on prod without backup; test local first

### Model scopes — qualify every column
Every column referenced inside a model scope MUST be table-qualified (`exam_entries.notes`, not bare `notes`). Scopes are composable primitives — the moment a caller adds a `->join('orders', …)` and `orders` also has a `notes` column, an unqualified reference throws `SQLSTATE[42702]: ambiguous column`. Postgres is unforgiving here. This bit us on `/admin/exam-entries` (May 8 2026) — see `app/Models/ExamEntry.php` scope comment.

Same rule applies to any reusable helper method that returns a query Builder or wraps a `where*` call.

## Testing
- Every feature needs Pest tests — never skip (TDD)
- Every new public page: add route test to `tests/Feature/RoutesTest.php`
- Run `sail test` before finishing any work
- Browser testing (Cypress/Playwright) planned for post-launch

### Scope composition test (mandatory alongside isolation test)
For every model scope, add TWO tests:
1. **Isolation test** — `Model::scope()->get()` and assert the right rows come back (semantics).
2. **Composition test** — `Model::query()->leftJoin('other_table', …)->scope()->get()` against a table that shares a likely-conflicting column name. Just asserting the query executes without throwing is enough — the test exists to catch unqualified-column SQL errors that isolation tests can't detect. See `tests/Feature/NoShowSemanticsTest.php` for the pattern (`whereResultPossible survives a join with orders`).

### Lead magnet PDF gating
The Trinity Exam Checklist PDF (downloaded as the lead-magnet reward) lives in S3 at `moowaymusicbucket/musicexamshelp/Trinity Exam Checklist.pdf`. The S3 object should be PRIVATE on production. Access flows:

1. `App\Mail\LeadMagnetDelivery::pdfUrl()` generates a short-lived (15-minute) presigned URL via `Storage::disk('s3')->temporaryUrl(...)` using the IAM user `musicexams-app` credentials (env: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION=eu-west-2`, `AWS_BUCKET=moowaymusicbucket`).
2. The Mailable's `attachments()` HTTP-fetches that URL server-side, attaches the bytes to the email, then the URL expires.
3. The subscriber receives the PDF as an email attachment — they never see or hold the URL.

The IAM user has a single-permission policy (`s3:GetObject` on `moowaymusicbucket/musicexamshelp/*`) so a leaked key can only read those marketing assets — can't delete, upload, or touch any other resource. See `~/Documents/Claude/aws-followups.md` for the IAM setup context.

In local dev or any environment where AWS keys aren't populated, the Mailable falls back to the public URL via `LEAD_MAGNET_PDF_URL` (or the hard-coded default in the class). This keeps `sail` development working without committing keys to non-secret places.

If the PDF asset moves: update `LEAD_MAGNET_PDF_PATH` (path within the bucket) — no code change needed.

### Public forms — throttle + honeypot
Every public, unauthenticated POST endpoint reachable from the website MUST have both:
1. **Rate limiting** — `Route::middleware('throttle:5,1')->group(...)` caps each IP at 5 submissions/minute. Stops single-IP flood attacks. See `routes/web.php` for the pattern.
2. **Honeypot** — a hidden `website_url` input in the Vue form (visually offscreen, `tabindex="-1"`, `autocomplete="off"`) and a `filled($request->input('website_url'))` early-return in the controller that responds success-shaped without doing anything. Stops the long tail of distributed bots that slip under the throttle.

Both layers are independently necessary. Without throttle, a fast attacker overwhelms before honeypot matters. Without honeypot, distributed bots evade per-IP throttling. Together they cut deep into automated abuse without any friction for real users.

Why this matters: contact / lead-magnet / subscribe endpoints fire real outbound emails. An unprotected endpoint is a free email-bombing tool against any third-party address — and bot abuse burns sender reputation, which silently kills deliverability for the entire site. See `tests/Feature/PublicFormProtectionTest.php` for the regression tests.

If adding a new public POST endpoint, the route MUST go inside the existing `throttle:5,1` group and the controller MUST honeypot-check.

## Cowork Sandbox Limitations
- No Docker in sandbox — give Paul sail commands to run, never try them here
- Always separate Mac terminal commands from Cloud Commands tab — never mix in same block
- Give bulk commands, not lots of individual ones

## Template Repo Sync
Constructor changes must also be applied to MooWayMan/dcTemplate.
