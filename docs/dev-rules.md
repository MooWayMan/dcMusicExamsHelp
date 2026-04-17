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
- All work on `dev` branch; merge to `main` to deploy
- Laravel Cloud auto-deploys from `main`, runs migrations automatically
- Seeding is manual only
- Live URL: dcmusicexamshelp-main-g97abz.laravel.cloud
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

## Testing
- Every feature needs Pest tests — never skip (TDD)
- Every new public page: add route test to `tests/Feature/RoutesTest.php`
- Run `sail test` before finishing any work
- Browser testing (Cypress/Playwright) planned for post-launch

## Cowork Sandbox Limitations
- No Docker in sandbox — give Paul sail commands to run, never try them here
- Always separate Mac terminal commands from Cloud Commands tab — never mix in same block
- Give bulk commands, not lots of individual ones

## Template Repo Sync
Constructor changes must also be applied to MooWayMan/dcTemplate.
