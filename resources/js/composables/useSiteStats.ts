// resources/js/composables/useSiteStats.ts
//
// The browser half of the site's own anonymous counter (the server half is
// app/Services/SiteStats.php). It sends only: which page, and for an action
// which action and its button text. No cookie is set or read for it beyond
// Laravel's own CSRF token, and nothing identifies the visitor, so it counts
// people who decline the cookie banner too.
//
// Call installSiteStats() once in app.ts. Actions go through
// recordSiteEvent() / recordButtonPress(), never a fetch of their own.

import { router } from '@inertiajs/vue3'
import { isPublicPage } from '@/lib/publicPages'
import { xsrfToken } from '@/lib/utils'
import { store as siteStatsStore } from '@/routes/site-stats'

const MAX_DETAIL = 80

let currentPath = ''
let currentComponent = ''
let isAdminViewer = false

function pathOf(url: string): string {
    try {
        return new URL(url, window.location.origin).pathname
    } catch {
        return url.split('?')[0].split('#')[0]
    }
}

function send(body: Record<string, string>): void {
    if (typeof window === 'undefined' || isAdminViewer || !currentPath) return

    try {
        void fetch(siteStatsStore.url(), {
            method: 'POST',
            keepalive: true,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify({ url: currentPath, ...body }),
        }).catch(() => {})
    } catch {
        // Counting must never break the page.
    }
}

export function installSiteStats(): void {
    router.on('navigate', (event) => {
        const page = event.detail.page
        const path = pathOf(page.url)
        const props = page.props as { auth?: { user?: { role?: string } | null } }

        currentComponent = page.component
        isAdminViewer = props.auth?.user?.role === 'admin'

        // A search box or filter that re-visits the same page is not a new
        // page open, so only a change of page counts.
        if (path === currentPath) return

        currentPath = path
        send({ kind: 'page' })
    })
}

export function recordSiteEvent(event: string, detail = ''): void {
    send({ kind: 'event', event, detail: detail.slice(0, MAX_DETAIL) })
}

// Button presses are counted on the public pages only: those labels are
// fixed site copy, while a signed-in page can put a person's name on a button.
export function recordButtonPress(label: string): void {
    if (!isPublicPage(currentComponent)) return

    const text = label.replace(/\s+/g, ' ').trim()
    if (text) recordSiteEvent('button', text)
}
