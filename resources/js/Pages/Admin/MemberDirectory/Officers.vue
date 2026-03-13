<script>
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Select from 'primevue/select';

const props = defineProps({
    officers: { type: Array, default: () => [] },
    memberOptions: { type: Array, default: () => [] },
});

const form = useForm({
    assignments: props.officers.map((officer) => ({
        id: officer.id,
        title: officer.title,
        person_id: officer.person_id ?? null,
    })),
});

const selectableMembers = computed(() => props.memberOptions.map((member) => ({
    id: member.id,
    label: member.member_number
        ? `${member.display_name} (#${member.member_number})`
        : member.display_name,
})));

const resetForm = () => {
    form.assignments = props.officers.map((officer) => ({
        id: officer.id,
        title: officer.title,
        person_id: officer.person_id ?? null,
    }));
    form.clearErrors();
};

const submit = () => {
    form.put(route('manage.member-directory.officers.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm text-surface-500 dark:text-surface-400">
                    <Link :href="route('officers')" class="hover:underline">Back to Officers</Link>
                </div>
                <h1 class="mt-2 text-3xl font-semibold text-surface-900 dark:text-surface-0">Officer Assignments</h1>
                <p class="mt-2 text-surface-600 dark:text-surface-300">
                    Assign each officer title to a member record. Leave blank to show Open.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button label="Reset" severity="secondary" outlined @click="resetForm" />
                <Button label="Save Assignments" :loading="form.processing" @click="submit" />
            </div>
        </div>

        <Card>
            <template #content>
                <div class="space-y-4">
                    <div
                        v-for="(assignment, index) in form.assignments"
                        :key="assignment.id"
                        class="grid gap-2 rounded-lg border border-surface-200 p-3 md:grid-cols-[16rem_1fr] md:items-start dark:border-surface-700"
                    >
                        <div class="text-sm font-medium text-surface-800 dark:text-surface-100">
                            {{ assignment.title }}
                        </div>
                        <div>
                            <Select
                                v-model="assignment.person_id"
                                :options="selectableMembers"
                                option-label="label"
                                option-value="id"
                                class="w-full"
                                placeholder="Open"
                                show-clear
                                filter
                            />
                            <p v-if="form.errors[`assignments.${index}.person_id`]" class="mt-1 text-sm text-red-500">
                                {{ form.errors[`assignments.${index}.person_id`] }}
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>

