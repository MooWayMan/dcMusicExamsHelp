// resources/js/app.ts
import '../css/app.css'
import '../css/fonts.css'

import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'

import { initializeTheme } from '@/composables/useAppearance'

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
        
        if (name === 'Welcome' || name === 'ConstructorsDemo') {
            // Landing page → clean marketing layout (no breadcrumbs)
    page.default.layout = undefined

        } else if (name.startsWith('auth/')) {
            page.default.layout = page.default.layout || AuthLayout

        } else if (name.startsWith('settings/')) {
            page.default.layout = page.default.layout || [AppLayout, SettingsLayout]

        } else {
            // Default app pages
            page.default.layout = page.default.layout || AppLayout
        }

        return page
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
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
}