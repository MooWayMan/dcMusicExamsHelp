<!-- resources/js/pages/admin/PaymentDocuments/Index.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { FileText, Plus } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyCardConstructor from '@/components/reusables/MyCardConstructor.vue'

interface DocumentRow {
    id: number
    number: string
    kind: string
    issued_on: string
    to_name: string
    title: string | null
    total: string
}

defineProps<{ documents: DocumentRow[] }>()

const columns = [
    { key: 'number', title: 'Number', sortable: true },
    { key: 'kind', title: 'Type', sortable: true },
    { key: 'issued_on', title: 'Date', sortable: true },
    { key: 'to_name', title: 'To / from', sortable: true },
    { key: 'title', title: 'What for', sortable: false },
    { key: 'total', title: 'Total', sortable: false, align: 'right' as const },
    { key: 'actions', title: '', sortable: false },
]
</script>

<template>
    <div class="mx-auto w-full max-w-screen-xl px-4 py-6 sm:px-6 lg:px-8">
        <PageHeader
            title="Payment Documents"
            eyebrow="Admin"
            size="compact"
            subtitle="Payment advices for money you pay out on Trinity's behalf (venue rent, stewards), and receipts for exam fees paid to you."
        >
            <template #actions>
                <div class="flex flex-wrap gap-3">
                    <Link href="/admin/payment-documents/create?kind=payment">
                        <MyButtonConstructor variant="primary" :icon="Plus">New payment advice</MyButtonConstructor>
                    </Link>
                    <Link href="/admin/payment-documents/create?kind=receipt">
                        <MyButtonConstructor variant="outline" :icon="Plus">New receipt</MyButtonConstructor>
                    </Link>
                </div>
            </template>
        </PageHeader>

        <div class="mt-6">
            <MyCardConstructor v-if="documents.length === 0" padding="large">
                <MyTextConstructor variant="muted">
                    <template #myPara>No payment documents yet.</template>
                </MyTextConstructor>
            </MyCardConstructor>

            <MyTableConstructor v-else :data="documents" :columns="columns" rowKey="id" defaultSortKey="issued_on" defaultSortDir="desc">
                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap justify-end gap-2">
                        <Link :href="`/admin/payment-documents/${row.id}/edit`">
                            <MyButtonConstructor variant="outline" size="small">Edit</MyButtonConstructor>
                        </Link>
                        <a :href="`/admin/payment-documents/${row.id}/pdf`" target="_blank" rel="noopener">
                            <MyButtonConstructor variant="outline" size="small" :icon="FileText">PDF</MyButtonConstructor>
                        </a>
                    </div>
                </template>
            </MyTableConstructor>
        </div>
    </div>
</template>
