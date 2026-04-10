/**
 * useScrollMemory — remembers scroll position across Inertia navigations.
 *
 * Call installScrollMemory() once in app.ts.
 * Works with back button, breadcrumbs, nav links — any navigation method.
 * Uses Inertia's own `navigate` event to read the destination URL reliably.
 */
import { router } from '@inertiajs/vue3'

const scrollPositions = new Map<string, number>()

/**
 * Extract just the pathname from a URL string (strips query params + hash).
 */
function toPathname(url: string): string {
    try {
        return new URL(url, window.location.origin).pathname
    } catch {
        return url.split('?')[0].split('#')[0]
    }
}

export function installScrollMemory(): void {
    // Save current scroll position before leaving the page
    router.on('before', () => {
        scrollPositions.set(toPathname(window.location.href), window.scrollY)
    })

    // Restore scroll position after arriving at the new page
    router.on('navigate', (event) => {
        const pathname = toPathname(event.detail.page.url)
        const saved = scrollPositions.get(pathname)

        if (saved !== undefined && saved > 0) {
            scrollPositions.delete(pathname)
            // Allow page content + animations to render before scrolling
            setTimeout(() => {
                window.scrollTo({ top: saved, behavior: 'instant' })
            }, 150)
        }
    })
}
