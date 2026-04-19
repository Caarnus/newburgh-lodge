<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

type FundraiserRow = {
    id: number
    title: string
    slug: string
    is_active: boolean
    goal_amount: number
    raised_amount: number
    progress_percent: number
    updated_at: string | null
    public_url: string
    qr_download_url: string
}

const props = defineProps<{
    fundraisers: FundraiserRow[]
}>()

const currency = computed(() =>
    new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 })
)
</script>

<template>
    <AppLayout title="Manage Fundraisers">
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                    Manage Fundraisers
                </h2>
                <Link :href="route('admin.fundraisers.create')">
                    <Button icon="pi pi-plus" label="Create Fundraiser" />
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <Card class="shadow-sm border border-surface-200 dark:border-surface-700">
                <template #content>
                    <DataTable :value="props.fundraisers" paginator :rows="12" dataKey="id" striped-rows>
                        <Column field="title" header="Fundraiser">
                            <template #body="{ data }">
                                <div class="font-medium">{{ data.title }}</div>
                                <div class="text-xs text-surface-500 dark:text-surface-400">{{ data.slug }}</div>
                            </template>
                        </Column>

                        <Column field="is_active" header="Status">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'danger'" :value="data.is_active ? 'Active' : 'Inactive'" />
                            </template>
                        </Column>

                        <Column header="Progress">
                            <template #body="{ data }">
                                <div class="text-sm">
                                    {{ currency.format(data.raised_amount) }} / {{ currency.format(data.goal_amount) }}
                                </div>
                                <div class="text-xs text-surface-500 dark:text-surface-400">{{ data.progress_percent.toFixed(1) }}%</div>
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 320px;">
                            <template #body="{ data }">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Link :href="route('admin.fundraisers.edit', data.id)">
                                        <Button label="Edit" size="small" icon="pi pi-pencil" />
                                    </Link>
                                    <a :href="data.public_url" target="_blank" rel="noopener noreferrer">
                                        <Button label="View" size="small" text />
                                    </a>
                                    <a :href="data.qr_download_url">
                                        <Button label="QR PNG" size="small" severity="secondary" outlined />
                                    </a>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

