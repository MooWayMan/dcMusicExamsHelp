#!/bin/bash
# Apply all changes from today's Cowork session
# Run from your musicexamshelp project root:
#   bash apply-changes.sh

set -e
echo "Applying changes..."

# ─── 1. Create useAuthConfig composable ───
mkdir -p resources/js/composables
cat > resources/js/composables/useAuthConfig.ts << 'ENDOFFILE'
// composables/useAuthConfig.ts
// Bridges auth page layout config (title/description) from Inertia's
// resolve function to AuthLayout.vue.

import { reactive } from 'vue'

export const authConfig = reactive({
    title: '',
    description: '',
})
ENDOFFILE
echo "✓ Created useAuthConfig.ts"

# ─── 2. Update app.ts ───
sed -i '' "s|import { initializeTheme } from '@/composables/useAppearance'|import { initializeTheme } from '@/composables/useAppearance'\nimport { authConfig } from '@/composables/useAuthConfig'|" resources/js/app.ts

sed -i '' '/} else if (name.startsWith('\''auth\/'\'')){/{
N
s|} else if (name.startsWith('\''auth\/'\'')){[[:space:]]*\n[[:space:]]*page.default.layout = page.default.layout || AuthLayout|} else if (name.startsWith('\''auth\/'\'')) {\
            const opt = page.default.layout\
            if (opt \&\& typeof opt === '\''object'\'' \&\& !('\''setup'\'' in opt) \&\& !('\''render'\'' in opt)) {\
                authConfig.title = (opt as any).title || '\'\''\
                authConfig.description = (opt as any).description || '\'\''\
            }\
            page.default.layout = AuthLayout|
}' resources/js/app.ts
echo "✓ Updated app.ts"

echo ""
echo "⚠️  The sed commands for app.ts are fragile. Please verify the file manually."
echo "If it looks wrong, the changes needed are described below."
echo ""
echo "STOPPING HERE - the sed approach is too error-prone for these files."
echo "Please use the patch file approach instead (see Cowork chat)."
