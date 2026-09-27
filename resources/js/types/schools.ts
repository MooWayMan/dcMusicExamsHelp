// resources/js/types/schools.ts

// Shapes served by App\Services\SchoolLinks for the admin school pages.

/** Someone who works (or worked) at a school. */
export interface SchoolTeacher {
  id: number
  name: string
  former: boolean
}

/** A pickable teacher or instrument. */
export interface SchoolOption {
  id: number
  name: string
}
