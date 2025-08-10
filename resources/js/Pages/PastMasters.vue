<script setup lang="ts">

import {computed} from 'vue'
import {usePage} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'

type PastMaster = {
    id: number
    name: string
    year: string
    deceased: boolean
}

const pastMasters = usePage().props.pastMasters;

const formattedMasters = computed(() => {
    return pastMasters.map(pm => ({
        ...pm,
        displayName: pm.deceased
            ? `${pm.name}<span class='text-primary-600 dark:text-primary-400'>*</span>`
            : pm.name
    }));
});

const columns = computed(() => {
    const half = Math.ceil(formattedMasters.value.length / 2);
    return [
        formattedMasters.value.slice(0, half),
        formattedMasters.value.slice(half)
    ];
});
</script>

<template>
    <AppLayout title="Past Masters">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">
                Past Masters
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card class="bg-surface-0 dark:bg-surface-800 shadow">
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                            <ul v-for="(col, idx) in columns" :key="idx"
                                class="divide-y divide-surface-200 dark:divide-surface-700">
                                <li
                                    v-for="(pm, rowIndex) in col"
                                    :key="pm.id"
                                    :class="[
                                        'flex gap-4 py-1 px-2 text-sm',
                                        rowIndex % 2 === 0
                                            ? 'bg-surface-50 dark:bg-surface-800'
                                            : 'bg-surface-100 dark:bg-surface-700'
                                    ]"
                                >
                                    <span class="w-20 shrink-0">{{ pm.year }}</span>
                                    <span class="truncate" v-html="pm.displayName"></span>
                                </li>
                            </ul>
                        </div>

                        <p class="mt-4 text-sm text-gray-500">
                            <span class="text-primary-600 dark:text-primary-400">*</span> Deceased
                        </p>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
