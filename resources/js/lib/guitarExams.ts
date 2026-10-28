// resources/js/lib/guitarExams.ts

// Trinity has three guitar exams on two booking systems, and teachers mix them
// up (Paul, 28 Oct 2026: a teacher entered "Acoustic Guitar, Grade 1 classical").
// The Syllabuses page, the FAQ and site search all read the difference from
// here, so the instrument rule below is stated once.

export const SYLLABUS_URL = {
  rockPopGrades: 'https://www.trinitycollege.com/qualifications/music/rock-and-pop/grades',
  classicalGuitar: 'https://www.trinitycollege.com/qualifications/music/grade-exams/classical-guitar',
  acousticGuitar: 'https://www.trinitycollege.com/qualifications/music/grade-exams/acoustic-guitar',
} as const

// Trinity's Rock & Pop Guitar syllabus, "Types of instrument".
export const ROCK_POP_ACOUSTIC_RULE =
  'Initial to Grade 2 can be played on an acoustic, classical, electro-acoustic or electric guitar. From Grade 3 it must be electric or electro-acoustic, although an acoustic can be used for an own-choice song that suits it.'

export interface GuitarExam {
  title: string
  bookedOn: string
  detail: string
  syllabusUrl: string
}

// bookedOn matches the option names in the Book Your Exam menu (BookingModal).
export const GUITAR_EXAMS: GuitarExam[] = [
  {
    title: 'Rock & Pop Guitar',
    bookedOn: 'Book: Face-to-Face Rock & Pop',
    detail: `Rock and pop songs, mostly on electric guitar. ${ROCK_POP_ACOUSTIC_RULE}`,
    syllabusUrl: SYLLABUS_URL.rockPopGrades,
  },
  {
    title: 'Acoustic Guitar',
    bookedOn: 'Book: Face-to-Face Classical & Jazz',
    detail: 'Steel-string guitar. Folk, pop, blues and country-style pieces, played with a plectrum or fingerstyle.',
    syllabusUrl: SYLLABUS_URL.acousticGuitar,
  },
  {
    title: 'Classical Guitar',
    bookedOn: 'Book: Face-to-Face Classical & Jazz',
    detail: 'Nylon-string guitar. Classical repertoire, played fingerstyle.',
    syllabusUrl: SYLLABUS_URL.classicalGuitar,
  },
]

export const GUITAR_EXAMS_ANCHOR = '/exam-guide/syllabuses#guitar'

export const GUITAR_EXAMS_SUMMARY =
  'Rock & Pop Guitar is booked through the Rock & Pop system (MyTrinity). Acoustic Guitar and Classical Guitar are both booked through the Classical & Jazz system, and they are different exams with different pieces.'
