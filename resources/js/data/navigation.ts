// resources/js/data/navigation.ts

export type NavigationLink = {
  name: string
  route?: string
  params?: Record<string, any>
  url?: string
  external?: boolean
}

export const mainNavigation: NavigationLink[] = [
  { name: 'Home', url: '/' },
]

// export const mainNavigation: NavigationLink[] = [
//   {
//     name: 'Home',
//     route: 'home',
//   },
//   {
//     name: 'Trinity Exams',
//     route: 'trinity.index', // create later if needed
//   },
//   {
//     name: 'Pieces',
//     route: 'pieces.index', // future
//   },
//   {
//     name: 'Guides',
//     route: 'guides.index', // future
//   },
//   {
//     name: 'Contact',
//     route: 'contact', // future
//   },
// ]