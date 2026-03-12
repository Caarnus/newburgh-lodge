<script>
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DirectoryFilterBar from '@/Components/MemberDirectory/DirectoryFilterBar.vue';
import DirectorySectionTabs from '@/Components/MemberDirectory/DirectorySectionTabs.vue';
import QuickContactLogModal from '@/Components/MemberDirectory/QuickContactLogModal.vue';

const props = defineProps({
    section: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, required: true },
    filters: { type: Object, required: true },
    records: { type: Object, required: true },
    sortOptions: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    relationshipTypeOptions: { type: Array, default: () => [] },
});

const page = usePage();

const canCreatePeople = computed(() => Boolean(page.props?.can?.manage?.people?.updateRecords));
const canImportRoster = computed(() => Boolean(page.props?.can?.manage?.people?.importRoster));
const canExportDirectory = computed(() => Boolean(page.props?.can?.manage?.people?.exportDirectory));
const canLogContacts = computed(() => Boolean(page.props?.can?.manage?.people?.logContacts));
const canViewDetails = computed(() => Boolean(page.props?.can?.manage?.people?.details));
const showActionsColumn = computed(() => canLogContacts.value || canViewDetails.value);

const only = [
    'section',
    'title',
    'description',
    'filters',
    'records',
    'statusOptions',
    'relationshipTypeOptions',
    'sortOptions',
];

const routeNameBySection = {
    all: 'manage.member-directory.all.index',
    members: 'manage.member-directory.members.index',
    widows: 'manage.member-directory.widows.index',
    orphans: 'manage.member-directory.orphans.index',
    relatives: 'manage.member-directory.relatives.index',
    others: 'manage.member-directory.others.index',
};

const currentRoute = computed(() => routeNameBySection[props.section] ?? routeNameBySection.all);
const countLabel = computed(() => ({
    all: 'people',
    members: 'members',
    widows: 'widows',
    orphans: 'orphans',
    relatives: 'relatives',
    others: 'people',
}[props.section] ?? 'people'));

const showMemberColumns = computed(() => ['all', 'members'].includes(props.section));
const showCareColumns = computed(() => ['widows', 'orphans'].includes(props.section));
const showRelativeColumns = computed(() => props.section === 'relatives');
const hideDeceasedStorageKey = 'member-directory.hide-deceased';
const normalizeHideDeceased = (value) => (
    value === true
    || value === 1
    || value === '1'
    || value === 'true'
    || value === 'on'
);
const hasHideDeceasedInUrl = computed(() => {
    const url = String(page.url ?? '');
    const query = url.split('?')[1] ?? '';

    return new URLSearchParams(query).has('hide_deceased');
});

const readPersistedHideDeceased = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    const value = window.localStorage.getItem(hideDeceasedStorageKey);

    if (value === null) {
        return null;
    }

    return value === 'true';
};

const persistHideDeceased = (value) => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(hideDeceasedStorageKey, value ? 'true' : 'false');
};

const pruneFilters = (filters) => Object.fromEntries(
    Object.entries(filters).filter(([, value]) => value !== null && value !== '')
);

