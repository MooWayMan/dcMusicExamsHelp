// resources/js/lib/quarterLabel.ts
//
// "3rd Quarter 2026", as printed on certificates. The browser's one copy of
// App\Support\QuarterLabel; tests/Feature/EntryCertificatesTest.php fails on
// a third anywhere in the code.

export function quarterLabel(quarter: number, year: number | string): string {
    const suffix = ['1st', '2nd', '3rd', '4th'][quarter - 1] ?? '?'

    return `${suffix} Quarter ${year}`
}
