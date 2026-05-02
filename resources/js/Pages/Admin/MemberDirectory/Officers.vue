<script>
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';

const props = defineProps({
    officers: { type: Array, default: () => [] },
    memberOptions: { type: Array, default: () => [] },
    pastMasters: { type: Array, default: () => [] },
});

const activeTab = ref('officers');

const assignmentForm = useForm({
    assignments: props.officers.map((officer) => ({
        id: officer.id,
        title: officer.title,
        person_id: officer.person_id ?? null,
    })),
});

const selectableMembers = computed(() => props.memberOptions.map((member) => {
    let label = member.member_number
        ? `${member.display_name} (#${member.member_number})`
        : member.display_name;

    if (member.is_deceased) {
        label = `${label} (Deceased)`;
    }

    return {
        id: member.id,
        label,
        is_deceased: Boolean(member.is_deceased),
    };
}));

const resetAssignmentForm = () => {
    assignmentForm.assignments = props.officers.map((officer) => ({
        id: officer.id,
        title: officer.title,
        person_id: officer.person_id ?? null,
    }));
    assignmentForm.clearErrors();
};

const saveAssignments = () => {
    assignmentForm.put(route('manage.member-directory.officers.update'), {
        preserveScroll: true,
    });
};

const pastMasterDialogVisible = ref(false);
const editingPastMasterId = ref(null);

const pastMasterForm = useForm({
    name: '',
    year: '',
    person_id: null,
    deceased: false,
});

const selectedLinkedMember = computed(() => {
    if (!pastMasterForm.person_id) {
        return null;
    }

    return selectableMembers.value.find((member) => member.id === pastMasterForm.person_id) ?? null;
});

const openCreatePastMaster = () => {
    editingPastMasterId.value = null;
    pastMasterForm.reset();
    pastMasterForm.clearErrors();
    pastMasterForm.deceased = false;
    pastMasterDialogVisible.value = true;
};

const openEditPastMaster = (pastMaster) => {
    editingPastMasterId.value = pastMaster.id;
    pastMasterForm.reset();
    pastMasterForm.clearErrors();
    pastMasterForm.name = pastMaster.name;
    pastMasterForm.year = pastMaster.year;
    pastMasterForm.person_id = pastMaster.person_id ?? null;
    pastMasterForm.deceased = Boolean(pastMaster.deceased);
    pastMasterDialogVisible.value = true;
};

const closePastMasterDialog = () => {
    pastMasterDialogVisible.value = false;
    pastMasterForm.clearErrors();
};

const savePastMaster = () => {
    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            pastMasterDialogVisible.value = false;
            pastMasterForm.reset();
            pastMasterForm.deceased = false;
            editingPastMasterId.value = null;
        },
    };

    if (editingPastMasterId.value) {
        pastMasterForm.put(route('manage.member-directory.past-masters.update', { pastMaster: editingPastMasterId.value }), options);
        return;
    }

    pastMasterForm.post(route('manage.member-directory.past-masters.store'), options);
};

