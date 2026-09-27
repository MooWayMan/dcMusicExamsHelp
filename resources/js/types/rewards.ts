// resources/js/types/rewards.ts
// The dashboard's Rewards card, as App\Services\TeacherRewards sends it.

export interface RewardPrize {
    id: string
    who: string
    prize: string
    amount: string
    status: string
    use_by: string
}

export interface RewardQuarter {
    quarter: number
    year: number
    label: string
    badge: string | null
    certificate: string | null
    prizes: RewardPrize[]
}
