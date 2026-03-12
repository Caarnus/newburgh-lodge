<script setup>
import { computed, reactive, watch } from 'vue';
import Button from 'primevue/button';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
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
    relationshipTypeOptions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['apply', 'reset']);

const localFilters = reactive({
    q: props.filters.q ?? null,
    status: props.filters.status ?? null,
    relationship_type: props.filters.relationship_type ?? null,
    hide_deceased: Boolean(props.filters.hide_deceased),
    sort: props.filters.sort ?? 'name',
    per_page: props.filters.per_page ?? 25,
});

watch(
    () => props.filters,
    (filters) => {
        localFilters.q = filters.q ?? null;
        localFilters.status = filters.status ?? null;
        localFilters.relationship_type = filters.relationship_type ?? null;
        localFilters.hide_deceased = Boolean(filters.hide_deceased);
        localFilters.sort = filters.sort ?? 'name';
        localFilters.per_page = filters.per_page ?? 25;
    },
    { deep: true }
);

watch(
    () => props.section,
    () => {
        localFilters.status = null;
        localFilters.relationship_type = null;
        localFilters.sort = 'name';
    }
);

const perPageOptions = [10, 25, 50, 100].map((value) => ({
    label: `${value} per page`,
    value,
}));

const showMemberFilters = computed(() => props.section === 'members');
const showRelationshipFilter = computed(() => props.section === 'relatives');

const searchPlaceholder = computed(() => ({
    members: 'Name, email, phone, member number',
    widows: 'Name, email, phone',
    orphans: 'Name, email, phone',
    relatives: 'Name, email, phone',
}[props.section] ?? 'Search'));

const submit = () => {
    emit('apply', {
        q: localFilters.q,
        status: localFilters.status,
        relationship_type: localFilters.relationship_type,
        hide_deceased: localFilters.hide_deceased,
        sort: localFilters.sort,
        per_page: localFilters.per_page,
        page: 1,
    });
};

const reset = () => {
    localFilters.q = null;
    localFilters.status = null;
    localFilters.relationship_type = null;
    localFilters.hide_deceased = false;
    localFilters.sort = 'name';
    localFilters.per_page = 25;

    emit('reset', {
        q: null,
        status: null,
        relationship_type: null,
        hide_deceased: false,
        sort: 'name',
        per_page: 25,
        page: 1,
    });
};
</script>

<template>
    <div class="rounded-2xl border border-surface-200 bg-white p-4 shadow-sm dark:border-surface-800 dark:bg-surface-900">
        <div class="grid gap-4 lg:grid-cols-6">
            <div class="lg:col-span-2">
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

            <div v-if="showMemberFilters">
                <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">
                    Status
                </label>
                <Select
                    v-model="localFilters.status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    show-clear
                    placeholder="Any status"
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

        <div class="mt-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <label class="flex items-center gap-3 text-sm text-surface-700 dark:text-surface-200">
                <ToggleSwitch v-model="localFilters.hide_deceased" />
                <span>Hide deceased people</span>
            </label>

            <div class="flex flex-wrap gap-2">
                <Button label="Reset" severity="secondary" outlined @click="reset" />
                <Button label="Apply Filters" @click="submit" />
            </div>
        </div>
    </div>
</template>
