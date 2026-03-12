<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    active: {
        type: String,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

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

const sharedData = () => ({
    q: props.filters.q ?? undefined,
    hide_deceased: props.filters.hide_deceased || undefined,
    per_page: props.filters.per_page ?? undefined,
    page: undefined,
});

const tabs = [
    { key: 'members', label: 'Members', href: route('manage.member-directory.members.index') },
    { key: 'widows', label: 'Widows', href: route('manage.member-directory.widows.index') },
    { key: 'orphans', label: 'Orphans', href: route('manage.member-directory.orphans.index') },
    { key: 'relatives', label: 'Relatives', href: route('manage.member-directory.relatives.index') },
];
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Link
            v-for="tab in tabs"
            :key="tab.key"
            :href="tab.href"
            :data="sharedData()"
            :only="only"
            preserve-scroll
            preserve-state
            class="rounded-lg border px-4 py-2 text-sm font-medium transition"
            :class="tab.key === active
                ? 'border-primary bg-primary text-white'
                : 'border-surface-300 bg-white text-surface-700 hover:bg-surface-50 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100 dark:hover:bg-surface-800'"
        >
            {{ tab.label }}
        </Link>
    </div>
</template>