const deletePastMaster = (pastMaster) => {
    if (!window.confirm(`Remove ${pastMaster.name} (${pastMaster.year}) from Past Masters?`)) {
        return;
    }

    router.delete(route('manage.member-directory.past-masters.destroy', { pastMaster: pastMaster.id }), {
        preserveScroll: true,
        preserveState: true,
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
                <h1 class="mt-2 text-3xl font-semibold text-surface-900 dark:text-surface-0">Officer Management</h1>
                <p class="mt-2 text-surface-600 dark:text-surface-300">
                    Manage officer assignments and the Past Masters list.
                </p>
            </div>
        </div>

        <Card>
            <template #content>
                <div class="mb-5 flex flex-wrap gap-2 border-b border-surface-200 pb-4 dark:border-surface-700">
                    <Button
                        label="Officer Assignments"
                        :severity="activeTab === 'officers' ? null : 'secondary'"
                        :outlined="activeTab !== 'officers'"
                        @click="activeTab = 'officers'"
                    />
                    <Button
                        label="Past Masters"
                        :severity="activeTab === 'past-masters' ? null : 'secondary'"
                        :outlined="activeTab !== 'past-masters'"
                        @click="activeTab = 'past-masters'"
                    />
                </div>

                <div v-if="activeTab === 'officers'" class="space-y-4">
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <Button label="Reset" severity="secondary" outlined @click="resetAssignmentForm" />
                        <Button label="Save Assignments" :loading="assignmentForm.processing" @click="saveAssignments" />
                    </div>

                    <div
                        v-for="(assignment, index) in assignmentForm.assignments"
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
                            <p v-if="assignmentForm.errors[`assignments.${index}.person_id`]" class="mt-1 text-sm text-red-500">
                                {{ assignmentForm.errors[`assignments.${index}.person_id`] }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <Button label="Add Past Master" icon="pi pi-plus" @click="openCreatePastMaster" />
                    </div>

                    <DataTable :value="pastMasters" data-key="id" responsive-layout="scroll">
                        <Column field="year" header="Year" style="min-width: 8rem" />
                        <Column field="name" header="Name" style="min-width: 16rem" />
                        <Column header="Linked Member" style="min-width: 16rem">
                            <template #body="{ data }">
                                <span v-if="data.person">
                                    {{ data.person.display_name }}
                                    <span v-if="data.person.member_number" class="text-xs text-surface-500">
                                        (#{{ data.person.member_number }})
                                    </span>
                                </span>
                                <span v-else>Unlinked</span>
                            </template>
                        </Column>
                        <Column header="Status" style="min-width: 10rem">
                            <template #body="{ data }">
                                <Tag :value="data.is_deceased ? 'Deceased' : 'Living'" :severity="data.is_deceased ? 'danger' : 'success'" />
                            </template>
                        </Column>
                        <Column header="Actions" style="min-width: 8rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <Button
                                        text
                                        rounded
                                        icon="pi pi-pencil"
                                        aria-label="Edit past master"
                                        v-tooltip.top="'Edit'"
                                        @click="openEditPastMaster(data)"
                                    />
                                    <Button
                                        text
                                        rounded
                                        severity="danger"
                                        icon="pi pi-trash"
                                        aria-label="Delete past master"
                                        v-tooltip.top="'Delete'"
                                        @click="deletePastMaster(data)"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </template>
        </Card>

        <Dialog
            v-model:visible="pastMasterDialogVisible"
            :header="editingPastMasterId ? 'Edit Past Master' : 'Add Past Master'"
            modal
            class="w-full max-w-2xl"
            @hide="closePastMasterDialog"
        >
            <div class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Year</label>
                        <InputText v-model="pastMasterForm.year" class="w-full" />
                        <p v-if="pastMasterForm.errors.year" class="mt-1 text-sm text-red-500">{{ pastMasterForm.errors.year }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Name</label>
                        <InputText v-model="pastMasterForm.name" class="w-full" />
                        <p v-if="pastMasterForm.errors.name" class="mt-1 text-sm text-red-500">{{ pastMasterForm.errors.name }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Linked Member (Optional)</label>
                    <Select
                        v-model="pastMasterForm.person_id"
                        :options="selectableMembers"
                        option-label="label"
                        option-value="id"
                        class="w-full"
                        placeholder="No linked member"
                        show-clear
                        filter
                    />
                    <p v-if="pastMasterForm.errors.person_id" class="mt-1 text-sm text-red-500">{{ pastMasterForm.errors.person_id }}</p>
                    <p v-if="selectedLinkedMember?.is_deceased" class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                        Linked member is marked deceased in the roster. This entry will display as deceased.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model="pastMasterForm.deceased" binary input-id="past-master-deceased" />
                    <label for="past-master-deceased" class="text-sm">Marked deceased</label>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Cancel" severity="secondary" outlined @click="closePastMasterDialog" />
                    <Button
                        :label="editingPastMasterId ? 'Save Changes' : 'Add Past Master'"
                        :loading="pastMasterForm.processing"
                        @click="savePastMaster"
                    />
                </div>
            </div>
        </Dialog>
    </div>
</template>
