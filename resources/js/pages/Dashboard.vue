<script setup lang="ts">
import { Head, Link, Form, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { LayoutDashboard, ClipboardList, Users, GraduationCap, CheckSquare, Award, AlertCircle, Home, LogOut, Mail, MessageCircle, Info } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import { Spinner } from '@/components/ui/spinner'
import { dashboard, logout } from '@/routes'

interface ExamEntryRow {
    id: number
    candidate_number: string | null
    candidate_name: string | null
    date_of_birth: string | null
    grade: string | null
    subject_area: string | null
    delivery_method: string | null
    result: string | null
    score: number | null
    exam_date: string | null
}

const props = defineProps<{
    examEntries?: ExamEntryRow[]
    hasLinkedContact?: boolean
}>()

const handleLogout = () => {
    router.flushAll()
}

const page = usePage()
const user = computed(() => (page.props.auth as any)?.user)
const isAdmin = computed(() => user.value?.role === 'admin')
const flashSuccess = computed(() => (page.props.flash as any)?.success)

const showLinkForm = ref(false)
const entries = computed<ExamEntryRow[]>(() => props.examEntries ?? [])
const hasEntries = computed(() => entries.value.length > 0)
const correctionFormFor = ref<number | null>(null)

function toggleCorrectionForm(entryId: number) {
    correctionFormFor.value = correctionFormFor.value === entryId ? null : entryId
}

const logo = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png'

const quickLinks = [
    { title: 'Admin Dashboard', subtitle: 'Stats, orders & contacts', href: '/admin', icon: LayoutDashboard },
    { title: 'Orders', subtitle: 'View all exam orders', href: '/admin/orders', icon: ClipboardList },
    { title: 'Pending Results', subtitle: 'Weekly results checklist', href: '/admin/pending-results', icon: AlertCircle },
    { title: 'Teachers', subtitle: 'Manage teacher records', href: '/admin/teachers', icon: Users },
    { title: 'Tasks', subtitle: 'Your to-do list', href: '/admin/tasks', icon: CheckSquare },
    { title: 'Certificates', subtitle: 'Generate certificates', href: '/admin/certificates', icon: Award },
]

function resultBadgeClass(result: string | null): string {
    switch (result) {
        case 'Distinction': return 'bg-brand-success-soft text-brand-success'
        case 'Merit': return 'bg-brand-accent/10 text-brand-accent'
        case 'Pass': return 'bg-brand-surface-soft text-brand-text-soft'
        case 'Fail': return 'bg-brand-danger-soft text-brand-danger'
        default: return 'bg-brand-surface-soft text-brand-text-soft'
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
})
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col items-center p-6 sm:p-8">
        <!-- Logo and welcome -->
        <div class="mt-4 flex flex-col items-center gap-5 sm:mt-8">
            <img
                :src="logo"
                alt="musicExams.help"
                class="h-16 w-auto sm:h-20"
            />
            <MyTextConstructor variant="heading" alignment="center" spacing="none">
                <template #myTitle>Welcome back, {{ user?.name?.split(' ')[0] }}</template>
            </MyTextConstructor>
            <p class="text-base text-brand-text-soft sm:text-lg">
                Centre 120 — Trinity College London
            </p>
        </div>

        <!-- Quick links grid (admin only) -->
        <div v-if="isAdmin" class="mt-10 w-full max-w-3xl">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="link in quickLinks"
                    :key="link.href"
                    :href="link.href"
                    class="group flex items-center gap-4 rounded-xl border border-brand-border bg-brand-surface p-5 transition-all hover:border-brand-accent hover:shadow-md"
                >
                    <div class="rounded-lg bg-brand-accent/10 p-3 transition-colors group-hover:bg-brand-accent/20">
                        <component :is="link.icon" class="h-6 w-6 text-brand-accent" />
                    </div>
                    <div>
                        <p class="text-base font-semibold text-brand-text">{{ link.title }}</p>
                        <p class="text-sm text-brand-text-soft">{{ link.subtitle }}</p>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Non-admin: candidate list OR linkage form -->
        <div v-else class="mt-8 w-full max-w-5xl">
            <!-- Flash from a successful link request -->
            <div
                v-if="flashSuccess"
                class="mb-6 rounded-lg border border-brand-success bg-brand-success-soft px-4 py-3 text-base text-brand-success"
            >
                {{ flashSuccess }}
            </div>

            <!-- Local-records-only banner — sets expectations before the table -->
            <div v-if="hasEntries" class="mb-4 flex items-start gap-3 rounded-xl border border-brand-border bg-brand-surface-soft px-4 py-3 text-sm text-brand-text">
                <Info class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <p>
                    Spotted a typo in a name or a wrong date of birth?
                    Use <span class="font-semibold">Report correction</span> next to the row and we&rsquo;ll fix it on
                    musicExams.help. Note that Trinity&rsquo;s own records aren&rsquo;t affected by changes here &mdash;
                    those still need to be updated through Trinity directly.
                </p>
            </div>

            <!-- Candidates table — when the user is linked and has entries -->
            <div v-if="hasEntries" class="rounded-xl border border-brand-border bg-brand-surface">
                <div class="flex items-center justify-between border-b border-brand-border px-5 py-4">
                    <div>
                        <h2 class="text-xl font-semibold text-brand-text">Your candidates</h2>
                        <p class="text-sm text-brand-text-soft">
                            Read-only view of every Trinity exam entry on file under your email.
                        </p>
                    </div>
                    <span class="rounded-full bg-brand-surface-soft px-3 py-1 text-sm font-medium text-brand-text-soft">
                        {{ entries.length }} {{ entries.length === 1 ? 'entry' : 'entries' }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[700px] w-full text-left text-sm">
                        <thead class="border-b border-brand-border bg-brand-surface-soft text-brand-text-soft">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Candidate</th>
                                <th class="px-4 py-3 font-semibold">DOB</th>
                                <th class="px-4 py-3 font-semibold">Grade</th>
                                <th class="px-4 py-3 font-semibold">Subject</th>
                                <th class="px-4 py-3 font-semibold">Delivery</th>
                                <th class="px-4 py-3 font-semibold">Exam date</th>
                                <th class="px-4 py-3 font-semibold">Result</th>
                                <th class="px-4 py-3 text-center font-semibold">Score</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-border">
                            <template v-for="row in entries" :key="row.id">
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="text-base font-medium text-brand-text">{{ row.candidate_name ?? '—' }}</div>
                                        <div v-if="row.candidate_number" class="text-xs text-brand-text-soft">
                                            {{ row.candidate_number }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-brand-text">{{ row.date_of_birth ?? '—' }}</td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ row.grade ?? '—' }}</td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ row.subject_area ?? '—' }}</td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ row.delivery_method ?? '—' }}</td>
                                    <td class="px-4 py-3 text-brand-text-soft">{{ row.exam_date ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="row.result" class="rounded-full px-2 py-0.5 text-sm font-medium" :class="resultBadgeClass(row.result)">
                                            {{ row.result }}
                                        </span>
                                        <span v-else class="text-brand-text-soft">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span v-if="row.score !== null && row.score !== undefined" class="font-medium text-brand-text">{{ row.score }}</span>
                                        <span v-else class="text-brand-text-soft">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-brand-border bg-brand-surface-soft px-3 py-1.5 text-xs font-medium text-brand-text-soft transition-colors hover:bg-brand-accent/10 hover:text-brand-accent"
                                            @click="toggleCorrectionForm(row.id)"
                                        >
                                            <MessageCircle class="h-3.5 w-3.5" />
                                            {{ correctionFormFor === row.id ? 'Cancel' : 'Report correction' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="correctionFormFor === row.id" class="bg-brand-surface-soft/40">
                                    <td colspan="9" class="px-5 py-4">
                                        <Form
                                            :action="`/dashboard/entries/${row.id}/correction-request`"
                                            method="post"
                                            :reset-on-success="['note']"
                                            v-slot="{ errors, processing }"
                                            class="grid gap-3"
                                            @success="correctionFormFor = null"
                                        >
                                            <label class="text-sm font-semibold text-brand-text">
                                                What needs correcting for {{ row.candidate_name }}?
                                            </label>
                                            <textarea
                                                name="note"
                                                rows="3"
                                                required
                                                placeholder="e.g. Spelling should be Freddie not Fred. Date of birth should be 14/05/2014."
                                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                                            ></textarea>
                                            <p v-if="errors.note" class="text-sm text-brand-danger">{{ errors.note }}</p>
                                            <div class="flex gap-2">
                                                <MyButtonConstructor
                                                    type="submit"
                                                    variant="primary"
                                                    size="small"
                                                    :disabled="processing"
                                                >
                                                    <Spinner v-if="processing" class="mr-2" />
                                                    Send correction
                                                </MyButtonConstructor>
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-brand-border bg-brand-surface px-3 py-1.5 text-sm font-medium text-brand-text-soft transition-colors hover:bg-brand-surface-soft"
                                                    @click="correctionFormFor = null"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </Form>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Linkage path — when no entries match the user's email -->
            <div v-else class="rounded-xl border border-brand-border bg-brand-surface p-6 sm:p-8">
                <div class="flex flex-col items-start gap-4">
                    <h2 class="text-2xl font-semibold text-brand-text">No candidates linked yet</h2>
                    <p class="text-base text-brand-text-soft">
                        We couldn&rsquo;t find any Trinity exam entries under your registered email
                        (<span class="font-medium text-brand-text">{{ user?.email }}</span>).
                    </p>
                    <p class="text-base text-brand-text-soft">
                        If you used a different email when applying through Trinity, the easiest fix is to log out and
                        sign up again with that email. Or, tell us which email you used on Trinity and we&rsquo;ll
                        link your account.
                    </p>

                    <div class="mt-2 flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-brand-accent px-4 py-2 text-sm font-semibold text-brand-text-inverse transition-colors hover:opacity-90"
                            @click="showLinkForm = !showLinkForm"
                        >
                            <Mail class="h-4 w-4" />
                            {{ showLinkForm ? 'Hide form' : 'Tell us your Trinity email' }}
                        </button>
                        <Link
                            href="/"
                            class="inline-flex items-center gap-2 rounded-lg border border-brand-border bg-brand-surface px-4 py-2 text-sm font-semibold text-brand-text transition-colors hover:bg-brand-surface-soft"
                        >
                            <Home class="h-4 w-4" />
                            Back to home
                        </Link>
                        <Link
                            :href="logout()"
                            @click="handleLogout"
                            as="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-brand-border bg-brand-surface px-4 py-2 text-sm font-semibold text-brand-text transition-colors hover:bg-brand-surface-soft"
                        >
                            <LogOut class="h-4 w-4" />
                            Log out
                        </Link>
                    </div>

                    <Form
                        v-if="showLinkForm"
                        action="/dashboard/link-request"
                        method="post"
                        :reset-on-success="['alternative_email', 'note']"
                        v-slot="{ errors, processing }"
                        class="mt-4 grid w-full max-w-xl gap-4"
                    >
                        <MyInputConstructor
                            type="email"
                            name="alternative_email"
                            label="Email used on your Trinity application"
                            placeholder="trinity-email@example.com"
                            size="small"
                            required
                            :error="errors.alternative_email"
                        />
                        <div>
                            <label class="mb-2 block text-lg font-semibold text-brand-text sm:text-xl">Anything else? (optional)</label>
                            <textarea
                                name="note"
                                rows="3"
                                placeholder="e.g. school name, candidate names — anything that helps us match you"
                                class="w-full rounded-lg border border-brand-border bg-brand-surface px-4 py-3 text-base text-brand-text placeholder:text-brand-text-soft focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            ></textarea>
                            <p v-if="errors.note" class="mt-1 text-sm text-brand-danger">{{ errors.note }}</p>
                        </div>
                        <MyButtonConstructor
                            type="submit"
                            variant="primary"
                            size="medium"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" class="mr-2" />
                            Send to musicExams.help
                        </MyButtonConstructor>
                    </Form>
                </div>
            </div>
        </div>
    </div>
</template>
