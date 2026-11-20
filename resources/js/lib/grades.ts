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

// Every Trinity graded level in order, Initial to Grade 8, as display labels.
// The one list for the front end (the PHP list is SyllabusFacets::GRADE_ORDER);
// built from formatGrade so the labels can never drift from it.
export const TRINITY_GRADES: string[] = ['Initial', 1, 2, 3, 4, 5, 6, 7, 8].map(formatGrade)
