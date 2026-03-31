# Admin Panel Blueprint

Everything learned building the MusicExams.help admin panel. Use this as a reference when building the next one — it'll save you days.

## Tech Stack Recipe

Laravel + Vue 3 + TypeScript + Inertia.js + Tailwind CSS v4 + Laravel Sail (Docker). Deploy via Laravel Cloud from `main` branch. Use the `dcTemplate` repo as a starting point — it has all the constructor components and brand tokens ready.

## Project Structure

```
resources/
├── css/
│   └── app.css                          ← Brand tokens (HSL custom properties)
├── js/
│   ├── components/
│   │   ├── reusables/                   ← Constructor components (portable)
│   │   ├── AppLogo.vue                  ← Sidebar logo + wordmark
│   │   ├── AppSidebarHeader.vue         ← Sticky top nav bar
│   │   └── AppContent.vue               ← Layout content wrapper
│   ├── composables/
│   │   └── usePageAnimation.ts          ← Page transition animations
│   ├── layouts/
│   │   └── app/AppSidebarLayout.vue     ← Main sidebar layout
│   └── pages/
│       └── admin/                       ← Admin pages
│           ├── Dashboard.vue
│           ├── Teachers/
│           │   ├── Index.vue
│           │   ├── Show.vue
│           │   ├── Create.vue
│           │   ├── Edit.vue
│           │   └── partials/TeacherForm.vue
│           ├── Schools/ (same structure)
│           ├── Orders/ (same structure)
│           └── Students/Index.vue
app/
├── Http/Controllers/Admin/              ← One controller per resource
├── Models/                              ← Eloquent models
database/
├── migrations/
├── seeders/
├── factories/                           ← For PEST tests
tests/
└── Feature/Admin/                       ← PEST tests per resource
```

## Layout Architecture

### Sidebar Layout (`AppSidebarLayout.vue`)

```vue
<AppShell variant="sidebar">
    <AppSidebar />
    <AppContent variant="sidebar" class="min-w-0">
        <AppSidebarHeader :breadcrumbs="breadcrumbs" />
        <slot />
    </AppContent>
</AppShell>
```

**Critical:** `min-w-0` on AppContent is essential — without it, flex children won't shrink below their content width and tables will push the layout wider than the viewport.

### Sticky Top Nav Bar (`AppSidebarHeader.vue`)

- Use `sticky top-0 z-30` for the nav bar
- Keep it slim: `h-14` is plenty
- Show brand wordmark image on `sm:` screens, small icon on mobile
- Home button should link to `/` (landing page), not `/admin`
- Logo/wordmark links to `/` too

**DO NOT** use `overflow-x-hidden` or `overflow-x-clip` on any parent of a sticky element — it creates a new scroll context and breaks `position: sticky`. If you need to prevent horizontal overflow, use `min-w-0` on flex children instead.

### Page Container Pattern

Every admin page uses this container:

```html
<div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
```

- `max-w-7xl` (80rem) prevents content from stretching too wide on large screens
- `mx-auto` centres it
- `w-full` fills available space up to the max
- Responsive padding scales for mobile

## Responsive Design Gotchas

These are the hard-won lessons that cost hours to debug:

### 1. Sidebar Eats Your Breakpoints

The sidebar is ~280px wide. When it's open, `sm:` breakpoints trigger at a much smaller content area than you'd expect. **Bump grid breakpoints up one level:**

- Instead of `sm:grid-cols-2` → use `md:grid-cols-2`
- Instead of `sm:grid-cols-3` → use `md:grid-cols-3`
- Instead of `md:grid-cols-4` → use `xl:grid-cols-4`

### 2. Tables Must Scroll Horizontally

Tables with many columns WILL overflow on mobile. The pattern:

```html
<!-- Outer card wrapper - NO overflow classes here -->
<div class="mt-4 rounded-xl border border-brand-border bg-brand-surface">
    <!-- Inner scrollable wrapper - THIS is where overflow-x-auto goes -->
    <div class="overflow-x-auto">
        <table class="min-w-[800px] w-full text-left text-base">
            ...
        </table>
    </div>
</div>
```

**DO NOT** put `overflow-x-auto` on the outer wrapper AND the inner wrapper — the double nesting breaks scrolling. Only the inner div around the table should scroll.

**DO NOT** use `overflow-hidden` on any table wrapper — it clips the horizontal scroll. Use `overflow-x-auto` instead.

Set `min-w-[Xpx]` on the table itself so it doesn't squash columns:
- Tables with 6+ columns: `min-w-[800px]`
- Tables with 4-5 columns: `min-w-[700px]`
- Tables with 3-4 columns: `min-w-[600px]`

