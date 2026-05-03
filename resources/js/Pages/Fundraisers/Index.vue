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
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'

type FundraiserRow = {
    id: number
    title: string
    slug: string
    sort_order: number
    category: { id: number; name: string } | null
    is_active: boolean
    goal_amount: number
    raised_amount: number
    progress_percent: number
    updated_at: string | null
    public_url: string
    qr_download_url: string
}

type CategoryRow = {
    id: number
    name: string
    slug: string
    sort_order: number
    fundraisers_count: number
}

const props = defineProps<{
    fundraisers: FundraiserRow[]
    categories: CategoryRow[]
}>()

const currency = computed(() =>
    new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 })
)

const addRaisedVisible = ref(false)
const selectedFundraiser = ref<FundraiserRow | null>(null)
const addRaisedForm = useForm({
    amount: null as number | null,
})

const createCategoryVisible = ref(false)
const createCategoryForm = useForm({
    name: '',
    sort_order: 0 as number | null,
})

const editCategoryVisible = ref(false)
const selectedCategory = ref<CategoryRow | null>(null)
const editCategoryForm = useForm({
    name: '',
    sort_order: 0 as number | null,
})

const deleteCategoryForm = useForm({})

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

function openCreateCategory() {
    createCategoryForm.name = ''
    createCategoryForm.sort_order = 0
    createCategoryForm.clearErrors()
    createCategoryVisible.value = true
}

function submitCreateCategory() {
    createCategoryForm.post(route('admin.fundraisers.categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createCategoryVisible.value = false
        },
    })
}

function openEditCategory(category: CategoryRow) {
    selectedCategory.value = category
    editCategoryForm.name = category.name
    editCategoryForm.sort_order = Number(category.sort_order ?? 0)
    editCategoryForm.clearErrors()
    editCategoryVisible.value = true
}

function submitEditCategory() {
    if (!selectedCategory.value) return

    editCategoryForm.put(route('admin.fundraisers.categories.update', selectedCategory.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editCategoryVisible.value = false
        },
    })
}

function deleteCategory(category: CategoryRow) {
    if (!confirm(`Delete category "${category.name}"?`)) return

    deleteCategoryForm.delete(route('admin.fundraisers.categories.destroy', category.id), {
        preserveScroll: true,
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

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <Card class="shadow-sm border border-surface-200 dark:border-surface-700">
                <template #content>
                    <DataTable :value="props.fundraisers" paginator :rows="12" dataKey="id" striped-rows>
                        <Column field="title" header="Fundraiser">
                            <template #body="{ data }">
                                <div class="font-medium">{{ data.title }}</div>
                                <div class="text-xs text-surface-500 dark:text-surface-400">{{ data.slug }}</div>
                            </template>
                        </Column>

                        <Column header="Category">
                            <template #body="{ data }">
                                <span class="text-sm">{{ data.category?.name ?? 'Uncategorized' }}</span>
                            </template>
                        </Column>

                        <Column field="sort_order" header="Sort" />

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

            <Card class="shadow-sm border border-surface-200 dark:border-surface-700">
                <template #title>
                    <div class="flex items-center justify-between">
                        <span>Fundraiser Categories</span>
                        <Button icon="pi pi-plus" label="Add Category" size="small" @click="openCreateCategory" />
                    </div>
                </template>

                <template #content>
                    <Message v-if="deleteCategoryForm.errors.delete_category" severity="error" class="mb-4">
                        {{ deleteCategoryForm.errors.delete_category }}
                    </Message>

                    <DataTable :value="props.categories" dataKey="id" striped-rows>
                        <Column field="name" header="Name" />
                        <Column field="slug" header="Slug" />
                        <Column field="sort_order" header="Sort" />
                        <Column field="fundraisers_count" header="Fundraisers" />
                        <Column header="Actions" style="width: 210px;">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button
                                        label="Edit"
                                        size="small"
                                        icon="pi pi-pencil"
                                        severity="secondary"
                                        outlined
                                        @click="openEditCategory(data)"
                                    />
                                    <Button
                                        label="Delete"
                                        size="small"
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        @click="deleteCategory(data)"
                                    />
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

        <Dialog
            v-model:visible="createCategoryVisible"
            modal
            :draggable="false"
            :style="{ width: 'min(92vw, 30rem)' }"
            header="Add Category"
        >
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Name</label>
                    <InputText v-model="createCategoryForm.name" class="w-full mt-1" />
                    <p v-if="createCategoryForm.errors.name" class="mt-1 text-sm text-red-500">{{ createCategoryForm.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Sort Order</label>
                    <InputNumber v-model="createCategoryForm.sort_order" class="w-full mt-1" :min="0" :max="1000000" />
                    <p v-if="createCategoryForm.errors.sort_order" class="mt-1 text-sm text-red-500">{{ createCategoryForm.errors.sort_order }}</p>
                </div>

                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" text @click="createCategoryVisible = false" />
                    <Button
                        label="Create"
                        icon="pi pi-check"
                        :loading="createCategoryForm.processing"
                        @click="submitCreateCategory"
                    />
                </div>
            </div>
        </Dialog>

        <Dialog
            v-model:visible="editCategoryVisible"
            modal
            :draggable="false"
            :style="{ width: 'min(92vw, 30rem)' }"
            header="Edit Category"
        >
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Name</label>
                    <InputText v-model="editCategoryForm.name" class="w-full mt-1" />
                    <p v-if="editCategoryForm.errors.name" class="mt-1 text-sm text-red-500">{{ editCategoryForm.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Sort Order</label>
                    <InputNumber v-model="editCategoryForm.sort_order" class="w-full mt-1" :min="0" :max="1000000" />
                    <p v-if="editCategoryForm.errors.sort_order" class="mt-1 text-sm text-red-500">{{ editCategoryForm.errors.sort_order }}</p>
                </div>

                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" text @click="editCategoryVisible = false" />
                    <Button
                        label="Save"
                        icon="pi pi-save"
                        :loading="editCategoryForm.processing"
                        @click="submitEditCategory"
                    />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
