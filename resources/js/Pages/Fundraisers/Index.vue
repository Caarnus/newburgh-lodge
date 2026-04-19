<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'

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

const addRaisedVisible = ref(false)
const selectedFundraiser = ref<FundraiserRow | null>(null)
const addRaisedForm = useForm({
    amount: null as number | null,
})

function openAddRaised(fundraiser: FundraiserRow) {
    selectedFundraiser.value = fundraiser
    addRaisedForm.amount = null
    addRaisedForm.clearErrors()
    addRaisedVisible.value = true
}

function submitAddRaised() {
    if (!selectedFundraiser.value) return

    addRaisedForm.post(route('admin.fundraisers.raise', selectedFundraiser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            addRaisedVisible.value = false
        },
    })
}
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

                        <Column header="Actions" style="width: 420px;">
                            <template #body="{ data }">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Link :href="route('admin.fundraisers.edit', data.id)">
                                        <Button label="Edit" size="small" icon="pi pi-pencil" />
                                    </Link>
                                    <Button
                                        label="Add Raised"
                                        size="small"
                                        icon="pi pi-plus"
                                        severity="success"
                                        outlined
                                        @click="openAddRaised(data)"
                                    />
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

        <Dialog
            v-model:visible="addRaisedVisible"
            modal
            :draggable="false"
            :style="{ width: 'min(92vw, 30rem)' }"
            header="Add Amount Raised"
        >
            <div class="space-y-4">
                <p class="text-sm text-surface-700 dark:text-surface-200">
                    {{ selectedFundraiser?.title }}
                </p>

                <div>
                    <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Amount to add</label>
                    <InputNumber
                        v-model="addRaisedForm.amount"
                        mode="currency"
                        currency="USD"
                        locale="en-US"
                        class="w-full mt-1"
                        :min="0.01"
                    />
                    <p v-if="addRaisedForm.errors.amount" class="mt-1 text-sm text-red-500">
                        {{ addRaisedForm.errors.amount }}
                    </p>
                </div>

                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" text @click="addRaisedVisible = false" />
                    <Button
                        label="Apply"
                        icon="pi pi-check"
                        :loading="addRaisedForm.processing"
                        @click="submitAddRaised"
                    />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

