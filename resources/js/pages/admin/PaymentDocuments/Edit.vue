<!-- resources/js/pages/admin/PaymentDocuments/Edit.vue -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, FileText, Plus, Save, Trash2, ListPlus } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MySelectConstructor from '@/components/reusables/MySelectConstructor.vue'
import MyTextareaConstructor from '@/components/reusables/MyTextareaConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyCardConstructor from '@/components/reusables/MyCardConstructor.vue'
import WhatForPicker from '@/components/paymentdocuments/WhatForPicker.vue'
import { TIME_OPTIONS, hoursBetween } from '@/lib/workTimes'
import { minimumWageOn, type WageRate } from '@/lib/minimumWage'

interface Line {
    description: string
    line_date: string
    start_time: string
    end_time: string
    detail: string
    quantity: string | number
    unit_price: string | number
}

interface LinePayload {
    description: string
    line_date: string | null
    start_time: string | null
    end_time: string | null
    detail: string | null
    quantity: string | number
    unit_price: string | number
}

interface Totals {
    total: string
    received: string | null
    balance: string | null
    balance_label: string | null
}

interface DocumentPayload {
    id: number
    kind: 'payment' | 'receipt'
    number: string
    issued_on: string | null
    title: string | null
    to_name: string
    to_detail: string | null
    order_id: number | null
    method: string | null
    paid_on: string | null
    amount_received: string | null
    reference: string | null
    notes: string | null
    lines: LinePayload[]
    totals: Totals
}

const props = defineProps<{
    document: DocumentPayload | null
    kind: 'payment' | 'receipt'
    suggestedNumber: string
    methods: string[]
    types: string[]
    orders: { id: number; label: string }[]
    minimumWages: WageRate[]
}>()

const isReceipt = computed(() => props.kind === 'receipt')
const today = new Date().toISOString().slice(0, 10)

function blankLine(description = ''): Line {
    return { description, line_date: '', start_time: '', end_time: '', detail: '', quantity: '1', unit_price: '' }
}

function toLine(l: LinePayload): Line {
    return { ...l, line_date: l.line_date ?? '', start_time: l.start_time ?? '', end_time: l.end_time ?? '', detail: l.detail ?? '' }
}

function hasTimes(line: Line): boolean {
    return hoursBetween(line.start_time, line.end_time) !== null
}

function wageFor(line: Line): string | null {
    return minimumWageOn(props.minimumWages, line.line_date)
}

function wageDay(line: Line): string {
    return line.line_date ? `on ${line.line_date.split('-').reverse().join('/')}` : 'today'
}

function paidMinimumWage(line: Line): boolean {
    const rate = wageFor(line)
    return rate !== null && Number(line.unit_price) === Number(rate)
}

function useMinimumWage(line: Line) {
    const rate = wageFor(line)
    if (rate) line.unit_price = rate
}

function timesChanged(line: Line) {
    const hours = hoursBetween(line.start_time, line.end_time)
    if (!hours) return
    line.quantity = hours
    if (!line.unit_price || Number(line.unit_price) === 0) useMinimumWage(line)
}

const form = useForm({
    kind: props.kind,
    number: props.document?.number ?? props.suggestedNumber,
    issued_on: props.document?.issued_on ?? today,
    title: props.document?.title ?? '',
    to_name: props.document?.to_name ?? '',
    to_detail: props.document?.to_detail ?? '',
    order_id: props.document?.order_id ?? null,
    method: props.document?.method ?? '',
    paid_on: props.document?.paid_on ?? '',
    amount_received: props.document?.amount_received ?? '',
    reference: props.document?.reference ?? '',
    notes: props.document?.notes ?? '',
    lines: props.document?.lines.length ? props.document.lines.map(toLine) : [blankLine()],
})

const methodOptions = computed(() => props.methods.map((m) => ({ value: m, label: m })))

// A payment line starts as whatever the document is for, and follows it when
// "What for" changes, unless the line has been given its own type.
const lineTypes = computed(() => (form.title && !props.types.includes(form.title) ? [...props.types, form.title] : props.types))

watch(
    () => form.title,
    (now, before) => {
        if (isReceipt.value) return
        for (const line of form.lines) {
            if (line.description === '' || line.description === before) line.description = now
        }
    },
)
const orderOptions = computed(() => props.orders.map((o) => ({ value: o.id, label: o.label })))

