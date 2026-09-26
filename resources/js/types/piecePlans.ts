// resources/js/types/piecePlans.ts

// Shapes served by App\Services\PiecePlans for the Piece tracker.

export type PlanSection = 'piece' | 'technical' | 'supporting'

export interface PlanItem {
  id: number | null
  section: PlanSection
  syllabus_piece_id: number | null
  label: string
  percent: number
  book: string | null
}

/** A pupil's mark out of maxScore for a syllabus piece they have heard. */
// A type, not an interface: it is sent in an Inertia request body, and
// only a type alias satisfies Inertia's index-signature payload type.
export type PlanRating = {
  syllabus_piece_id: number
  score: number
}

export interface PiecePlan {
  id: number
  pupil_name: string
  exam_stream: string
  instrument: string
  grade: string
  target_date: string | null
  items: PlanItem[]
  ratings: PlanRating[]
  ready: number
}

export interface PlanCandidate {
  name: string
  last_exam: string
}

export type SectionLabels = Record<string, Record<PlanSection, string>>
export type Suggestions = Record<string, { technical: string[]; supporting: string[] }>
