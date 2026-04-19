<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'

type FundraiserShow = {
    id: number
    title: string
    slug: string
    short_description: string | null
    description: string | null
    goal_amount: number
    raised_amount: number
    progress_percent: number
    image_urls: string[]
}

const props = defineProps<{
    fundraiser: FundraiserShow
}>()

const currency = computed(() =>
    new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 })
)

function progressWidth(value: number): string {
    return `${Math.max(0, Math.min(100, value))}%`
}
</script>

<template>
    <AppLayout :title="props.fundraiser.title">
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                    {{ props.fundraiser.title }}
                </h2>
                <Link :href="route('fundraisers.index')">
                    <Button label="All Fundraisers" icon="pi pi-arrow-left" text />
                </Link>
            </div>
        </template>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <Card class="shadow-sm border border-surface-200 dark:border-surface-700">
                <template #content>
                    <p v-if="props.fundraiser.short_description" class="text-lg text-surface-700 dark:text-surface-200 mb-5">
                        {{ props.fundraiser.short_description }}
                    </p>

                    <div class="space-y-2 mb-6">
                        <div class="flex items-center justify-between text-sm text-surface-600 dark:text-surface-300">
                            <span>Raised {{ currency.format(props.fundraiser.raised_amount) }}</span>
                            <span>Goal {{ currency.format(props.fundraiser.goal_amount) }}</span>
                        </div>
                        <div class="h-4 rounded-full bg-surface-200 dark:bg-surface-700 overflow-hidden">
                            <div class="h-full bg-emerald-500 transition-all" :style="{ width: progressWidth(props.fundraiser.progress_percent) }" />
                        </div>
                        <div class="text-xs text-surface-500 dark:text-surface-400 text-right">
                            {{ props.fundraiser.progress_percent.toFixed(1) }}%
                        </div>
                    </div>

                    <div v-if="props.fundraiser.description" class="whitespace-pre-line text-surface-800 dark:text-surface-100 mb-8">
                        {{ props.fundraiser.description }}
                    </div>

                    <div v-if="props.fundraiser.image_urls.length > 0" class="space-y-4">
                        <img
                            :src="props.fundraiser.image_urls[0]"
                            alt="Fundraiser image"
                            class="w-full max-h-[30rem] object-cover rounded-xl border border-surface-200 dark:border-surface-700"
                        >
                        <div
                            v-if="props.fundraiser.image_urls.length > 1"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                        >
                            <img
                                v-for="(imageUrl, idx) in props.fundraiser.image_urls.slice(1)"
                                :key="`${imageUrl}-${idx}`"
                                :src="imageUrl"
                                alt="Fundraiser image"
                                class="w-full h-52 object-cover rounded-lg border border-surface-200 dark:border-surface-700"
                            >
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