const heading = computed(() => {
    const type = isReceipt.value ? 'Receipt' : 'Payment advice'
    return props.document ? `${type} ${props.document.number}` : `New ${type.toLowerCase()}`
})

function addLine() {
    form.lines.push(blankLine(isReceipt.value ? '' : form.title))
}

function removeLine(index: number) {
    if (form.lines.length > 1) form.lines.splice(index, 1)
}

const filling = ref(false)
const fillError = ref('')

async function fillFromOrder() {
    if (!form.order_id) return
    filling.value = true
    fillError.value = ''
    try {
        const res = await fetch(`/admin/payment-documents/order-lines/${form.order_id}`, { headers: { Accept: 'application/json' } })
        if (!res.ok) throw new Error()
        const data = (await res.json()) as { lines: LinePayload[] }
        if (data.lines.length === 0) {
            fillError.value = 'That order has no candidates in the app yet.'
            return
        }
        form.lines = data.lines.map(toLine)
    } catch {
        fillError.value = 'Could not read that order.'
    } finally {
        filling.value = false
    }
}

// Save is only live when something has changed (a new document can always be
// saved). After a save, what is on screen becomes the new "unchanged".
const canSave = computed(() => !form.processing && (!props.document || form.isDirty))

// The PDF always shows what is SAVED, so "View PDF" saves any changes first.
// The tab is opened straight away, while the click still counts as the
// person's own (a tab opened after the save would be blocked as a pop-up),
// and pointed at the PDF once the save has gone through.
function save(viewPdf = false) {
    if (viewPdf && props.document && !form.isDirty) {
        window.open(`/admin/payment-documents/${props.document.id}/pdf`, '_blank')
        return
    }

    const tab = viewPdf ? window.open('', '_blank') : null
    const options = {
        preserveScroll: true,
        onSuccess: (page: { props: Record<string, unknown> }) => {
            form.defaults()
            const saved = page.props.document as DocumentPayload | null
            if (tab && saved) tab.location.href = `/admin/payment-documents/${saved.id}/pdf`
        },
        onError: () => tab?.close(),
    }

    if (props.document) {
        form.put(`/admin/payment-documents/${props.document.id}`, options)
    } else {
        form.post('/admin/payment-documents', options)
    }
}

const confirmingDelete = ref(false)

function destroy() {
    if (props.document) router.delete(`/admin/payment-documents/${props.document.id}`)
}
</script>

