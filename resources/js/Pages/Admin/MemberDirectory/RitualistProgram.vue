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
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Tag from 'primevue/tag';

const props = defineProps({
    canManage: { type: Boolean, default: false },
    programs: { type: Array, default: () => [] },
    degreeOptions: { type: Array, default: () => [] },
    achievementLevels: { type: Array, default: () => [] },
    memberOptions: { type: Array, default: () => [] },
    enrollments: { type: Array, default: () => [] },
});

const groupedPrograms = computed(() => {
    const grouped = new Map(props.degreeOptions.map((option) => [option.value, []]));

    for (const program of props.programs) {
        if (!grouped.has(program.degree_group)) {
            grouped.set(program.degree_group, []);
        }
        grouped.get(program.degree_group).push(program);
    }

    return props.degreeOptions.map((option) => ({
        key: option.value,
        label: option.label,
        programs: grouped.get(option.value) ?? [],
    }));
});

const ritualFilterProgramIds = ref([]);

const ritualFilterOptions = computed(() => props.degreeOptions.map((degreeOption) => ({
    degree_label: degreeOption.label,
    items: props.programs
        .filter((program) => program.degree_group === degreeOption.value)
        .map((program) => ({
            id: program.id,
            label: `${program.name} (${program.points})`,
        })),
})));

const filteredEnrollments = computed(() => {
    const requiredProgramIds = ritualFilterProgramIds.value.map((id) => Number(id));

    if (requiredProgramIds.length === 0) {
        return props.enrollments;
    }

    return props.enrollments.filter((enrollment) => {
        const completed = new Set((enrollment.completed_program_ids ?? []).map((id) => Number(id)));
        return requiredProgramIds.every((programId) => completed.has(programId));
    });
});

const availableMemberOptions = computed(() => props.memberOptions
    .filter((member) => !member.is_enrolled)
    .map((member) => ({
        id: member.id,
        label: member.member_number
            ? `${member.display_name} (#${member.member_number})`
            : member.display_name,
        is_deceased: Boolean(member.is_deceased),
    })));

const enrollmentForm = useForm({
    person_id: null,
});

const enrollmentDialogVisible = ref(false);

const openEnrollmentDialog = () => {
    if (!props.canManage) {
        return;
    }

    enrollmentForm.reset();
    enrollmentForm.clearErrors();
    enrollmentDialogVisible.value = true;
};

const closeEnrollmentDialog = () => {
    enrollmentDialogVisible.value = false;
    enrollmentForm.clearErrors();
};

const addEnrollment = () => {
    if (!props.canManage) {
        return;
    }

    enrollmentForm.post(route('manage.member-directory.ritualist.enrollments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            enrollmentForm.reset();
            enrollmentForm.clearErrors();
            enrollmentDialogVisible.value = false;
        },
    });
};

const removeEnrollment = (enrollment) => {
    if (!props.canManage) {
        return;
    }

    if (!window.confirm(`Remove ${enrollment.person?.display_name ?? 'this member'} from ritualist tracking?`)) {
        return;
    }

    router.delete(route('manage.member-directory.ritualist.enrollments.destroy', { ritualEnrollment: enrollment.id }), {
        preserveScroll: true,
    });
};

const completionDialogVisible = ref(false);
const selectedEnrollment = ref(null);

const completionForm = useForm({
    program_ids: [],
});

const selectedProgramIds = computed({
    get: () => completionForm.program_ids,
    set: (value) => {
        completionForm.program_ids = Array.from(new Set((value ?? []).map((id) => Number(id))));
    },
});

const openCompletionDialog = (enrollment) => {
    selectedEnrollment.value = enrollment;
    completionForm.program_ids = [...(enrollment.completed_program_ids ?? [])];
    completionForm.clearErrors();
    completionDialogVisible.value = true;
};

const closeCompletionDialog = () => {
    completionDialogVisible.value = false;
    completionForm.clearErrors();
};

const selectedPoints = computed(() => {
    const selected = new Set(selectedProgramIds.value.map((id) => Number(id)));
    return props.programs.reduce((total, program) => {
        return selected.has(Number(program.id)) ? total + Number(program.points) : total;
    }, 0);
});

const selectedLevelLabel = computed(() => {
    const levels = [...props.achievementLevels].sort((a, b) => Number(a.points) - Number(b.points));
    let label = 'In Progress';

    for (const level of levels) {
        if (selectedPoints.value >= Number(level.points)) {
            label = level.label;
        }
    }

    return label;
});

const saveCompletions = () => {
    if (!props.canManage) {
        return;
    }

    if (!selectedEnrollment.value) {
        return;
    }

    completionForm.put(
        route('manage.member-directory.ritualist.enrollments.completions.update', {
            ritualEnrollment: selectedEnrollment.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                completionDialogVisible.value = false;
            },
        }
    );
};

const programDialogVisible = ref(false);
const editingProgram = ref(null);

const programForm = useForm({
    name: '',
    points: 0,
    degree_group: 'entered_apprentice',
    display_order: null,
});

