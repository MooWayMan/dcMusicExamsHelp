// resources/js/composables/useQuarterCertificateBatch.ts
import { computed, ref } from 'vue'
import { xsrfToken } from '@/lib/utils'

export interface TopScorerCert {
    name: string
    short_name: string
    certificate: string
    group: string
    band: string
    score: number
    instrument: string | null
    grade: string | null
    standalone_path: string
    download_url: string
}

export interface QuarterBatchResult {
    total: number | null
    quarter_label: string
    teachers: Record<string, number>
    download_links: Record<string, string>
    master_zip: string | null
    top_scorer_certs?: TopScorerCert[]
    top_scorer_count?: number
    from_disk?: boolean
}

interface BatchStep {
    teacher: string
    part: number
    parts: number
}

async function post<T>(url: string, body: Record<string, unknown>): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    })
    const data = await response.json().catch(() => ({}))
    if (!response.ok) {
        throw new Error(data.error ?? data.message ?? `Request failed (${response.status})`)
    }
    return data as T
}

export function useQuarterCertificateBatch() {
    const running = ref(false)
    const done = ref(0)
    const total = ref(0)
    const currentTeacher = ref('')
    const result = ref<QuarterBatchResult | null>(null)
    const error = ref<string | null>(null)

    const progress = computed(() => {
        if (!running.value) return ''
        if (total.value === 0) return 'Getting ready…'
        if (done.value === total.value) return 'Making the ZIPs…'
        return `Step ${done.value + 1} of ${total.value}: ${currentTeacher.value}`
    })

    async function run(quarter: number, year: number): Promise<QuarterBatchResult | null> {
        running.value = true
        done.value = 0
        total.value = 0
        currentTeacher.value = ''
        error.value = null

        try {
            const plan = await post<{ steps: BatchStep[] }>('/admin/certificates/batch/start', { quarter, year })
            total.value = plan.steps.length

            for (const step of plan.steps) {
                currentTeacher.value = step.parts > 1 ? `${step.teacher} (${step.part} of ${step.parts})` : step.teacher
                await post('/admin/certificates/batch/step', { quarter, year, teacher: step.teacher, part: step.part })
                done.value++
            }

            result.value = await post<QuarterBatchResult>('/admin/certificates/batch/finish', { quarter, year })
            return result.value
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Something went wrong generating the certificates.'
            return null
        } finally {
            running.value = false
        }
    }

    return { running, progress, result, error, run }
}