<template>
    <div class="mx-auto w-full max-w-screen-lg px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-4">
            <Link href="/admin/payment-documents">
                <MyButtonConstructor variant="ghost" size="small" :icon="ArrowLeft">All payment documents</MyButtonConstructor>
            </Link>
        </div>

        <PageHeader
            :title="heading"
            eyebrow="Admin"
            size="compact"
            :subtitle="isReceipt ? 'Money paid to you for exam fees, what it covered, and the balance either way.' : 'Money you paid out on Trinity\'s behalf, such as venue rent or a steward.'"
        >
            <template v-if="document" #actions>
                <a :href="`/admin/payment-documents/${document.id}/pdf`" target="_blank" rel="noopener">
                    <MyButtonConstructor variant="primary" :icon="FileText">Open PDF</MyButtonConstructor>
                </a>
            </template>
        </PageHeader>

        <form class="mt-6 flex flex-col gap-6" @submit.prevent="save()">
            <MyCardConstructor title="Details" padding="large">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <MyInputConstructor v-model="form.number" size="compact" label="Number" :error="form.errors.number" required />
                    <MyInputConstructor v-model="form.issued_on" type="date" size="compact" label="Date" :error="form.errors.issued_on" required />
                    <WhatForPicker v-model="form.title" :types="types" label="What for" :error="form.errors.title" />
                    <MyInputConstructor
                        v-model="form.to_name"
                        size="compact"
                        :label="isReceipt ? 'Received from' : 'Paid to'"
                        :error="form.errors.to_name"
                        required
                    />
                    <div class="sm:col-span-2">
                        <MyTextareaConstructor v-model="form.to_detail" size="compact" label="Address or email (optional)" :rows="2" :error="form.errors.to_detail" />
                    </div>
                </div>
            </MyCardConstructor>

            <MyCardConstructor :title="isReceipt ? 'Payment received' : 'How it was paid'" padding="large">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <MySelectConstructor v-model="form.method" :options="methodOptions" size="small" label="Method" placeholder="Choose…" />
                    <MyInputConstructor v-model="form.paid_on" type="date" size="compact" :label="isReceipt ? 'Received on' : 'Paid on'" :error="form.errors.paid_on" />
                    <MyInputConstructor
                        v-if="isReceipt"
                        v-model="form.amount_received"
                        type="number"
                        size="compact"
                        label="Amount received (£)"
                        :error="form.errors.amount_received"
                    />
                    <MyInputConstructor v-model="form.reference" size="compact" label="Reference (optional)" :error="form.errors.reference" />
                </div>
            </MyCardConstructor>

            <MyCardConstructor v-if="isReceipt" title="Trinity order" padding="large">
                <div class="flex flex-col gap-3">
                    <MySelectConstructor v-model="form.order_id" :options="orderOptions" size="small" label="Order this receipt is for (optional)" placeholder="No order" />
                    <div>
                        <MyButtonConstructor type="button" variant="outline" size="small" :icon="ListPlus" :disabled="!form.order_id || filling" @click="fillFromOrder">
                            Fill lines from this order
                        </MyButtonConstructor>
                    </div>
                    <div v-if="fillError" class="text-sm">
                        <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">{{ fillError }}</MyTextConstructor>
                    </div>
                </div>
            </MyCardConstructor>

            <MyCardConstructor title="Lines" padding="large">
                <div class="flex flex-col gap-4">
                    <div v-for="(line, i) in form.lines" :key="i" class="grid grid-cols-2 gap-3 rounded-lg border border-brand-border p-3 sm:grid-cols-12">
                        <div class="col-span-2 sm:col-span-8">
                            <WhatForPicker
                                v-if="!isReceipt"
                                v-model="line.description"
                                :types="lineTypes"
                                label="Description"
                                :error="form.errors[`lines.${i}.description`]"
                            />
                            <MyInputConstructor v-else v-model="line.description" size="compact" label="Description" :error="form.errors[`lines.${i}.description`]" />
                        </div>
                        <div class="col-span-2 sm:col-span-4">
                            <MyInputConstructor v-model="line.detail" size="compact" label="Note (optional)" :placeholder="isReceipt ? '' : 'e.g. C&J, R&P'" />
                        </div>
                        <div :class="isReceipt ? 'col-span-2 sm:col-span-4' : 'col-span-2 sm:col-span-3'">
                            <MyInputConstructor v-model="line.line_date" type="date" size="compact" label="Date" />
                        </div>
                        <div v-if="!isReceipt" class="sm:col-span-2">
                            <MySelectConstructor v-model="line.start_time" :options="TIME_OPTIONS" size="small" label="Start" placeholder="–" @update:model-value="timesChanged(line)" />
                        </div>
                        <div v-if="!isReceipt" class="sm:col-span-2">
                            <MySelectConstructor v-model="line.end_time" :options="TIME_OPTIONS" size="small" label="End" placeholder="–" @update:model-value="timesChanged(line)" />
                        </div>
                        <div :class="isReceipt ? 'sm:col-span-3' : 'sm:col-span-2'">
                            <MyInputConstructor
                                v-model="line.quantity"
                                type="number"
                                size="compact"
                                :label="isReceipt ? 'Qty' : hasTimes(line) ? 'Hours' : 'Qty / hours'"
                                :readonly="hasTimes(line)"
                                :error="form.errors[`lines.${i}.quantity`]"
                            />
                        </div>
                        <div :class="isReceipt ? 'sm:col-span-4' : 'sm:col-span-2'">
                            <MyInputConstructor v-model="line.unit_price" type="number" size="compact" :label="isReceipt ? 'Amount (£)' : 'Rate (£)'" :error="form.errors[`lines.${i}.unit_price`]" />
                        </div>
                        <div class="flex items-end sm:col-span-1">
                            <MyButtonConstructor type="button" variant="outline" size="small" :icon="Trash2" :disabled="form.lines.length === 1" aria-label="Remove line" @click="removeLine(i)" />
                        </div>
                        <div v-if="form.errors[`lines.${i}.end_time`]" class="col-span-2 text-sm sm:col-span-12">
                            <MyTextConstructor bodyVariant="inherit" textColor="text-brand-danger" spacing="none">{{ form.errors[`lines.${i}.end_time`] }}</MyTextConstructor>
                        </div>
                        <div v-if="line.start_time || line.end_time" class="col-span-2 flex flex-wrap items-center gap-3 sm:col-span-12">
                            <div class="text-sm">
                                <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">
                                    <template v-if="!wageFor(line)">No minimum wage found {{ wageDay(line) }}.</template>
                                    <template v-else-if="paidMinimumWage(line)">Paid at the minimum wage for 21 and over {{ wageDay(line) }}: £{{ wageFor(line) }} (GOV.UK)</template>
                                    <template v-else>Minimum wage for 21 and over {{ wageDay(line) }}: £{{ wageFor(line) }} (GOV.UK)</template>
                                </MyTextConstructor>
                            </div>
                            <MyButtonConstructor
                                v-if="wageFor(line) && !paidMinimumWage(line)"
                                type="button"
                                variant="outline"
                                size="small"
                                @click="useMinimumWage(line)"
                            >
                                Use minimum wage
                            </MyButtonConstructor>
                        </div>
                    </div>
                    <div>
                        <MyButtonConstructor type="button" variant="outline" size="small" :icon="Plus" @click="addLine">Add line</MyButtonConstructor>
                    </div>
                </div>
            </MyCardConstructor>

            <MyCardConstructor title="Notes (printed on the document)" padding="large">
                <MyTextareaConstructor v-model="form.notes" size="compact" :rows="3" :error="form.errors.notes" />
            </MyCardConstructor>

            <MyCardConstructor title="Totals" padding="large">
                <MyTextConstructor v-if="!document" variant="muted">
                    <template #myPara>Totals are worked out when you save.</template>
                </MyTextConstructor>
                <div v-else class="flex flex-col gap-2">
                    <MyTextConstructor variant="muted">
                        <template #myPara>Total: {{ document.totals.total }}</template>
                    </MyTextConstructor>
                    <MyTextConstructor v-if="document.totals.received" variant="muted">
                        <template #myPara>Received: {{ document.totals.received }}</template>
                    </MyTextConstructor>
                    <MyTextConstructor v-if="document.totals.balance_label" variant="muted">
                        <template #myPara>{{ document.totals.balance_label }}<span v-if="document.totals.balance !== '£0.00'">: {{ document.totals.balance }}</span></template>
                    </MyTextConstructor>
                </div>
            </MyCardConstructor>

            <div class="flex flex-wrap items-center gap-3">
                <MyButtonConstructor type="submit" variant="primary" :icon="Save" :disabled="!canSave">Save</MyButtonConstructor>
                <MyButtonConstructor type="button" variant="outline" :icon="FileText" :disabled="form.processing" @click="save(true)">View PDF</MyButtonConstructor>
                <MyButtonConstructor v-if="document" type="button" variant="outline" :icon="Trash2" :disabled="confirmingDelete" @click="confirmingDelete = true">Delete</MyButtonConstructor>
                <div v-if="form.processing" class="text-sm">
                    <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">Saving…</MyTextConstructor>
                </div>
                <div v-else-if="form.hasErrors" class="text-sm">
                    <MyTextConstructor bodyVariant="inherit" textColor="text-brand-danger" spacing="none">Not saved: check the boxes marked in red.</MyTextConstructor>
                </div>
                <div v-else-if="form.isDirty && document" class="text-sm">
                    <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">Unsaved changes</MyTextConstructor>
                </div>
                <div v-else-if="form.recentlySuccessful" class="text-sm">
                    <MyTextConstructor bodyVariant="inherit" textColor="text-brand-success" spacing="none">Saved</MyTextConstructor>
                </div>
            </div>
            <div v-if="document && confirmingDelete" class="flex flex-wrap items-center gap-3">
                <MyButtonConstructor type="button" variant="danger" :icon="Trash2" @click="destroy">Yes, delete {{ document.number }}</MyButtonConstructor>
                <MyButtonConstructor type="button" variant="outline" @click="confirmingDelete = false">Keep it</MyButtonConstructor>
            </div>
        </form>
    </div>
</template>
