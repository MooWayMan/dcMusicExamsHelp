# UI System Rules (musicexams.help)

## Core Philosophy
- Use reusable constructors for all UI
- Keep styling token-based (no hardcoded colours)
- Ensure consistency across all pages

---

## Design Tokens
- All colours must come from app.css variables
- Use:
  - brand-primary
  - brand-accent
  - brand-surface
  - brand-text
  - brand-border
- Include semantic tokens:
  - success, warning, danger, info
- Support light and dark mode

---

## Typography (MyTextConstructor)
- All UI text must use MyTextConstructor
- Variants:
  - display
  - heading
  - subheading
  - body
  - muted
  - eyebrow
- Do NOT hardcode text sizes in components
- Do NOT use per-breakpoint props
- Allow optional overrides (e.g. textColor)

---

## Inputs & Forms
- Labels use MyTextConstructor
- Helper / error / success text use MyTextConstructor
- Input text itself remains native (not wrapped)

---

## Buttons
- Always use MyButtonConstructor
- Use brand tokens for colours
- Allow overrides but default to system

---

## Alerts
- Use semantic tokens (success, warning, danger)
- All text via MyTextConstructor

---

## Progress
- Labels use MyTextConstructor
- Colours use semantic tokens
- Animations must behave correctly

---

## Runner System
- Use one MyRunnerConstructor
- Variants:
  - text
  - icon
  - image
- Header and footer must align consistently
- Avoid spacing inconsistencies between cards

---

## Components Rules
- No hardcoded Tailwind colours inside reusable components
- Use tokens instead
- Keep components flexible but not over-configurable

---

## Pages
- Compose pages using constructors only
- Avoid custom styling unless necessary
- One-offs are allowed but should be minimal

---

## Reusability Goal
- Entire system should be reusable in other apps by:
  - copying components
  - changing only app.css tokens

---

## Boot Instruction (IMPORTANT)
When working with this project:
- Always prioritise MyTextConstructor for text
- Always use token-based colours (no hardcoded Tailwind colours)
- Prefer reusable constructors over custom code
- Maintain consistency across components and pages

## Layout System
- Use max-w-7xl for standard sections
- Use max-w-4xl or max-w-5xl for narrower reading sections
- Use lg:grid-cols-2 for split layouts
- Use lg:grid-cols-3 for card grids
- Control balance with max-w-* inside columns
- Avoid grid-cols-[...] and percentage width hacks