const openCreateProgramDialog = () => {
    if (!props.canManage) {
        return;
    }

    editingProgram.value = null;
    programForm.reset();
    programForm.clearErrors();
    programForm.points = 0;
    programForm.degree_group = 'entered_apprentice';
    programForm.display_order = null;
    programDialogVisible.value = true;
};

const openEditProgramDialog = (program) => {
    if (!props.canManage) {
        return;
    }

    editingProgram.value = program;
    programForm.clearErrors();
    programForm.name = program.name;
    programForm.points = Number(program.points);
    programForm.degree_group = program.degree_group;
    programForm.display_order = Number(program.display_order ?? 0);
    programDialogVisible.value = true;
};

const closeProgramDialog = () => {
    programDialogVisible.value = false;
    programForm.clearErrors();
};

const saveProgram = () => {
    if (!props.canManage) {
        return;
    }

    if (editingProgram.value) {
        programForm.put(
            route('manage.member-directory.ritualist.programs.update', {
                ritualProgram: editingProgram.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    programDialogVisible.value = false;
                },
            }
        );
        return;
    }

    programForm.post(route('manage.member-directory.ritualist.programs.store'), {
        preserveScroll: true,
        onSuccess: () => {
            programDialogVisible.value = false;
        },
    });
};

const removeProgram = (program) => {
    if (!props.canManage) {
        return;
    }

    if (!window.confirm(`Delete ritual "${program.name}"?`)) {
        return;
    }

    router.delete(route('manage.member-directory.ritualist.programs.destroy', { ritualProgram: program.id }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm text-surface-500 dark:text-surface-400">
                    <Link :href="route('manage.member-directory.index')" class="hover:underline">Back to Directory</Link>
                </div>
                <h1 class="mt-2 text-3xl font-semibold text-surface-900 dark:text-surface-0">Ritualist Program</h1>
                <p class="mt-2 text-surface-600 dark:text-surface-300">
                    {{ canManage
                        ? 'Track member proficiencies and manage rituals from the roster.'
                        : 'View tracked members, proficiencies, and rituals.'
                    }}
                </p>
            </div>
        </div>

        <Card>
            <template #title>Achievement Levels</template>
            <template #content>
                <div class="flex flex-wrap gap-2">
                    <Tag
                        v-for="level in achievementLevels"
                        :key="level.label"
                        :value="`${level.label} (${level.points})`"
                        severity="contrast"
                    />
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Tracked Members</template>
            <template #content>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Filter by Ritual Proficiency</label>
                    <MultiSelect
                        v-model="ritualFilterProgramIds"
                        :options="ritualFilterOptions"
                        option-label="label"
                        option-value="id"
                        option-group-label="degree_label"
                        option-group-children="items"
                        filter
                        placeholder="All rituals"
                        class="w-full"
                        :max-selected-labels="2"
                    >
                        <template #optiongroup="{ option }">
                            <div class="text-sm font-medium">{{ option.degree_label }}</div>
                        </template>
                    </MultiSelect>
                    <p class="mt-1 text-xs text-surface-500">
                        Shows members who are proficient in all selected rituals.
                    </p>
                </div>

                <div v-if="canManage" class="mb-4 flex justify-end">
                    <Button label="Add Member" icon="pi pi-plus" @click="openEnrollmentDialog" />
                </div>

                <DataTable :value="filteredEnrollments" data-key="id" responsive-layout="scroll">
                    <Column header="Member" style="min-width: 16rem">
                        <template #body="{ data }">
                            <div class="font-medium">{{ data.person?.display_name }}</div>
                            <div class="text-xs text-surface-500">
                                {{ data.person?.member_number ? `#${data.person.member_number}` : 'No member #' }}
                            </div>
                        </template>
                    </Column>
                    <Column header="Status" style="min-width: 8rem">
                        <template #body="{ data }">
                            <Tag :value="data.person?.is_deceased ? 'Deceased' : 'Living'" :severity="data.person?.is_deceased ? 'danger' : 'success'" />
                        </template>
                    </Column>
                    <Column field="completed_count" header="Completed" style="min-width: 7rem" />
                    <Column field="total_points" header="Points" style="min-width: 7rem" />
                    <Column field="level_label" header="Level" style="min-width: 10rem" />
                    <Column header="Actions" style="min-width: 12rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <Button
                                    :label="canManage ? 'Proficiencies' : 'View'"
                                    size="small"
                                    text
                                    @click="openCompletionDialog(data)"
                                />
                                <Button
                                    v-if="canManage"
                                    icon="pi pi-trash"
                                    size="small"
                                    severity="danger"
                                    text
                                    aria-label="Remove member"
                                    @click="removeEnrollment(data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Card>
            <template #title>Rituals</template>
            <template #content>
                <div v-if="canManage" class="mb-4 flex justify-end">
                    <Button label="Add Ritual" icon="pi pi-plus" @click="openCreateProgramDialog" />
                </div>

                <div class="space-y-6">
                    <div v-for="group in groupedPrograms" :key="group.key" class="space-y-2">
                        <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-0">
                            {{ group.label }}
                        </h3>
                        <DataTable :value="group.programs" data-key="id" responsive-layout="scroll">
                            <Column field="name" header="Ritual" style="width: 65%" />
                            <Column field="points" header="Points" style="width: 12%" />
                            <Column field="display_order" header="Order" style="width: 11%" />
                            <Column v-if="canManage" header="Actions" style="width: 12%">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <Button
                                            icon="pi pi-pencil"
                                            text
                                            size="small"
                                            aria-label="Edit program"
                                            @click="openEditProgramDialog(data)"
                                        />
                                        <Button
                                            icon="pi pi-trash"
                                            text
                                            size="small"
                                            severity="danger"
                                            aria-label="Delete program"
                                            @click="removeProgram(data)"
                                        />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </template>
        </Card>

        <Dialog
            v-if="canManage"
            v-model:visible="enrollmentDialogVisible"
            header="Add Tracked Member"
            modal
            class="w-full max-w-xl"
            @hide="closeEnrollmentDialog"
        >
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Member from Roster</label>
                    <Select
                        v-model="enrollmentForm.person_id"
                        :options="availableMemberOptions"
                        option-label="label"
                        option-value="id"
                        placeholder="Select member"
                        class="w-full"
                        filter
                    />
                    <p v-if="enrollmentForm.errors.person_id" class="mt-1 text-sm text-red-500">
                        {{ enrollmentForm.errors.person_id }}
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Cancel" severity="secondary" outlined @click="closeEnrollmentDialog" />
                    <Button
                        label="Add Member"
                        icon="pi pi-plus"
                        :loading="enrollmentForm.processing"
                        :disabled="!enrollmentForm.person_id"
                        @click="addEnrollment"
                    />
                </div>
            </div>
        </Dialog>

        <Dialog
            v-model:visible="completionDialogVisible"
            header="Member Proficiencies"
            modal
            class="w-full max-w-4xl"
            @hide="closeCompletionDialog"
        >
            <div class="space-y-4">
                <div>
                    <div class="text-lg font-semibold">{{ selectedEnrollment?.person?.display_name }}</div>
                    <div class="text-sm text-surface-500">
                        {{ selectedEnrollment?.person?.member_number ? `#${selectedEnrollment.person.member_number}` : 'No member #' }}
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Tag :value="`Points: ${selectedPoints}`" severity="info" />
                    <Tag :value="`Level: ${selectedLevelLabel}`" severity="contrast" />
                </div>

                <div class="space-y-5">
                    <div v-for="group in groupedPrograms" :key="`dialog-${group.key}`" class="space-y-2">
                        <h4 class="text-base font-semibold text-surface-900 dark:text-surface-0">
                            {{ group.label }}
                        </h4>
                        <div class="space-y-2 rounded-lg border border-surface-200 p-3 dark:border-surface-700">
                            <div
                                v-for="program in group.programs"
                                :key="program.id"
                                class="flex items-start justify-between gap-3"
                            >
                                <label :for="`program-${program.id}`" class="flex items-start gap-2 text-sm">
                                    <Checkbox
                                        :input-id="`program-${program.id}`"
                                        v-model="selectedProgramIds"
                                        :value="program.id"
                                        :disabled="!canManage"
                                    />
                                    <span>{{ program.name }}</span>
                                </label>
                                <span class="text-sm text-surface-600 dark:text-surface-300">{{ program.points }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Cancel" severity="secondary" outlined @click="closeCompletionDialog" />
                    <Button
                        v-if="canManage"
                        label="Save Proficiencies"
                        :loading="completionForm.processing"
                        @click="saveCompletions"
                    />
                </div>
            </div>
        </Dialog>

        <Dialog
            v-if="canManage"
            v-model:visible="programDialogVisible"
            :header="editingProgram ? 'Edit Ritual' : 'Add Ritual'"
            modal
            class="w-full max-w-xl"
            @hide="closeProgramDialog"
        >
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Ritual Name</label>
                    <InputText v-model="programForm.name" class="w-full" />
                    <p v-if="programForm.errors.name" class="mt-1 text-sm text-red-500">{{ programForm.errors.name }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Points</label>
                        <InputNumber v-model="programForm.points" class="w-full" :min="0" :use-grouping="false" />
                        <p v-if="programForm.errors.points" class="mt-1 text-sm text-red-500">{{ programForm.errors.points }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Degree Group</label>
                        <Select
                            v-model="programForm.degree_group"
                            :options="degreeOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                        />
                        <p v-if="programForm.errors.degree_group" class="mt-1 text-sm text-red-500">{{ programForm.errors.degree_group }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Display Order</label>
                        <InputNumber v-model="programForm.display_order" class="w-full" :min="0" :use-grouping="false" />
                        <p v-if="programForm.errors.display_order" class="mt-1 text-sm text-red-500">{{ programForm.errors.display_order }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Cancel" severity="secondary" outlined @click="closeProgramDialog" />
                    <Button :label="editingProgram ? 'Save Changes' : 'Add Program'" :loading="programForm.processing" @click="saveProgram" />
                </div>
            </div>
        </Dialog>
    </div>
</template>
