// resources/js/lib/publicPages.ts
//
// The public marketing pages (Inertia component names). The one list both
// app.ts (which gives them the clean, sidebar-free layout) and useSiteStats
// (which counts button presses only on these pages) read from.

export const PUBLIC_PAGES: readonly string[] = [
    'Welcome',
    'ConstructorsDemo',
    'Search',
    'Faq',
    'ForTeachers',
    'TeacherAwards',
    'SwitchToCentre120',
    'TrinityExamInformation',
    'ForParents',
    'ForStudents',
    'Books',
    'Syllabus',
    'TopTen',
    'ThankYou',
    'ExamGuide',
    'ExamGuideUcas',
    'ExamGuideExpect',
    'ExamGuideDigital',
    'ExamGuideGrades',
    'ExamGuideSyllabuses',
    'ExamFees',
    'Incentives',
    'Contact',
    'About',
    'PrivacyPolicy',
    'CookiePolicy',
    'TermsOfUse',
    'ComingSoonPage',
    'Sitemap',
]

export function isPublicPage(component: string): boolean {
    return PUBLIC_PAGES.includes(component)
}
