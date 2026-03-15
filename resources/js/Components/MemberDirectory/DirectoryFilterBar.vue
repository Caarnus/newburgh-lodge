<script setup>
import { computed, reactive, watch } from 'vue';
import Button from 'primevue/button';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';

const props = defineProps({
    section: {
        type: String,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    sortOptions: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
    defaultStatusFilters: {
        type: Array,
        default: () => [],
    },
    relationshipTypeOptions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['apply', 'reset']);
const normalizeStatusFilter = (value) => {
    if (Array.isArray(value)) {
        return value.filter(Boolean);
    }

    if (value === null || value === undefined || value === '') {
        return [];
    }

    return [value];
};
const defaultMemberStatuses = () => normalizeStatusFilter(props.defaultStatusFilters);

const localFilters = reactive({
    q: props.filters.q ?? null,
    status: normalizeStatusFilter(
        props.filters.status ?? defaultMemberStatuses()
    ),
    relationship_type: props.filters.relationship_type ?? null,
    has_email: props.filters.has_email ?? null,
    has_phone: props.filters.has_phone ?? null,
    last_contact_older_than_days: props.filters.last_contact_older_than_days ?? null,
    sort: props.filters.sort ?? 'name',
    per_page: props.filters.per_page ?? 25,
});

watch(
    () => props.filters,
    (filters) => {
        localFilters.q = filters.q ?? null;
        localFilters.status = normalizeStatusFilter(
            filters.status ?? defaultMemberStatuses()
        );
        localFilters.relationship_type = filters.relationship_type ?? null;
        localFilters.has_email = filters.has_email ?? null;
        localFilters.has_phone = filters.has_phone ?? null;
        localFilters.last_contact_older_than_days = filters.last_contact_older_than_days ?? null;
        localFilters.sort = filters.sort ?? 'name';
        localFilters.per_page = filters.per_page ?? 25;
    },
    { deep: true }
);

watch(
    () => props.section,
    () => {
        localFilters.status = defaultMemberStatuses();
        localFilters.relationship_type = null;
        localFilters.sort = 'name';
    }
);

const perPageOptions = [10, 25, 50, 100].map((value) => ({
    label: `${value} per page`,
    value,
}));

const showStatusFilter = computed(() => props.statusOptions.length > 0);
const showRelationshipFilter = computed(() => props.section === 'relatives');
const yesNoOptions = [
    { label: 'Has Value', value: 'yes' },
    { label: 'No Value', value: 'no' },
];
const lastContactOptions = [
    { label: '30 days', value: 30 },
    { label: '60 days', value: 60 },
    { label: '90 days', value: 90 },
    { label: '180 days', value: 180 },
    { label: '365 days', value: 365 },
];

const searchPlaceholder = computed(() => ({
    all: 'Name, email, phone, member number',
    members: 'Name, email, phone, member number',
    widows: 'Name, email, phone',
    orphans: 'Name, email, phone',
    relatives: 'Name, email, phone',
    others: 'Name, email, phone',
}[props.section] ?? 'Search'));

const submit = () => {
    emit('apply', {
        q: localFilters.q,
        status: localFilters.status.length > 0 ? localFilters.status : null,
        relationship_type: localFilters.relationship_type,
        has_email: localFilters.has_email,
        has_phone: localFilters.has_phone,
        last_contact_older_than_days: localFilters.last_contact_older_than_days,
        sort: localFilters.sort,
        per_page: localFilters.per_page,
        page: 1,
    });
};

const reset = () => {
    localFilters.q = null;
    localFilters.status = defaultMemberStatuses();
    localFilters.relationship_type = null;
    localFilters.has_email = null;
    localFilters.has_phone = null;
    localFilters.last_contact_older_than_days = null;
    localFilters.sort = 'name';
    localFilters.per_page = 25;

    emit('reset', {
        q: null,
        status: localFilters.status.length > 0 ? localFilters.status : null,
        relationship_type: null,
        has_email: null,
        has_phone: null,
        last_contact_older_than_days: null,
        sort: 'name',
        per_page: 25,
        page: 1,
    });
};
</script>

<template>
    <div class="rounded-2xl border border-surface-200 bg-white p-4 shadow-sm dark:border-surface-800 dark:bg-surface-900">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-8">
            <div class="md:col-span-2 lg:col-span-3 xl:col-span-2 2xl:col-span-2">
                <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">
                    Search
                </label>
                <InputText
                    v-model="localFilters.q"
                    class="w-full"
                    :placeholder="searchPlaceholder"
                    @keyup.enter="submit"
                />
            </div>

            <div v-if="showStatusFilter">
                <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">
                    Status
                </label>
                <MultiSelect
                    v-model="localFilters.status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    display="chip"
                    :max-selected-labels="2"
                    selected-items-label="{0} selected"
                    :show-clear="true"
                    placeholder="Any statuses"
                />
            </div>

            <div v-if="showRelationshipFilter">
                <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">
                    Relationship Type
                </label>
                <Select
                    v-model="localFilters.relationship_type"
                    :options="relationshipTypeOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    show-clear
                    placeholder="Any relationship"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">
                    Email
                </label>
                <Select
                    v-model="localFilters.has_email"
                    :options="yesNoOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    show-clear
                    placeholder="Any"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">
                    Phone
                </label>
                <Select
                    v-model="localFilters.has_phone"
                    :options="yesNoOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    show-clear
                    placeholder="Any"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">
                    Last Contact
                </label>
                <Select
                    v-model="localFilters.last_contact_older_than_days"
                    :options="lastContactOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    show-clear
                    placeholder="Any"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">
                    Sort By
                </label>
                <Select
                    v-model="localFilters.sort"
                    :options="sortOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    placeholder="Sort"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">
                    Page Size
                </label>
                <Select
                    v-model="localFilters.per_page"
                    :options="perPageOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    placeholder="Page size"
                />
            </div>
        </div>

        <div class="mt-4 flex flex-wrap justify-end gap-2 md:mt-5">
            <Button label="Reset" severity="secondary" outlined @click="reset" />
            <Button label="Apply Filters" @click="submit" />
        </div>
    </div>
</template>
