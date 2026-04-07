// resources/js/app.ts
import '../css/app.css'
import '../css/fonts.css'

import { createApp, h, type DefineComponent, defineAsyncComponent } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'

import { initializeTheme } from '@/composables/useAppearance'
import { authConfig } from '@/composables/useAuthConfig'

// Layouts
import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import MarketingLayout from '@/layouts/MarketingLayout.vue'

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
        
        if (['Welcome', 'ConstructorsDemo', 'Faq', 'ForTeachers', 'FaberDiscounts', 'TeacherAwards', 'ForParents', 'ForStudents', 'ThankYou', 'ExamGuide', 'ExamGuideUcas', 'ExamGuideExpect', 'ExamGuideDigital', 'ExamGuideGrades', 'ExamFees', 'Contact', 'About', 'PrivacyPolicy', 'CookiePolicy'].includes(name)) {
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
            page.default.layout = page.default.layout || [AppLayout, SettingsLayout]

        } else {
            // Default app pages
            page.default.layout = page.default.layout || AppLayout
        }

        return page
    },

    setup({ el, App, props, plugin }) {
        const CookieConsent = defineAsyncComponent(() => import('@/components/CookieConsent.vue'))
        const NewsletterPopup = defineAsyncComponent(() => import('@/components/NewsletterPopup.vue'))

        createApp({
            render: () => h('div', [
                h(App, props),
                h(CookieConsent),
                h(NewsletterPopup),
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