### 3. mx-auto in Flex Column Parents

`mx-auto` inside a `display: flex; flex-direction: column` parent prevents `align-items: stretch`. If your content isn't filling the width, check for this.

### 4. MyTableConstructor Responsive

When using `MyTableConstructor` component (not raw HTML tables), it handles its own responsive scroll via the `responsive` prop (defaults to true). The `tableBoxClasses` uses `overflow-x-auto` internally.

## Page Animation System

### How It Works

Uses `onMounted` — Inertia unmounts and remounts page components on navigation, so `onMounted` fires every time. Simple and reliable.

```ts
// In usePageAnimation.ts
export function usePageAnimation() {
    const ready = ref(false)
    onMounted(() => {
        ready.value = false
        nextTick(() => {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    ready.value = true
                })
            })
        })
    })
    return { ready, animClass }
}
```

**Double `requestAnimationFrame`** is essential — ensures the browser paints the hidden state before transitioning to visible. Single rAF is unreliable.

### What We Tried That DIDN'T Work

1. **`watch(() => usePage().url)`** — didn't trigger reliably on Inertia navigation
2. **`router.on('navigate')`** — fires before the new page renders
3. **`router.on('finish')`** — double-fires with onMounted, also fires on `preserveState` visits (sort/filter)
4. **`router.on('before')` with preserveState check** — still problematic
5. **Just `onMounted`** ← THIS IS THE ONE THAT WORKS. Keep it simple.

### Adding Animations to a Page

```ts
import { usePageAnimation } from '@/composables/usePageAnimation'
const { animClass } = usePageAnimation()
```

```html
<PageHeader ... />  <!-- Has its own built-in animations -->
<div :class="animClass('fade-up', 1)">Search bar</div>
<div :class="animClass('fade-up', 2)">Filters</div>
<div :class="animClass('fade-up', 3)">Table</div>
```

### preserveState and Animations

When using `router.get()` for sorting/filtering, pass `preserveState: true`. This keeps the same component instance (no remount, no animation replay) which is what you want — you don't want the whole page to animate just because someone clicked a sort header.

```ts
router.get('/admin/teachers', params, { preserveState: true, replace: true })
```

## Admin Index Page Pattern

Every index page follows this structure:

### Frontend

```vue
<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- 1. PageHeader with eyebrow, title, subtitle, action button -->
        <PageHeader title="Teachers" subtitle="Manage your network" eyebrow="Admin" size="compact">
            <template #actions>
                <Link href="/admin/teachers/create">
                    <MyButtonConstructor variant="primary" size="medium" :icon="Plus">
                        Add Teacher
                    </MyButtonConstructor>
                </Link>
            </template>
        </PageHeader>

        <!-- 2. Search bar + count -->
        <div :class="['mt-6 flex items-center gap-4', animClass('fade-up', 1)]">
            <div class="relative max-w-md flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-text-soft" />
                <input v-model="search" ... />
            </div>
            <p class="text-base text-brand-text-soft">{{ items.total }} items</p>
        </div>

        <!-- 3. Optional filter pills -->
        <!-- 4. Table card with top + bottom pagination -->
        <div :class="['mt-4 rounded-xl border border-brand-border bg-brand-surface', animClass('fade-up', 2)]">
            <!-- Top pagination -->
            <div v-if="items.last_page > 1" class="flex items-center justify-between border-b border-brand-border px-4 py-3">
                ...pagination links...
            </div>
            <!-- Scrollable table -->
            <div class="overflow-x-auto">
                <table class="min-w-[800px] w-full text-left text-base">
                    ...
                </table>
            </div>
            <!-- Bottom pagination (same markup, border-t instead of border-b) -->
        </div>
    </div>
</template>
```

### Backend Controller

```php
public function index(Request $request): Response
{
    $query = Model::with(['relationship'])->withCount('children');

    // Search
    if ($search = $request->input('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // Sorting — relationship columns need subqueries
    $sortBy = $request->input('sort', 'name');
    $sortDir = $request->input('direction', 'asc');

    if ($sortBy === 'school') {
        // Subquery sort for belongsTo/belongsToMany
        $query->orderBy(
            RelatedModel::select('name')
                ->whereColumn('related.id', 'main.related_id')
                ->limit(1),
            $sortDir
        );
    } elseif (in_array($sortBy, $allowedSorts)) {
        $query->orderBy($sortBy, $sortDir);
    }

    $items = $query->paginate(20)->withQueryString();
    // Transform with ->through()
}
```

### Sortable Column Headers

Make ALL columns sortable where it makes sense. Frontend pattern:

