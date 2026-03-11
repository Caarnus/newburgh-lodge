<script setup>
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Tag from 'primevue/tag';
import DirectoryFilterBar from '@/Components/MemberDirectory/DirectoryFilterBar.vue';
import DirectorySectionTabs from '@/Components/MemberDirectory/DirectorySectionTabs.vue';

const props = defineProps({
    filters: { type: Object, required: true },
    members: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    memberTypeOptions: { type: Array, default: () => [] },
    sortOptions: { type: Array, default: () => [] },
});

const applyFilters = (nextFilters) => {
    router.get(route('manage.member-directory.members.index'), pruneFilters(nextFilters), {
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
const deceasedSeverity = (row) => row.is_deceased ? 'danger' : 'success';
</script>

<template>
    <div class="space-y-6 p-6">
        <div class="space-y-4">
            <DirectorySectionTabs active="members" />
            <div>
                <h1 class="text-3xl font-semibold text-surface-900 dark:text-surface-0">Member Directory</h1>
                <p class="mt-2 text-surface-600 dark:text-surface-300">
                    Internal roster view with filters for status, type, and deceased visibility.
                </p>
            </div>
        </div>

        <DirectoryFilterBar
            :filters="filters"
            :sort-options="sortOptions"
            :status-options="statusOptions"
            :member-type-options="memberTypeOptions"
            :show-member-filters="true"
            @apply="applyFilters"
            @reset="applyFilters"
        />

        <div class="rounded-2xl border border-surface-200 bg-white p-4 shadow-sm dark:border-surface-800 dark:bg-surface-900">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-surface-600 dark:text-surface-300">
                    Showing {{ members.from ?? 0 }}–{{ members.to ?? 0 }} of {{ members.total ?? 0 }} members.
                </div>
                <Button label="Export" severity="secondary" outlined disabled />
            </div>

            <DataTable
                :value="members.data"
                data-key="id"
                paginator
                lazy
                :rows="members.per_page"
                :first="((members.current_page || 1) - 1) * (members.per_page || 25)"
                :total-records="members.total"
                responsive-layout="scroll"
                @page="onPage"
            >
                <Column field="display_name" header="Name" style="min-width: 16rem">
                    <template #body="{ data }">
                        <div class="font-medium text-surface-900 dark:text-surface-0">{{ data.display_name }}</div>
                        <div class="mt-1 text-xs text-surface-500">{{ data.email || 'No email' }}</div>
                    </template>
                </Column>

                <Column header="Member #" style="min-width: 8rem">
                    <template #body="{ data }">{{ data.member_profile?.member_number || '—' }}</template>
                </Column>

                <Column header="Status" style="min-width: 9rem">
                    <template #body="{ data }">{{ data.member_profile?.status || '—' }}</template>
                </Column>

                <Column header="Type" style="min-width: 9rem">
                    <template #body="{ data }">{{ data.member_profile?.member_type || '—' }}</template>
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

                <Column header="Deceased" style="min-width: 10rem">
                    <template #body="{ data }">
                        <div class="flex flex-col gap-1">
                            <Tag :severity="deceasedSeverity(data)" :value="data.is_deceased ? 'Yes' : 'No'" />
                            <span v-if="data.is_deceased" class="text-xs text-surface-500">{{ formatDate(data.death_date) }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="Last Contact" style="min-width: 11rem">
                    <template #body="{ data }">{{ formatDateTime(data.last_contact_at) }}</template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
