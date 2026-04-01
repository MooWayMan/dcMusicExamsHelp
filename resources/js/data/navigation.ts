// resources/js/data/navigation.ts

export type NavigationLink = {
  name: string
  route?: string
  routeName?: string
  params?: Record<string, any>
  url?: string
  external?: boolean
}

export const mainNavigation: NavigationLink[] = [
  { name: 'Home', url: '/' },
  { name: 'Why Use This Page', url: '/#why' },
  { name: 'Incentives', url: '/#incentives' },
  { name: 'FAQ', url: '/#faq' },
  { name: 'Book Your Exam', url: 'https://booking.trinitycollege.com/?larCode=120', external: true },
]