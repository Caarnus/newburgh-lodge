<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Button, Tag, InputText, Column, DataTable, Card } from 'primevue'
import AppLayout from "@/Layouts/AppLayout.vue"

const $page = usePage()
const props = defineProps({
    newsletters: Array
})

const search = ref('')
const filteredNewsletters = computed(() => {
    if (!search.value) return props.newsletters
    return props.newsletters.filter(newsletter =>
        newsletter.title.toLowerCase().includes(search.value.toLowerCase())
    )
})

const latest = computed(() => {
    return [...props.newsletters].sort((a, b) =>
        new Date(b.created_at) - new Date(a.created_at)
    )[0]
})
</script>

<template>
    <AppLayout :title="$page.props.site.newsletterLabel">
        <template #header>
            <div class="flex flex-row justify-between">
                <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                    {{ $page.props.site.newsletterLabel }}
                </h2>
                <div v-if="$page.props.can?.newsletter?.create" class="flex justify-end">
                    <Link :href="route('newsletters.create')">
                        <Button icon="pi pi-plus" label="New" />
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8 space-y-8">
            <!-- Latest Newsletter Card -->
            <Card v-if="latest" class="shadow-lg border border-surface-200 dark:border-surface-700 rounded-xl">
                <template #title>
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-surface-900 dark:text-surface-100">
                            Latest {{ $page.props.site.newsletterLabel }}
                        </h2>
                        <Tag value="New" severity="success" />
                    </div>
                </template>
                <template #content>
                    <p class="text-lg font-semibold text-surface-800 dark:text-surface-200">{{ latest.title }}</p>
                    <p class="text-sm text-surface-500 dark:text-surface-400">
                        Published {{ new Date(latest.created_at).toLocaleDateString() }}
                    </p>
                </template>
                <template #footer>
                    <Link :href="latest.url">
                        <Button label="View Newsletter" icon="pi pi-eye" severity="primary" />
                    </Link>
                </template>
            </Card>

            <!-- Search + Table -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-bold text-surface-900 dark:text-surface-100">
                    All {{ $page.props.site.newsletterLabel }}
                </h2>
                <span class="p-input-icon-left w-full sm:w-auto">
                    <i class="pi pi-search mr-2" />
                    <InputText
                        v-model="search"
                        placeholder="Search newsletters..."
                        class="w-full sm:w-64"
                    />
                </span>
            </div>

            <div class="bg-surface-0 dark:bg-surface-800 shadow rounded-lg p-4">
                <DataTable
                    :value="filteredNewsletters"
                    paginator
                    :rows="10"
                    responsive-layout="scroll"
                    striped-rows
                >
                    <Column field="title" header="Title" :sortable="true">
                        <template #body="{ data }">
                            <Link :href="data.url" class="text-primary hover:underline">
                                {{ data.title }}
                            </Link>
                        </template>
                    </Column>
                    <Column field="created_at" header="Published" :sortable="true">
                        <template #body="{ data }">
                            {{ new Date(data.created_at).toLocaleDateString() }}
                        </template>
                    </Column>
                    <Column header="Actions">
                        <template #body="{ data }">
                            <Link :href="data.url">
                                <Button icon="pi pi-eye" severity="secondary" text rounded />
                            </Link>
                            <Link v-if="$page.props.can.newsletter.update" :href="route('newsletters.edit', data.id)">
                                <Button icon="pi pi-pencil" severity="warn" text rounded />
                            </Link>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
