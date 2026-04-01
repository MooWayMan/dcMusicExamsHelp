// composables/useAuthConfig.ts
// Bridges auth page layout config (title/description) from Inertia's
// resolve function to AuthLayout.vue.  The resolve function extracts
// the object set via defineOptions({ layout: { title, description } })
// and stores it here so AuthLayout can read it reactively.

import { reactive } from 'vue'

export const authConfig = reactive({
    title: '',
    description: '',
})
