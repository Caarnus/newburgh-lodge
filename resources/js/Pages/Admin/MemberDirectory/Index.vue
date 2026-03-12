<script>
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Tag from 'primevue/tag';
import DirectoryFilterBar from '@/Components/MemberDirectory/DirectoryFilterBar.vue';
import DirectorySectionTabs from '@/Components/MemberDirectory/DirectorySectionTabs.vue';

const props = defineProps({
    section: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, required: true },
    filters: { type: Object, required: true },
    records: { type: Object, required: true },
    sortOptions: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    memberTypeOptions: { type: Array, default: () => [] },
    relationshipTypeOptions: { type: Array, default: () => [] },
});

const only = [
    'section',
    'title',
    'description',
    'filters',
    'records',
    'statusOptions',
    'memberTypeOptions',
    'relationshipTypeOptions',
    'sortOptions',
];

const routeNameBySection = {
    members: 'manage.member-directory.members.index',
    widows: 'manage.member-directory.widows.index',
    orphans: 'manage.member-directory.orphans.index',
    relatives: 'manage.member-directory.relatives.index',
};

const currentRoute = computed(() => routeNameBySection[props.section] ?? routeNameBySection.members);
const countLabel = computed(() => ({
    members: 'members',
    widows: 'widows',
    orphans: 'orphans',
    relatives: 'relatives',
}[props.section] ?? 'people'));

const showMemberColumns = computed(() => props.section === 'members');
const showCareColumns = computed(() => ['widows', 'orphans'].includes(props.section));
const showRelativeColumns = computed(() => props.section === 'relatives');

const pruneFilters = (filters) => Object.fromEntries(
    Object.entries(filters).filter(([, value]) => value !== null && value !== '' && value !== false)
);

const visitSection = (nextFilters) => {
    router.get(route(currentRoute.value), pruneFilters(nextFilters), {
        only,
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const onPage = (event) => {
    visitSection({
        ...props.filters,
        page: event.page + 1,
        per_page: event.rows,
    });
};

const formatDate = (value) => value ? new Date(`${value}T00:00:00`).toLocaleDateString() : '—';
const formatDateTime = (value) => value ? new Date(value).toLocaleString() : '—';
const relationshipLabel = (value) => value || '—';
</script>

<template>
    <div class="space-y-6 p-6">
        <div class="space-y-4">
            <DirectorySectionTabs :active="section" :filters="filters" />
            <div>
                <h1 class="text-3xl font-semibold text-surface-900 dark:text-surface-0">{{ title }}</h1>
                <p class="mt-2 text-surface-600 dark:text-surface-300">
                    {{ description }}
                </p>
            </div>
        </div>

        <DirectoryFilterBar
            :section="section"
            :filters="filters"
            :sort-options="sortOptions"
            :status-options="statusOptions"
            :member-type-options="memberTypeOptions"
            :relationship-type-options="relationshipTypeOptions"
            @apply="visitSection"
            @reset="visitSection"
        />

        <div class="rounded-2xl border border-surface-200 bg-white p-4 shadow-sm dark:border-surface-800 dark:bg-surface-900">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-surface-600 dark:text-surface-300">
                    Showing {{ records.from ?? 0 }}–{{ records.to ?? 0 }} of {{ records.total ?? 0 }} {{ countLabel }}.
                </div>
                <Button v-if="section === 'members'" label="Export" severity="secondary" outlined disabled />
            </div>

            <DataTable
                :value="records.data"
                data-key="id"
                paginator
                lazy
                :rows="records.per_page"
                :first="((records.current_page || 1) - 1) * (records.per_page || 25)"
                :total-records="records.total"
                responsive-layout="scroll"
                @page="onPage"
            >
                <Column field="display_name" header="Name" style="min-width: 16rem">
                    <template #body="{ data }">
                        <div class="font-medium text-surface-900 dark:text-surface-0">{{ data.display_name }}</div>
                        <div class="mt-1 text-xs text-surface-500">{{ data.email || 'No email' }}</div>
                    </template>
                </Column>

                <Column v-if="showMemberColumns" header="Member #" style="min-width: 8rem">
                    <template #body="{ data }">{{ data.member_profile?.member_number || '—' }}</template>
                </Column>

                <Column v-if="showMemberColumns" header="Status" style="min-width: 9rem">
                    <template #body="{ data }">{{ data.member_profile?.status || '—' }}</template>
                </Column>

                <Column v-if="showMemberColumns" header="Type" style="min-width: 9rem">
                    <template #body="{ data }">{{ data.member_profile?.member_type || '—' }}</template>
                </Column>

                <Column v-if="showCareColumns" header="Related Member" style="min-width: 14rem">
                    <template #body="{ data }">
                        <div>{{ data.related_member?.display_name || '—' }}</div>
                        <div class="mt-1 text-xs text-surface-500">{{ data.related_member?.member_number || 'No member #' }}</div>
                    </template>
                </Column>

                <Column v-if="showCareColumns" header="Death Date" style="min-width: 10rem">
                    <template #body="{ data }">{{ formatDate(data.related_member?.death_date) }}</template>
                </Column>

                <Column v-if="showRelativeColumns" header="Relationship" style="min-width: 11rem">
                    <template #body="{ data }">{{ relationshipLabel(data.relationship?.label) }}</template>
                </Column>

                <Column v-if="showRelativeColumns" header="Related To" style="min-width: 14rem">
                    <template #body="{ data }">
                        <div>{{ data.relationship?.related_person?.display_name || '—' }}</div>
                        <div class="mt-1 text-xs text-surface-500">{{ data.relationship?.related_person?.member_number || 'No member #' }}</div>
                    </template>
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
                            <Tag :severity="data.is_deceased ? 'danger' : 'success'" :value="data.is_deceased ? 'Yes' : 'No'" />
                            <span v-if="data.is_deceased" class="text-xs text-surface-500">{{ formatDate(data.death_date) }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="Last Contact" style="min-width: 11rem">
                    <template #body="{ data }">{{ formatDateTime(data.last_contact_at) }}</template>
                </Column>

                <Column header="Actions" style="min-width: 9rem">
                    <template #body="{ data }">
                        <Link
                            :href="route('manage.member-directory.people.show', { person: data.id, from: section })"
                            class="inline-flex items-center rounded-lg border border-surface-300 px-3 py-2 text-sm font-medium text-surface-700 transition hover:bg-surface-50 dark:border-surface-700 dark:text-surface-100 dark:hover:bg-surface-800"
                        >
                            View
                        </Link>
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
