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

export interface PiecePlan {
  id: number
  pupil_name: string
  exam_stream: string
  instrument: string
  grade: string
  target_date: string | null
  items: PlanItem[]
  ready: number
}

export interface PlanCandidate {
  name: string
  last_exam: string
}

export type SectionLabels = Record<string, Record<PlanSection, string>>
export type Suggestions = Record<string, { technical: string[]; supporting: string[] }>
