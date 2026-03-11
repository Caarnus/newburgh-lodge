<script setup>
import { router } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Tag from 'primevue/tag';
import DirectoryFilterBar from '@/Components/MemberDirectory/DirectoryFilterBar.vue';
import DirectorySectionTabs from '@/Components/MemberDirectory/DirectorySectionTabs.vue';
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    filters: { type: Object, required: true },
    orphans: { type: Object, required: true },
    sortOptions: { type: Array, default: () => [] },
});

const applyFilters = (nextFilters) => {
    router.get(route('manage.member-directory.orphans.index'), pruneFilters(nextFilters), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const onPage = (event) => {
    applyFilters({
        ...props.filters,
        page: event.page + 1,
        per_page: event.rows,
    });
};

const pruneFilters = (filters) => Object.fromEntries(
    Object.entries(filters).filter(([, value]) => value !== null && value !== '' && value !== false)
);

const formatDate = (value) => value ? new Date(`${value}T00:00:00`).toLocaleDateString() : '—';
const formatDateTime = (value) => value ? new Date(value).toLocaleString() : '—';
</script>

<template>
    <AppLayout title="Orphans">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                    Orphans
                </h2>
            </div>
        </template>
    <div class="space-y-6 p-6">
        <div class="space-y-4">
            <DirectorySectionTabs active="orphans" />
            <div>
                <p class="mt-2 text-surface-600 dark:text-surface-300">
                    Children connected to deceased lodge members through parent relationships.
                </p>
            </div>
        </div>

        <DirectoryFilterBar
            :filters="filters"
            :sort-options="sortOptions"
            @apply="applyFilters"
            @reset="applyFilters"
        />

        <div class="rounded-2xl border border-surface-200 bg-white p-4 shadow-sm dark:border-surface-800 dark:bg-surface-900">
            <div class="mb-4 text-sm text-surface-600 dark:text-surface-300">
                Showing {{ orphans.from ?? 0 }}–{{ orphans.to ?? 0 }} of {{ orphans.total ?? 0 }} orphans.
            </div>

            <DataTable
                :value="orphans.data"
                data-key="id"
                paginator
                lazy
                :rows="orphans.per_page"
                :first="((orphans.current_page || 1) - 1) * (orphans.per_page || 25)"
                :total-records="orphans.total"
                responsive-layout="scroll"
                @page="onPage"
            >
                <Column field="display_name" header="Name" style="min-width: 16rem">
                    <template #body="{ data }">
                        <div class="font-medium text-surface-900 dark:text-surface-0">{{ data.display_name }}</div>
                        <div class="mt-1 text-xs text-surface-500">{{ data.email || 'No email' }}</div>
                    </template>
                </Column>

                <Column header="Related Member" style="min-width: 14rem">
                    <template #body="{ data }">
                        <div>{{ data.related_member?.display_name || '—' }}</div>
                        <div class="mt-1 text-xs text-surface-500">{{ data.related_member?.member_number || 'No member #' }}</div>
                    </template>
                </Column>

                <Column header="Death Date" style="min-width: 10rem">
                    <template #body="{ data }">{{ formatDate(data.related_member?.death_date) }}</template>
                </Column>

                <Column header="Phone" style="min-width: 10rem">
                    <template #body="{ data }">{{ data.phone || '—' }}</template>
                </Column>

                <Column header="Location" style="min-width: 10rem">
                    <template #body="{ data }">
                        <span v-if="data.city || data.state">{{ [data.city, data.state].filter(Boolean).join(', ') }}</span>
                        <span v-else>—</span>
                    </template>
                </Column>

                <Column header="Status" style="min-width: 8rem">
                    <template #body="{ data }">
                        <Tag :severity="data.is_deceased ? 'danger' : 'success'" :value="data.is_deceased ? 'Deceased' : 'Living'" />
                    </template>
                </Column>

                <Column header="Last Contact" style="min-width: 11rem">
                    <template #body="{ data }">{{ formatDateTime(data.last_contact_at) }}</template>
                </Column>
            </DataTable>
        </div>
    </div>
    </AppLayout>
</template>
