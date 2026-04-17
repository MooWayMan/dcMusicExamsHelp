# UI System Reference

> Read this file when building or modifying pages/components. Not needed for every conversation.

## Brand Token System
All colours use CSS custom properties (HSL) defined in `resources/css/app.css`. To re-skin for a new app, change only the `:root` HSL values.

### Available Tokens
| Token | Purpose |
|---|---|
| `brand-primary` / `brand-primary-dark` | Main dark colour (navy) |
| `brand-accent` / `brand-accent-dark` | Accent colour (blue) |
| `brand-cta` / `brand-cta-dark` | Call to action (matches accent) |
| `brand-bg` | Page background (light grey) |
| `brand-surface` | Card/component background (white) |
| `brand-surface-soft` | Subtle background (light blue-grey) |
| `brand-text` | Primary text colour (dark) |
| `brand-text-soft` | Secondary/muted text (grey) |
| `brand-text-inverse` | Text on dark backgrounds (white) |
| `brand-border` | Border colour |
| `brand-success` / `brand-success-soft` | Success/commission states (burgundy) |
| `brand-teal` / `brand-teal-soft` | Contact/status indicators (teal) |
| `brand-danger` / `brand-danger-soft` | Error/danger states |

### Gradient Pattern
Headers/footers: `bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary`

---

## Constructor Components
All live in `resources/js/components/reusables/`. Portable — copy folder + brand CSS to start a new project.

### MyTextConstructor.vue
Foundation component — almost every other component uses this for text rendering.

**Props:**
- `variant`: 'display' | 'heading' | 'subheading' | 'body' | 'muted' | 'eyebrow' | 'button' | 'button-sm' | 'button-lg'
- `alignment`: 'left' | 'center' | 'right'
- `textColor`: any Tailwind text colour class or 'inherit'
- `bgColor`: optional background
- `showUnderline`: boolean
- `underlineColor`: defaults to 'bg-brand-accent'
- `spacing`: 'none' | 'tight' | 'normal' | 'relaxed'
- `fontFamily`: 'default' | 'display' | 'asap-condensed'
- `subTitleVariant`: 'body' | 'muted' | 'subheading'
- `bodyVariant`: 'body' | 'muted'

**Variant Sizes (responsive):**
| Variant | Classes |
|---|---|
| `display` | text-5xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold |
| `heading` | text-3xl sm:text-3xl md:text-4xl lg:text-5xl font-bold |
| `subheading` | text-2xl sm:text-2xl md:text-3xl lg:text-4xl font-semibold |
| `body` | text-xl sm:text-xl md:text-2xl lg:text-3xl leading-relaxed |
| `muted` | text-lg sm:text-lg md:text-xl lg:text-2xl leading-relaxed |
| `eyebrow` | text-base sm:text-base font-semibold uppercase tracking-[0.08em] |
| `button` | text-base sm:text-lg font-semibold leading-none |
| `button-sm` | text-sm sm:text-base font-semibold leading-none |
| `button-lg` | text-xl sm:text-xl md:text-2xl font-semibold leading-none |

**Slots:** `#myEyebrow`, `#myTitle`, `#mySubTitle`, default (body text)

### MyButtonConstructor.vue
**Props:**
- `size`: 'small' | 'medium' | 'large' (default: 'medium')
- `variant`: 'primary' | 'secondary' | 'outline' | 'ghost' | 'success' | 'danger' | 'light'
- `icon`: Component (optional), `iconPosition`: 'left' | 'right'
- `fullWidth`: boolean, `disabled`: boolean
- `rounded`: 'md' | 'lg' | 'xl' | 'full'
- `type`: 'button' | 'submit' | 'reset'

**Size Guide:** small = button-sm text (secondary actions) · medium = button text (forms, nav) · large = button-lg text (hero CTAs)

**Events:** `@click`, `@clicked`

### MyRunnerConstructor.vue
Clickable card grid. Emits `@cardClick` with card data + isExternal flag.

**Props:**
- `theArray`: RunnerItem[], `variant`: 'text' | 'icon' | 'image' (default: 'text')
- `columns`: 1–4 (default: 3), `spacing`: 'tight' | 'normal' | 'loose'
- `maxWidth`: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | 'full'
- `enableHover`: boolean (default: true)
- `showHeader` / `showFooter`: boolean (image variant only)
- `imageAspect`: 'video' | 'square' | 'auto'

