<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'

type FundraiserCard = {
    id: number
    title: string
    slug: string
    category: {
        id: number
        name: string
    } | null
    short_description: string | null
    goal_amount: number
    raised_amount: number
    progress_percent: number
}

type FundraiserGroup = {
    category: string
    fundraisers: FundraiserCard[]
}

const props = defineProps<{
    fundraisers: FundraiserCard[]
}>()

const currency = computed(() =>
    new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 })
)

const groupedFundraisers = computed<FundraiserGroup[]>(() => {
    const groups = new Map<string, FundraiserCard[]>()

    for (const fundraiser of props.fundraisers) {
        const categoryName = fundraiser.category?.name ?? 'Uncategorized'
        if (!groups.has(categoryName)) {
            groups.set(categoryName, [])
        }
        groups.get(categoryName)?.push(fundraiser)
    }

    return Array.from(groups.entries())
        .sort(([a], [b]) => {
            if (a === 'Uncategorized') return 1
            if (b === 'Uncategorized') return -1
            return a.localeCompare(b)
        })
        .map(([category, fundraisers]) => ({ category, fundraisers }))
})

function progressWidth(value: number): string {
    return `${Math.max(0, Math.min(100, value))}%`
}
</script>

<template>
    <AppLayout title="Fundraisers">
        <template #header>
            <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                Active Fundraisers
            </h2>
        </template>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <section
                v-for="group in groupedFundraisers"
                :key="group.category"
                class="space-y-4"
            >
                <h3 class="text-lg font-semibold text-surface-800 dark:text-surface-100">
                    {{ group.category }}
                </h3>

                <Card
                    v-for="fundraiser in group.fundraisers"
                    :key="fundraiser.id"
                    class="shadow-sm border border-surface-200 dark:border-surface-700"
                >
                    <template #title>
                        <div class="text-2xl text-surface-900 dark:text-surface-0">{{ fundraiser.title }}</div>
                    </template>

                    <template #content>
                        <p v-if="fundraiser.short_description" class="text-surface-700 dark:text-surface-200 mb-4">
                            {{ fundraiser.short_description }}
                        </p>

                        <div class="space-y-2 mb-4">
                            <div class="flex items-center justify-between text-sm text-surface-600 dark:text-surface-300">
                                <span>Raised {{ currency.format(fundraiser.raised_amount) }}</span>
                                <span>Goal {{ currency.format(fundraiser.goal_amount) }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-surface-200 dark:bg-surface-700 overflow-hidden">
                                <div
                                    class="h-full bg-emerald-500 transition-all"
                                    :style="{ width: progressWidth(fundraiser.progress_percent) }"
                                />
                            </div>
                            <div class="text-xs text-surface-500 dark:text-surface-400 text-right">
                                {{ fundraiser.progress_percent.toFixed(1) }}%
                            </div>
                        </div>

                        <Link :href="route('fundraisers.show', fundraiser.slug)">
                            <Button label="View Details" icon="pi pi-arrow-right" icon-pos="right" />
                        </Link>
                    </template>
                </Card>
            </section>

            <Card
                v-if="props.fundraisers.length === 0"
                class="shadow-sm border border-surface-200 dark:border-surface-700"
            >
                <template #content>
                    <p class="text-surface-700 dark:text-surface-200">
                        No active fundraisers are available right now. Please check back soon.
                    </p>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
