// resources/js/lib/minimumWage.ts
//
// Picks the UK National Living Wage (aged 21 and over) in force on a date.
// The rates themselves are NOT written here: the server reads them from
// GOV.UK (App\Services\MinimumWage) and hands them to the page.

export interface WageRate {
    from: string
    rate: string
}

/** The rate in force on a YYYY-MM-DD date (today when blank), or null if none is known. */
export function minimumWageOn(rates: WageRate[], date: string | null | undefined): string | null {
    const day = date || new Date().toISOString().slice(0, 10)
    const inForce = rates.filter((r) => r.from <= day).sort((a, b) => a.from.localeCompare(b.from))
    return inForce.length ? inForce[inForce.length - 1].rate : null
}