**RunnerItem interface:**
```ts
{
  id?: string | number
  title: string
  subTitle?: string
  descript?: string
  paragraph?: string
  url?: string
  icon?: Component
  image?: string
  header?: string
  footer?: string
  showIcon?: boolean
  isExternal?: boolean
  level?: number | string
  type?: number | string
  headerBgColor?: string
  headerTextColor?: string
  footerBgColor?: string
  footerTextColor?: string
}
```

**Variant details:**
- `text`: Arrow icon, title (button-lg), subtitle (muted), optional badges
- `icon`: Centred, icon, title (button-lg), subtitle (muted), description. Thick border (4px).
- `image`: Gradient header/footer (button-lg), image (object-contain), title (button-lg), subtitle, paragraph. Equal height with flexbox.

### MyInputConstructor.vue
**Props:**
- `modelValue`: string | number (v-model)
- `type`: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'
- `size`: 'small' | 'medium' | 'large'
- `variant`: 'solid' | 'outline' | 'ghost'
- `placeholder`, `disabled`, `readonly`, `required`, `autofocus`: standard HTML
- `label`: string (button-lg variant), `error` / `success`: string (muted variant)

**Size classes:** small = text-lg sm:text-xl · medium = text-xl sm:text-2xl md:text-3xl · large = text-2xl sm:text-3xl md:text-4xl

**Events:** `@update:modelValue`, `@focus`, `@blur`, `@keyup`, `@keydown`, `@enter`

### MyTextareaConstructor.vue
Same as input minus `type`. Has `rows` (default: 4) and `size` ('small' | 'medium' | 'large').

### MyTableConstructor.vue
**Props:**
- `data`: Array<Record<string, unknown>>
- `columns`: Array<{ key, title, width?, sortable?, sortFn?, align? }>
- `rowKey`: string
- `title` / `subtitle`: optional header text
- `headerColor` / `headerTextColor`: brand tokens
- `size`: 'small' | 'medium' | 'large'
- `striped`, `bordered`, `hoverable`, `responsive`: boolean
- `clickableRows` / `clickableCells`: boolean
- `sortable`: boolean (default: true)
- `defaultSortKey` / `defaultSortDir`: initial sort

**Events:** `@rowClick`, `@cellClick`, `@sort`
**Slots:** `#cell-{columnKey}` for custom cell rendering

### MyAccordionConstructor.vue
**Props:**
- `items`: Array<{ id, question, answer? }>
- `allowMultiple`: boolean (default: false)
- `size`: 'small' | 'medium' | 'large'
- `headerBgColor`, `headerTextColor`, `headerHoverBgColor`, `borderColor`, `contentBgColor`: brand tokens

**Slots:** `#content-{itemId}` for custom content per item

### MyGlassCardConstructor.vue
Glass-style cards for dark backgrounds. Black header bar (icon + title), glass body, black footer bar (CTA).
```vue
<MyGlassCardConstructor :cards="cardData" :columns="2" />
```
Card data: `{ icon, title, subtitle?, detail, link?, linkText? }`

### MyAlert.vue
`title`, `subTitle`, `buttonText` strings · `variant`: 'info' | 'success' | 'warning' | 'neutral'

### MyBanner.vue
`text` (required), `buttonText`, `buttonLink`, `buttonIcon` · `variant`: 'default' | 'dark' | 'primary' · `rounded`: boolean · `padding`: 'tight' | 'normal' | 'loose'

### MyProgress.vue
`percentage` (0-100), `label`, `color` · `animated`, `striped`, `indeterminate`: boolean

### PageHeader.vue
`title` (required), `subtitle`, `eyebrow` · `centerAlign`: boolean · `surface`: 'default' | 'solid' | 'minimal' · `size`: 'default' | 'compact' | 'hero' · `contained`: boolean · `showUnderline`, `showIcon`: boolean · **Slot:** `#actions`

Has built-in animation — don't wrap with `animClass`.

### Legacy Components
- `MyFlexImageGallery.vue` — image grid with overlay text
- `MyIconRunners.vue` — legacy, prefer MyRunnerConstructor icon variant
- `MyPicRunners.vue` — legacy, prefer MyRunnerConstructor image variant
- `MyRunnerListTextInfo.vue` — text info list runner
- `ComingSoon.vue` — placeholder page

---

## Standard Component Configurations
Always apply these exact props — universal across all pages and all apps.

