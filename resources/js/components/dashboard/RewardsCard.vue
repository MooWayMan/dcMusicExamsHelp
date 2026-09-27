<!-- resources/js/components/dashboard/RewardsCard.vue -->
<!--
  RewardsCard: what a teacher has won, one block per quarter, newest first.
  Their appreciation badge with its certificate to download, then every
  gift-token prize for them or their own pupils and where it has got to.
  The data is App\Services\TeacherRewards; this only draws it.
-->
<script setup lang="ts">
import { Award, Download, Gift } from 'lucide-vue-next'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyCardConstructor from '@/components/reusables/MyCardConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import type { RewardQuarter } from '@/types/rewards'

const props = defineProps<{
    rewards: RewardQuarter[]
    certificateBase: string
}>()

function downloadCertificate(q: RewardQuarter) {
    window.location.href = `${props.certificateBase}/${q.year}/${q.quarter}/certificate`
}
</script>

<template>
    <MyCardConstructor title="Your rewards" class="mb-6">
        <div class="flex flex-col gap-4">
            <div
                v-for="q in rewards"
                :key="`${q.year}-${q.quarter}`"
                class="flex flex-col gap-3 border-t border-brand-border pt-4 first:border-t-0 first:pt-0"
            >
                <div class="text-sm">
                    <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">{{ q.label }}</MyTextConstructor>
                </div>

                <div v-if="q.badge" class="flex flex-wrap items-center gap-3">
                    <Award class="h-5 w-5 shrink-0 text-brand-accent" />
                    <div class="min-w-40 flex-1 text-base">
                        <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text" spacing="none">{{ q.badge }} teacher badge</MyTextConstructor>
                    </div>
                    <MyButtonConstructor size="small" variant="outline" :icon="Download" @click="downloadCertificate(q)">
                        Certificate
                    </MyButtonConstructor>
                </div>

                <div v-for="p in q.prizes" :key="p.id" class="flex flex-wrap items-start gap-3">
                    <Gift class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                    <div class="flex min-w-40 flex-1 flex-col gap-1">
                        <div class="text-base">
                            <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text" spacing="none">{{ p.who }}: {{ p.prize }}, {{ p.amount }} gift token</MyTextConstructor>
                        </div>
                        <div class="text-sm">
                            <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" spacing="none">
                                {{ p.status }}<template v-if="p.use_by">, use by {{ p.use_by }}</template>
                            </MyTextConstructor>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MyCardConstructor>
</template>
