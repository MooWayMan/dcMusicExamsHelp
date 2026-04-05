// resources/js/ssr.ts
import { createSSRApp, h, type DefineComponent } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createInertiaApp } from '@inertiajs/vue3'
import createServer from '@inertiajs/vue3/server'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'

import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import { authConfig } from '@/composables/useAuthConfig'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => title || appName,

        resolve: async (name) => {
            const resolvedPage = await resolvePageComponent(
                `./pages/${name}.vue`,
                import.meta.glob<DefineComponent>('./pages/**/*.vue')
            )

            const publicPages = ['Welcome', 'ConstructorsDemo', 'Faq', 'ForTeachers', 'FaberDiscounts', 'ForParents', 'ForStudents', 'ThankYou', 'ExamGuide', 'ExamGuideUcas', 'ExamGuideExpect', 'ExamGuideDigital', 'ExamGuideGrades', 'ExamFees', 'PrivacyPolicy', 'CookiePolicy']

            if (publicPages.includes(name)) {
                resolvedPage.default.layout = undefined
            } else if (name.startsWith('auth/')) {
                const opt = resolvedPage.default.layout
                if (opt && typeof opt === 'object' && !('setup' in opt) && !('render' in opt)) {
                    authConfig.title = (opt as any).title || ''
                    authConfig.description = (opt as any).description || ''
                }
                resolvedPage.default.layout = AuthLayout
            } else if (name.startsWith('settings/')) {
                resolvedPage.default.layout = resolvedPage.default.layout || [AppLayout, SettingsLayout]
            } else if (!resolvedPage.default.layout) {
                resolvedPage.default.layout = AppLayout
            }

            return resolvedPage
        },

        setup({ App, props, plugin }) {
            return createSSRApp({
                render: () => h(App, props),
            })
                .use(plugin)
                .use(ZiggyVue)
        },
    })
)