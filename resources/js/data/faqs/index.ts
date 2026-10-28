// resources/js/data/faqs/index.ts

// Every page FAQ, for site search. A new FAQ file goes in this list too
// (tests/Feature/FaqSearchGuardTest.php fails if one is missing). The main FAQ
// page is first, so when two pages ask the same question search shows that one.

import type { FaqPage } from '@/lib/faqs'
import faq from '@/data/faqs/faq'
import forParents from '@/data/faqs/forParents'
import forTeachers from '@/data/faqs/forTeachers'
import teacherAwards from '@/data/faqs/teacherAwards'
import incentives from '@/data/faqs/incentives'
import examFees from '@/data/faqs/examFees'
import examGuide from '@/data/faqs/examGuide'
import examGuideGrades from '@/data/faqs/examGuideGrades'
import examGuideExpect from '@/data/faqs/examGuideExpect'
import examGuideDigital from '@/data/faqs/examGuideDigital'
import examGuideUcas from '@/data/faqs/examGuideUcas'
import examGuideSyllabuses from '@/data/faqs/examGuideSyllabuses'
import switchToCentre120 from '@/data/faqs/switchToCentre120'
import contact from '@/data/faqs/contact'
import welcome from '@/data/faqs/welcome'

export const FAQ_PAGES: FaqPage[] = [
  faq,
  forParents,
  forTeachers,
  teacherAwards,
  incentives,
  examFees,
  examGuide,
  examGuideGrades,
  examGuideExpect,
  examGuideDigital,
  examGuideUcas,
  examGuideSyllabuses,
  switchToCentre120,
  contact,
  welcome,
]
