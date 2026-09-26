// resources/js/lib/sendJson.ts

import { xsrfToken } from '@/lib/utils'

// A JSON request to the app from a page that saves in the background
// (autosave, instant ticks) without an Inertia page visit, so what the user
// is typing is never replaced by a reload. `keepalive` lets a last save
// finish while the page is being left or refreshed.
export function sendJson(url: string, method: 'POST' | 'PUT' | 'DELETE', body: unknown, keepalive = false): Promise<Response> {
  return fetch(url, {
    method,
    keepalive,
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-XSRF-TOKEN': xsrfToken(),
    },
    body: JSON.stringify(body),
  })
}