```html
<th class="cursor-pointer px-4 py-3 font-semibold text-brand-text hover:text-brand-accent"
    @click="sortBy('name')">
    Name{{ sortIcon('name') }}
</th>
```

```ts
function sortBy(field: string) {
    const direction = filters.sort === field && filters.direction === 'asc' ? 'desc' : 'asc'
    router.get(url, { ...filters, sort: field, direction }, { preserveState: true, replace: true })
}
function sortIcon(field: string) {
    if (filters.sort !== field) return ''
    return filters.direction === 'asc' ? ' ↑' : ' ↓'
}
```

For relationship columns (teacher name, school name, instrument family), use Eloquent subqueries in the controller — see the `StudentController` for examples of sorting by `teacher`, `instrument`, and `instrument_family`.

## Stepped Wizard Pattern

For long forms (like Teacher Create with 5+ sections), convert to a stepped wizard:

### Structure

- Numbered steps with connecting lines and checkmarks for completed steps
- `canProceed()` validation gating per step (e.g. step 1 requires name + email)
- Vue `<Transition>` for slide animations between steps
- Back/Next navigation with Cancel on step 1, Save on final step
- Final step = Review page showing summary with "Edit" links back to each step

### Select All / Clear All

For pill-selector steps (instruments, subject areas, schools), always provide:
- A global "Select All" / "Clear All" button next to the section heading
- Per-group toggles (e.g. "all" / "clear" next to each instrument family)

Teachers often cover many options — it's faster to select all and remove a few than click 20 individual pills.

## Brand Token System

### The Rule

**NEVER use hardcoded Tailwind colours** (blue-500, gray-200, etc.) in any component. Always use brand tokens from `app.css`. This means any app can be reskinned by changing just the `:root` HSL values.

### Available Tokens

```
brand-primary / brand-primary-dark    – Main dark colour
brand-accent / brand-accent-dark      – Accent colour
brand-cta / brand-cta-dark            – Call to action
brand-bg                               – Page background
brand-surface                         – Card/component background
brand-surface-soft                    – Subtle background
brand-text                            – Primary text
brand-text-soft                       – Secondary/muted text
brand-text-inverse                    – Text on dark backgrounds
brand-border                          – Border colour
brand-success / brand-success-soft    – Success states
brand-danger / brand-danger-soft      – Error/danger states
```

### Gradient Pattern

```html
bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary
```

## Buttons

- Always include `cursor-pointer` in button styles
- Use `variant="outline"` for destructive actions like Archive/Delete — red `danger` variant is too alarming
- Label destructive actions as "Archive" not "Delete" when using soft delete

## Things NOT to Do

1. **Don't use `overflow-x-hidden` or `overflow-x-clip` on layout wrappers** — breaks sticky positioning and table scrolling
2. **Don't use double `overflow-x-auto`** — outer card + inner table wrapper. Only the inner one.
3. **Don't use `sm:` breakpoints for grids** when there's a sidebar — bump to `md:` / `xl:`
4. **Don't use `heading` or `subheading` variants for card titles** — they overflow on iPad portrait. Use `button-lg`.
5. **Don't use `router.on()` for animations** — just use `onMounted`. Inertia remounts on navigation.
6. **Don't use purple** — Trinity College London brand guidelines forbid it
7. **Don't use `git add .`** — add specific files to avoid committing unwanted stuff
8. **Don't hardcode colours** — always brand tokens
9. **Don't put `mx-auto` inside flex-column parents** without understanding it prevents stretch
10. **Don't forget `min-w-0`** on flex children that contain tables or wide content

## Testing with PEST

- One test file per resource in `tests/Feature/Admin/`
- Use `RefreshDatabase` trait
- Create factories for all models (`SchoolFactory`, `OrderFactory`, `StudentFactory`)
- Test: index loads, create/store works, show loads, edit/update works, delete works, sorting works, search works

## Deployment Checklist

1. Push to `main` branch
2. Laravel Cloud auto-deploys and runs migrations
3. Seeding is manual (run via SSH or Tinker)
4. After constructor changes, sync to `dcTemplate` repo

## Starting a New Admin Panel

1. Clone `dcTemplate` repo
2. Update `:root` HSL values in `app.css` for new brand
3. Update logo URLs in `AppSidebarHeader.vue` and `AppLogo.vue`
4. Create models, migrations, controllers, and pages following the patterns above
5. Add `usePageAnimation` to every page
6. Add pagination top + bottom on every index page
7. Make all sensible columns sortable
8. Test on mobile — check table scrolling and grid breakpoints
9. Write PEST tests
