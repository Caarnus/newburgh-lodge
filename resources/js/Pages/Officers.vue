<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    officers: { type: Array, default: () => [] },
});

const page = usePage();

const canViewProfileLinks = computed(() => Boolean(
    page.props?.auth?.user
    && page.props?.can?.manage?.people?.details
));

const canManageAssignments = computed(() => Boolean(page.props?.can?.manage?.people?.updateRecords));
</script>

<template>
    <AppLayout title="Officers">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">
                Lodge Officers
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-surface-0 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="dark:bg-surface-900 dark:text-surface-200 font-sans">
                        <div class="max-w-3xl mx-auto space-y-4 p-6">
                            <div v-if="canManageAssignments" class="flex justify-end">
                                <Link
                                    :href="route('manage.member-directory.officers.edit')"
                                    class="inline-flex items-center rounded-lg border border-surface-300 px-3 py-2 text-sm font-medium text-surface-700 transition hover:bg-surface-50 dark:border-surface-700 dark:text-surface-100 dark:hover:bg-surface-800"
                                >
                                    Manage Assignments
                                </Link>
                            </div>
                            <section class="mb-6">
                                <table class="mt-4 mx-auto w-full max-w-lg text-left">
                                    <thead>
                                    <tr class="text-primary-700 border-b border-surface-700">
                                        <th class="px-4 py-2 text-right">Title</th>
                                        <th class="px-4 py-2 text-left">Name</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-surface-700">
                                    <tr v-for="officer in officers" :key="officer.id">
                                        <td class="px-4 py-2 text-right">{{ officer.title }}:</td>
                                        <td class="px-4 py-2">
                                            <Link
                                                v-if="canViewProfileLinks && officer.person?.id"
                                                :href="route('manage.member-directory.people.show', { person: officer.person.id, from: 'all' })"
                                                class="text-primary-700 hover:underline"
                                            >
                                                {{ officer.person.display_name }}
                                            </Link>
                                            <span v-else>{{ officer.person?.display_name || 'Open' }}</span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
