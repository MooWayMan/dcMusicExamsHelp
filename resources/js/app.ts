// resources/js/app.ts
import '../css/app.css'
import '../css/fonts.css'

import { createApp, h, type DefineComponent, defineAsyncComponent } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'

import { initializeTheme } from '@/composables/useAppearance'
import { installScrollMemory } from '@/composables/useScrollMemory'
import { authConfig } from '@/composables/useAuthConfig'

// Layouts
import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import UserLayout from '@/layouts/UserLayout.vue'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createInertiaApp({
    title: (title) => title || appName,

    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue')
        )

        // ===============================
        // Layout switching
        // ===============================

        // Pull the current Inertia page payload off the root element. We
        // can't use `usePage()` here because the Vue app hasn't been
        // created yet — but the SSR-injected JSON on #app[data-page]
        // already contains the auth user, so we can route a non-admin to
        // UserLayout for the Dashboard without an extra request.
        const rootEl = typeof document !== 'undefined'
            ? document.getElementById('app')
            : null
        let authUser: { role?: string } | null = null
        if (rootEl?.dataset.page) {
            try {
                const parsed = JSON.parse(rootEl.dataset.page)
                authUser = parsed?.props?.auth?.user ?? null
            } catch {
                authUser = null
            }
        }
        const isAuthed = !!authUser
        const isAdmin = authUser?.role === 'admin'

        if (['Welcome', 'ConstructorsDemo', 'Faq', 'ForTeachers', 'TeacherAwards', 'SwitchToCentre120', 'TrinityExamInformation', 'ForParents', 'ForStudents', 'Books', 'Syllabus', 'TopTen', 'ThankYou', 'ExamGuide', 'ExamGuideUcas', 'ExamGuideExpect', 'ExamGuideDigital', 'ExamGuideGrades', 'ExamGuideSyllabuses', 'ExamFees', 'Incentives', 'Contact', 'About', 'PrivacyPolicy', 'CookiePolicy', 'TermsOfUse', 'ComingSoonPage', 'Sitemap'].includes(name)) {
            // Public marketing pages → clean layout (no admin sidebar)
    page.default.layout = undefined

        } else if (name.startsWith('auth/')) {
            // defineOptions({ layout: { title, description } }) sets
            // page.default.layout to a plain object, NOT a component.
            // Extract that config so AuthLayout can use it, then always
            // assign the real AuthLayout component.
            const opt = page.default.layout
            if (opt && typeof opt === 'object' && !('setup' in opt) && !('render' in opt)) {
                authConfig.title = (opt as any).title || ''
                authConfig.description = (opt as any).description || ''
            }
            page.default.layout = AuthLayout

        } else if (name.startsWith('settings/')) {
            // Settings pages set defineOptions({ layout: { breadcrumbs } }) —
            // a config object, not a layout component. Always overwrite so
            // the real layout chain renders. Non-admin users get UserLayout
            // (their lean sidebar) + SettingsLayout (settings nav).
            page.default.layout = isAdmin
                ? [AppLayout, SettingsLayout]
                : [UserLayout, SettingsLayout]

        } else if (name === 'Dashboard') {
            // Dashboard.vue uses defineOptions({ layout: { breadcrumbs: [...] } })
            // — that's a config object, not a layout component. We must
            // ALWAYS overwrite it (not `||`) so a real layout component
            // is rendered. Admins get AppLayout (full admin sidebar);
            // everyone else gets UserLayout (lean teacher sidebar).
            page.default.layout = isAdmin ? AppLayout : UserLayout

        } else {
            // Default app pages
            page.default.layout = page.default.layout || AppLayout
        }

        return page
    },

    setup({ el, App, props, plugin }) {
        const CookieConsent = defineAsyncComponent(() => import('@/components/CookieConsent.vue'))
        // NewsletterPopup retired in favour of the inline LeadMagnetCapture
        // lead-magnet on Welcome.vue. Component file kept in the repo so
        // existing tests / other forms still resolve, but no longer mounted
        // globally — Paul to delete properly once the lead magnet is proven.
        const ScrollToTop = defineAsyncComponent(() => import('@/components/ScrollToTop.vue'))

        createApp({
            render: () => h('div', [
                h(App, props),
                h(CookieConsent),
                h(ScrollToTop),
            ])
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)
    },

    progress: {
        color: '#4B5563',
    },
})

// ===============================
// Client-only logic
// ===============================
if (typeof window !== 'undefined') {
    initializeTheme()

    // Scroll position memory (back button, breadcrumbs, nav links)
    installScrollMemory()

    // ===============================
    // Session expiry handler
    // ===============================
    // When the session expires, Inertia requests return a non-Inertia
    // response (419 CSRF mismatch or a redirect to /login). This catches
    // those and shows a clear message instead of silently failing.
    router.on('invalid', (event) => {
        const status = event.detail.response?.status

        // 419 = CSRF token expired (session gone)
        // 401 = Unauthenticated
        if (status === 419 || status === 401) {
            event.preventDefault()
            alert('Your session has expired. You will be redirected to log in again.')
            window.location.href = '/login'
            return
        }

        // Any other non-Inertia response likely means session expired
        // (Laravel redirects to /login which returns HTML, not Inertia JSON)
        if (!status || status === 302 || status === 200) {
            event.preventDefault()
            alert('Your session has expired. You will be redirected to log in again.')
            window.location.href = '/login'
            return
        }

        // For other non-Inertia responses (500 etc), let Inertia handle normally
    })

    // Also catch failed responses (network errors, server errors)
    router.on('error', () => {
        // Don't interfere — just let the user know if it's persistent
    })
}