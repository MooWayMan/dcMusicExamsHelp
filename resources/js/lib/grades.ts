// resources/js/lib/grades.ts

// Trinity grades are stored three ways depending on which importer wrote the
// row: "Grade 4", a bare "4", or "Initial". Every email, page and tooltip that
// shows a grade goes through here, so nothing ever reads "Grade Grade 4" or
// "Grade Initial". The PHP twin is App\Support\Grade (used on certificates).
export function formatGrade(g: unknown): string {
  if (g === null || g === undefined) return ''
  const bare = String(g).trim().replace(/^grade\s+/i, '')
  if (bare === '') return ''
  if (bare.toLowerCase() === 'initial') return 'Initial'
  return `Grade ${bare}`
}
