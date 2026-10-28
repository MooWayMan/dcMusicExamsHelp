<!-- resources/js/components/SiteSearchBox.vue -->
<script setup lang="ts">
// The one site search box. Used on the home page, the FAQ, the phone menu and
// the search page. Pressing Enter opens /search with the words typed; on the
// search page itself (stayOnPage) it updates the address without reloading.
import { router } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import { searchUrl } from '@/composables/useSiteSearch'

const props = withDefaults(defineProps<{
  placeholder?: string
  autofocus?: boolean
  stayOnPage?: boolean
}>(), {
  placeholder: 'Search',
  autofocus: false,
  stayOnPage: false,
})

const emit = defineEmits<{ submitted: [] }>()

const query = defineModel<string>({ default: '' })

const submit = () => {
  if (props.stayOnPage) {
    router.visit(searchUrl(query.value), { preserveState: true, preserveScroll: true, replace: true })
  } else {
    router.visit(searchUrl(query.value))
    query.value = ''
  }
  emit('submitted')
}
</script>

<template>
  <form role="search" @submit.prevent="submit">
    <MyInputConstructor
      v-model="query"
      type="search"
      size="small"
      :icon="Search"
      :placeholder="placeholder"
      :autofocus="autofocus"
      aria-label="Search the site"
    />
  </form>
</template>
