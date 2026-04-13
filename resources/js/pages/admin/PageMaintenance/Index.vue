<!-- resources/js/pages/admin/PageMaintenance/Index.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Construction, ToggleLeft, ToggleRight, Save } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { usePageAnimation } from '@/composables/usePageAnimation'

interface MaintenancePage {
  id: number
  page_slug: string
  page_name: string
  is_active: boolean
  message: string
}

const props = defineProps<{
  pages: MaintenancePage[]
}>()

const { animClass } = usePageAnimation()

// Track editable messages locally
const editingId = ref<number | null>(null)
const editMessage = ref('')

function togglePage(page: MaintenancePage) {
  router.patch(`/admin/page-maintenance/${page.id}/toggle`, {}, {
    preserveScroll: true,
  })
}

function startEditing(page: MaintenancePage) {
  editingId.value = page.id
  editMessage.value = page.message
}

function cancelEditing() {
  editingId.value = null
  editMessage.value = ''
}

function saveMessage(page: MaintenancePage) {
  router.patch(`/admin/page-maintenance/${page.id}/message`, {
    message: editMessage.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      editingId.value = null
      editMessage.value = ''
    },
  })
}
</script>

<template>
  <PageHeader
    title="Page Maintenance"
    subtitle="Toggle data-heavy pages into maintenance mode when you need to fix data issues"
    eyebrow="Site Controls"
    :showIcon="true"
  >
    <template #actions>
      <div class="flex items-center gap-2 text-sm text-brand-text-soft">
        <Construction class="h-4 w-4" />
        Visitors see a friendly "back later" message
      </div>
    </template>
  </PageHeader>

  <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
    <div :class="animClass('fade-up', 1)" class="space-y-4">
      <div
        v-for="page in pages"
        :key="page.id"
        class="overflow-hidden rounded-xl border-2 transition-all duration-300"
        :class="page.is_active
          ? 'border-brand-danger bg-brand-danger-soft/20'
          : 'border-brand-border bg-brand-surface'"
      >
        <!-- Header row -->
        <div class="flex items-center justify-between px-5 py-4 sm:px-6">
          <div class="flex items-center gap-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-lg"
              :class="page.is_active ? 'bg-brand-danger/20' : 'bg-brand-surface-soft'"
            >
              <Construction
                class="h-5 w-5"
                :class="page.is_active ? 'text-brand-danger' : 'text-brand-text-soft'"
              />
            </div>
            <div>
              <h3 class="text-lg font-bold text-brand-text sm:text-xl">
                {{ page.page_name }}
              </h3>
              <p class="text-sm text-brand-text-soft">
                /{{ page.page_slug }}
              </p>
            </div>
          </div>

          <button
            @click="togglePage(page)"
            class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all"
            :class="page.is_active
              ? 'bg-brand-danger/20 text-brand-danger hover:bg-brand-danger/30'
              : 'bg-brand-surface-soft text-brand-text-soft hover:bg-brand-accent/10 hover:text-brand-accent'"
          >
            <component
              :is="page.is_active ? ToggleRight : ToggleLeft"
              class="h-6 w-6"
            />
            {{ page.is_active ? 'Maintenance ON' : 'Live' }}
          </button>
        </div>

        <!-- Status banner when active -->
        <div v-if="page.is_active" class="border-t border-brand-danger/20 bg-brand-danger-soft/10 px-5 py-3 sm:px-6">
          <p class="text-sm font-medium text-brand-danger">
            Visitors currently see the maintenance message below instead of the page content.
          </p>
        </div>

        <!-- Message section -->
        <div class="border-t border-brand-border/50 px-5 py-4 sm:px-6">
          <div v-if="editingId === page.id" class="space-y-3">
            <label class="text-sm font-semibold text-brand-text">Maintenance message:</label>
            <textarea
              v-model="editMessage"
              rows="3"
              class="w-full rounded-lg border border-brand-border bg-brand-surface px-3 py-2 text-base text-brand-text focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/20"
              maxlength="500"
            />
            <div class="flex gap-2">
              <MyButtonConstructor size="small" variant="primary" :icon="Save" @clicked="saveMessage(page)">
                Save
              </MyButtonConstructor>
              <MyButtonConstructor size="small" variant="ghost" @clicked="cancelEditing">
                Cancel
              </MyButtonConstructor>
            </div>
          </div>
          <div v-else class="flex items-start justify-between gap-4">
            <p class="text-sm leading-relaxed text-brand-text-soft">
              <span class="font-semibold text-brand-text">Message:</span>
              {{ page.message }}
            </p>
            <button
              @click="startEditing(page)"
              class="shrink-0 text-sm font-semibold text-brand-accent transition hover:opacity-70"
            >
              Edit
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Help text -->
    <div :class="animClass('fade-up', 2)" class="mt-8 rounded-xl border border-brand-border bg-brand-surface-soft p-5">
      <h4 class="text-base font-bold text-brand-text">How it works</h4>
      <ul class="mt-2 space-y-1 text-sm text-brand-text-soft">
        <li>Toggle a page to <strong>Maintenance ON</strong> and visitors will see a friendly "back later" message.</li>
        <li>As an admin, you'll still see the real page content (with a red warning banner at the top).</li>
        <li>The rest of the site stays fully live — only the toggled pages are affected.</li>
        <li>Flip it back to <strong>Live</strong> when you're done fixing things.</li>
      </ul>
    </div>
  </div>
</template>
