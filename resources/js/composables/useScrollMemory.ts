/**
 * useScrollMemory — remembers scroll position across Inertia navigations.
 *
 * Call installScrollMemory() once in app.ts.
 * Works with back button, breadcrumbs, nav links — any navigation method.
 * Uses Inertia's own `navigate` event to read the destination URL reliably.
 *
 * iOS Safari fix: disables browser's own scroll restoration and uses a
 * polling loop that keeps setting scrollTo until it sticks, because iOS
 * resets scroll position AFTER JavaScript's initial scrollTo fires.
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

/**
 * Aggressively restore scroll position — keeps trying for up to 1 second.
 * iOS Safari resets scroll AFTER our initial scrollTo, so we poll until
 * the position sticks or we give up.
 */
function forceScrollTo(target: number): void {
    let attempts = 0
    const maxAttempts = 20
    const interval = 50 // try every 50ms for up to 1 second

    const tryScroll = () => {
        attempts++
        window.scrollTo(0, target)

        // Stop once the scroll is close enough (within 5px) or we've tried enough
        if (attempts >= maxAttempts || Math.abs(window.scrollY - target) < 5) {
            return
        }
        requestAnimationFrame(() => setTimeout(tryScroll, interval))
    }

    // First attempt after a frame + short delay to let DOM render
    requestAnimationFrame(() => setTimeout(tryScroll, 30))
}

export function installScrollMemory(): void {
    // Disable browser's built-in scroll restoration — we handle it ourselves.
    // This is the key fix for iOS Safari which otherwise fights our scrollTo.
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual'
    }

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
            forceScrollTo(saved)
        }
    })
}
