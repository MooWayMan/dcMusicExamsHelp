// resources/js/composables/useSiteSearch.ts

// Where a site search goes. Every search box (home page, FAQ, menu, the search
// page itself) builds its address here, through SiteSearchBox.
export function searchUrl(query: string): string {
  const q = query.trim()
  return '/search' + (q ? '?q=' + encodeURIComponent(q) : '')
}