### MyAccordionConstructor (FAQ sections)
```vue
<MyAccordionConstructor
  :items="..."
  size="small"
  header-bg-color="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary"
  header-text-color="text-brand-text-inverse"
  header-hover-bg-color="hover:opacity-90"
  border-color="border-brand-primary"
  content-bg-color="bg-brand-surface"
/>
```

### MyTableConstructor (data tables)
```vue
<MyTableConstructor
  :data="..."
  :columns="..."
  rowKey="..."
  :sortable="true"
  :striped="true"
  :bordered="true"
  size="medium"
/>
```

---

## Page Animation System

### usePageAnimation composable
```ts
import { usePageAnimation } from '@/composables/usePageAnimation'
const { animClass } = usePageAnimation()
```

**In template:**
```html
<div :class="animClass('fade-up', 0)">  <!-- no delay -->
<div :class="animClass('fade-up', 1)">  <!-- 100ms delay -->
<div :class="animClass('slide-left', 2)"> <!-- 200ms delay -->
```

**Types:** `fade-up`, `fade-down`, `slide-left`, `slide-right`, `zoom-in`, `fade`
**Delays:** 0 (none), 1 (100ms), 2 (200ms), 3 (300ms), 4 (500ms), 5 (700ms)

**How it works:** Watches `usePage().url` to detect Inertia navigation. Resets elements to initial state, then after nextTick + rAF sets final state. Tailwind `transition-all duration-700 ease-out` handles animation.

**Typical pattern:** search bar = 1, filters = 2, table/content = 3

---

## New Public Page Checklist

### 1. Register in `app.ts`
Add page name to public pages array so it gets `layout = undefined` (no admin sidebar).

### 2. Page wrapper
```vue
<template>
  <Head :title="..." :description="..." />
  <Navbar />
  <Breadcrumbs :items="breadcrumbs" />
  <div class="min-h-screen bg-black text-brand-text">
    <!-- content -->
  </div>
  <MyFooter variant="gradient" />
</template>
```

### 3. Icons — all `text-brand-accent`
Never mix icon colours. Exception: award tier pages (confirm with Paul).

### 4. Cards — use MyGlassCardConstructor
Manual fallback pattern: `border-4 border-brand-accent rounded-2xl bg-white/10 backdrop-blur-sm` with `bg-black px-5 py-3` header/footer bars.

### 5. Card text sizing
- Titles: `variant="subheading"` or `text-lg sm:text-xl md:text-2xl font-bold`
- Descriptions: plain `<p>` with `text-base sm:text-base md:text-lg leading-relaxed text-white/80`
- NEVER `muted` or `body` variants inside cards

### 6. Bullet lists
`CheckCircle` from lucide-react · `text-brand-accent` · `h-5 w-5 shrink-0`

### 7. Section backgrounds
`bg-black` default · `bg-brand-surface` for contrast · starry bg for celebratory pages only

### 8. Text colours on dark backgrounds
Titles → `text-white` · Icons → `text-brand-accent` · Body → `text-white/80` · Muted → `text-white/60` · Links → `text-brand-accent`

### 9. Glass card header bar
```html
<div class="bg-black px-5 py-3 sm:px-6 flex items-center gap-3">
  <Icon class="h-5 w-5 text-brand-accent" />
  <span class="text-lg font-bold text-white sm:text-xl">Title</span>
</div>
```

### 10. CTA links inside cards
```html
<a class="inline-flex items-center gap-1 text-sm font-semibold text-brand-accent transition hover:opacity-70">
  Link text <ArrowRight class="h-4 w-4" />
</a>
```

### 11. Cross-page links
Add `?from=for-teachers` etc. Don't add from Welcome page or within same hierarchy.

### 12. Pest tests (TDD)
Add to `tests/Feature/RoutesTest.php`. Test route returns 200. Test query params if applicable. Run `sail test`.

### Final checklist
- [ ] Page in `app.ts` public pages array?
- [ ] Navbar, Head, Breadcrumbs, MyFooter imported?
- [ ] All icons `text-brand-accent`?
- [ ] Cards using `border-4 border-brand-accent`?
- [ ] Card descriptions using plain `<p>`, not MyTextConstructor?
- [ ] CheckCircle bullets `text-brand-accent`?
- [ ] No hardcoded colours?
- [ ] Page animations added?
- [ ] Accordion standard config?
- [ ] Titles on dark bg = `text-white`?
- [ ] Glass card header bars correct?
- [ ] Pest tests in RoutesTest.php?
- [ ] Cross-page `?from=` params?
- [ ] No white/light content backgrounds?