const visitSection = (nextFilters) => {
    const hideDeceased = normalizeHideDeceased(nextFilters.hide_deceased);
    const normalizedFilters = {
        ...nextFilters,
        hide_deceased: hideDeceased ? 1 : 0,
    };

    persistHideDeceased(hideDeceased);

    router.get(route(currentRoute.value), pruneFilters(normalizedFilters), {
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

const exportFilters = computed(() => pruneFilters({
    ...props.filters,
    page: undefined,
    per_page: undefined,
}));

const quickLogVisible = ref(false);
const quickLogPerson = ref(null);

const openQuickLog = (person) => {
    quickLogPerson.value = person;
    quickLogVisible.value = true;
};

const closeQuickLog = () => {
    quickLogVisible.value = false;
    quickLogPerson.value = null;
};

const onQuickLogVisibleChange = (value) => {
    quickLogVisible.value = value;

    if (!value) {
        closeQuickLog();
    }
};

const onQuickLogSaved = () => {
    router.reload({
        only: ['records'],
        preserveScroll: true,
        preserveState: true,
    });
};

onMounted(() => {
    const persisted = readPersistedHideDeceased();
    const current = normalizeHideDeceased(props.filters.hide_deceased);

    if (persisted === null) {
        persistHideDeceased(current);
        return;
    }

    if (hasHideDeceasedInUrl.value) {
        persistHideDeceased(current);
        return;
    }

    if (persisted !== current) {
        visitSection({
            ...props.filters,
            hide_deceased: persisted,
            page: 1,
        });
        return;
    }

    persistHideDeceased(current);
});
</script>

<template>
    <div class="space-y-6 p-6">
        <div class="space-y-4">
            <DirectorySectionTabs :active="section" :filters="filters" />
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-surface-900 dark:text-surface-0">{{ title }}</h1>
                    <p class="mt-2 text-surface-600 dark:text-surface-300">
                        {{ description }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="canCreatePeople"
                        :href="route('manage.member-directory.people.create')"
                        class="inline-flex items-center rounded-lg border border-surface-300 px-3 py-2 text-sm font-medium text-surface-700 transition hover:bg-surface-50 dark:border-surface-700 dark:text-surface-100 dark:hover:bg-surface-800"
                    >
                        New Person
                    </Link>
                    <Link
                        v-if="canImportRoster"
                        :href="route('manage.member-directory.imports.index')"
                        class="inline-flex items-center rounded-lg bg-primary px-3 py-2 text-sm font-medium text-white transition hover:opacity-90"
                    >
                        Import Roster
                    </Link>
                </div>
            </div>
        </div>

        <DirectoryFilterBar
            :section="section"
            :filters="filters"
            :sort-options="sortOptions"
            :status-options="statusOptions"
            :relationship-type-options="relationshipTypeOptions"
            @apply="visitSection"
            @reset="visitSection"
        />

        <div class="rounded-2xl border border-surface-200 bg-white p-4 shadow-sm dark:border-surface-800 dark:bg-surface-900">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-surface-600 dark:text-surface-300">
                    Showing {{ records.from ?? 0 }}–{{ records.to ?? 0 }} of {{ records.total ?? 0 }} {{ countLabel }}.
                </div>
                <Link
                    v-if="section === 'members' && canExportDirectory"
                    :href="route('manage.member-directory.members.export', exportFilters)"
                    class="inline-flex items-center rounded-lg border border-surface-300 px-3 py-2 text-sm font-medium text-surface-700 transition hover:bg-surface-50 dark:border-surface-700 dark:text-surface-100 dark:hover:bg-surface-800"
                >
                    Export
                </Link>
                <Button v-else-if="section === 'members'" label="Export" severity="secondary" outlined disabled />
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

                <Column header="Last Contact" style="min-width: 11rem">
                    <template #body="{ data }">{{ formatDateTime(data.last_contact_at) }}</template>
                </Column>

                <Column v-if="showActionsColumn" header="Actions" style="min-width: 9rem">
                    <template #body="{ data }">
                        <div class="flex flex-wrap items-center gap-2">
                            <Button
                                v-if="canLogContacts"
                                text
                                size="small"
                                label="Quick Log"
                                @click="openQuickLog(data)"
                            />
                            <Link
                                v-if="canViewDetails"
                                :href="route('manage.member-directory.people.show', { person: data.id, from: section })"
                                class="inline-flex items-center rounded-lg border border-surface-300 px-3 py-2 text-sm font-medium text-surface-700 transition hover:bg-surface-50 dark:border-surface-700 dark:text-surface-100 dark:hover:bg-surface-800"
                            >
                                View
                            </Link>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <QuickContactLogModal
            :visible="quickLogVisible"
            :person="quickLogPerson"
            :from-section="section"
            @update:visible="onQuickLogVisibleChange"
            @saved="onQuickLogSaved"
        />
    </div>
</template>
