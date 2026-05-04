// vite.config.ts
import inertia from '@inertiajs/vite'
import { wayfinder } from '@laravel/vite-plugin-wayfinder'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                // Force the reusable design-system components into their own
                // chunks. Without this, Rolldown sometimes inlines
                // MyTextConstructor into app.js (because it's reached eagerly
                // via MarketingLayout → Navbar) while leaving
                // MyButtonConstructor in its own chunk that re-imports
                // MyTextConstructor from app.js. That creates a circular
                // import (app.js ↔ MyButtonConstructor) and on first paint
                // the imported symbol is undefined → "_ is not a function".
                manualChunks(id) {
                    if (id.includes('/components/reusables/MyTextConstructor')) {
                        return 'reusable-MyTextConstructor'
                    }
                    if (id.includes('/components/reusables/MyButtonConstructor')) {
                        return 'reusable-MyButtonConstructor'
                    }
                },
            },
        },
    },
})