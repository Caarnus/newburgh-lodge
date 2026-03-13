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
import Textarea from 'primevue/textarea';

const props = defineProps({
    batches: { type: Array, default: () => [] },
    selectedBatch: { type: Object, default: null },
    selectedRows: { type: Array, default: () => [] },
    maxRowsShown: { type: Number, default: 500 },
    memberStatusOptions: { type: Array, default: () => [] },
    rowStatusOptions: { type: Array, default: () => [] },
    rowFilters: { type: Object, default: () => ({}) },
});

const fileInput = ref(null);
const uploadForm = useForm({
    file: null,
    source_label: null,
});

const applyForm = useForm({
    include_possible_matches: false,
});
const editRowDialogVisible = ref(false);
const editingRow = ref(null);
const editRowForm = useForm({
    member_number: '',
    status: null,
    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',
    preferred_name: '',
    email: '',
    phone: '',
    birth_date: '',
    ea_date: '',
    fc_date: '',
    mm_date: '',
    honorary_date: '',
    demit_date: '',
    is_deceased: false,
    death_date: '',
    past_master: false,
    review_notes: '',
});
const rowFilters = ref({
    row_status: props.rowFilters?.row_status ?? null,
    error_only: Boolean(props.rowFilters?.error_only ?? false),
    error_query: props.rowFilters?.error_query ?? '',
});

const canApplySelected = computed(() => ['staged', 'failed'].includes(props.selectedBatch?.status));

const batchStatusSeverity = (status) => ({
    uploaded: 'info',
    staged: 'warn',
    applied: 'success',
    failed: 'danger',
}[status] ?? 'secondary');

const rowStatusSeverity = (status) => ({
    exact_match: 'info',
    possible_match: 'warn',
    new_person: 'contrast',
    ignored: 'secondary',
    applied: 'success',
    failed: 'danger',
}[status] ?? 'secondary');

const formatDateTime = (value) => value ? new Date(value).toLocaleString() : '—';

const filterQuery = () => {
    const errorQuery = (rowFilters.value.error_query || '').trim();

    return {
        row_status: rowFilters.value.row_status || undefined,
        error_only: rowFilters.value.error_only ? 1 : undefined,
        error_query: errorQuery !== '' ? errorQuery : undefined,
    };
};

const indexQuery = (batchId = null) => ({
    batch: batchId ?? undefined,
    ...filterQuery(),
});

const selectBatch = (id) => {
    router.get(route('manage.member-directory.imports.index'), indexQuery(id), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const onFileChange = (event) => {
    uploadForm.file = event.target.files?.[0] ?? null;
};

const submitUpload = () => {
    uploadForm.post(route('manage.member-directory.imports.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const applySelectedBatch = () => {
    if (!props.selectedBatch) {
        return;
    }

    applyForm.post(route('manage.member-directory.imports.apply', {
        importBatch: props.selectedBatch.id,
        ...filterQuery(),
    }), {
        preserveScroll: true,
    });
};

const applyRowFilters = () => {
    if (!props.selectedBatch) {
        return;
    }

    router.get(route('manage.member-directory.imports.index'), indexQuery(props.selectedBatch.id), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const clearRowFilters = () => {
    rowFilters.value.row_status = null;
    rowFilters.value.error_only = false;
    rowFilters.value.error_query = '';
    applyRowFilters();
};

const deleteBatch = (batch) => {
    if (!batch) {
        return;
    }

    if (!window.confirm(`Delete batch #${batch.id}? This cannot be undone.`)) {
        return;
    }

    router.delete(route('manage.member-directory.imports.destroy', {
        importBatch: batch.id,
        ...indexQuery(props.selectedBatch?.id ?? null),
    }), {
        preserveScroll: true,
    });
};

const openEditRowDialog = (row) => {
    editingRow.value = row;
    editRowForm.reset();
    editRowForm.clearErrors();

    const payload = row.normalized_payload || {};
    editRowForm.member_number = payload.member_number || '';
    editRowForm.status = payload.status || null;
    editRowForm.first_name = payload.first_name || '';
    editRowForm.middle_name = payload.middle_name || '';
    editRowForm.last_name = payload.last_name || '';
    editRowForm.suffix = payload.suffix || '';
    editRowForm.preferred_name = payload.preferred_name || '';
    editRowForm.email = payload.email || '';
    editRowForm.phone = payload.phone || '';
    editRowForm.birth_date = payload.birth_date || '';
    editRowForm.ea_date = payload.ea_date || '';
    editRowForm.fc_date = payload.fc_date || '';
    editRowForm.mm_date = payload.mm_date || '';
    editRowForm.honorary_date = payload.honorary_date || '';
    editRowForm.demit_date = payload.demit_date || '';
    editRowForm.is_deceased = Boolean(payload.is_deceased ?? false);
    editRowForm.death_date = payload.death_date || '';
    editRowForm.past_master = Boolean(payload.past_master ?? false);
    editRowForm.review_notes = row.review_notes || '';

    editRowDialogVisible.value = true;
};

const closeEditRowDialog = () => {
    editRowDialogVisible.value = false;
    editingRow.value = null;
    editRowForm.clearErrors();
};

const saveRowEdits = () => {
    if (!props.selectedBatch || !editingRow.value) {
        return;
    }

    editRowForm.patch(
        route('manage.member-directory.imports.rows.update', {
            importBatch: props.selectedBatch.id,
            row: editingRow.value.id,
            ...filterQuery(),
        }),
        {
            preserveScroll: true,
            onSuccess: () => closeEditRowDialog(),
        },
    );
};

const personLabel = (row) => {
    const person = row.matched_person;
    if (!person) {
        return '—';
    }

    const memberPart = person.member_number ? ` (#${person.member_number})` : '';
    return `${person.display_name}${memberPart}`;
};

const incomingName = (row) => {
    const payload = row.normalized_payload || {};
    return [payload.first_name, payload.middle_name, payload.last_name, payload.suffix]
        .filter(Boolean)
        .join(' ') || '—';
};
</script>

<template>
    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm text-surface-500 dark:text-surface-400">
                    <Link :href="route('manage.member-directory.members.index')" class="hover:underline">Back to Directory</Link>
                </div>
                <h1 class="mt-2 text-3xl font-semibold text-surface-900 dark:text-surface-0">Roster Imports</h1>
                <p class="mt-2 text-surface-600 dark:text-surface-300">
                    Upload a roster spreadsheet, review staged matches, then apply.
                </p>
            </div>
        </div>

        <Card>
            <template #title>Upload Spreadsheet</template>
            <template #content>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">Roster File</label>
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            class="block w-full rounded-lg border border-surface-300 bg-white px-3 py-2 text-sm dark:border-surface-700 dark:bg-surface-900"
                            @change="onFileChange"
                        />
                        <p v-if="uploadForm.errors.file" class="mt-1 text-sm text-red-500">{{ uploadForm.errors.file }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700 dark:text-surface-200">Source Label</label>
                        <InputText v-model="uploadForm.source_label" class="w-full" placeholder="Quarterly roster" />
                        <p v-if="uploadForm.errors.source_label" class="mt-1 text-sm text-red-500">{{ uploadForm.errors.source_label }}</p>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <Button
                        label="Upload and Stage"
                        :loading="uploadForm.processing"
                        :disabled="uploadForm.processing || !uploadForm.file"
                        @click="submitUpload"
                    />
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Recent Batches</template>
            <template #content>
                <DataTable :value="batches" data-key="id" responsive-layout="scroll">
                    <Column header="Batch">
                        <template #body="{ data }">#{{ data.id }}</template>
                    </Column>
                    <Column header="File">
                        <template #body="{ data }">
                            <div class="font-medium">{{ data.original_filename || '—' }}</div>
                            <div class="text-xs text-surface-500">{{ data.source_label || 'No source label' }}</div>
                        </template>
                    </Column>
                    <Column header="Status">
                        <template #body="{ data }">
                            <Tag :severity="batchStatusSeverity(data.status)" :value="data.status" />
                        </template>
                    </Column>
                    <Column header="Rows">
                        <template #body="{ data }">{{ data.rows_count }}</template>
                    </Column>
                    <Column header="Uploaded">
                        <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
                    </Column>
                    <Column header="Actions">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <Button label="Review" size="small" outlined @click="selectBatch(data.id)" />
                                <Button label="Delete" size="small" severity="danger" outlined @click="deleteBatch(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Card v-if="selectedBatch">
            <template #title>Review Batch #{{ selectedBatch.id }}</template>
            <template #content>
                <div class="mb-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-lg border border-surface-200 p-3 dark:border-surface-700">
                        <div class="text-xs uppercase tracking-wide text-surface-500">Status</div>
                        <Tag class="mt-2" :severity="batchStatusSeverity(selectedBatch.status)" :value="selectedBatch.status" />
                    </div>
                    <div class="rounded-lg border border-surface-200 p-3 dark:border-surface-700">
                        <div class="text-xs uppercase tracking-wide text-surface-500">Summary</div>
                        <div class="mt-2 text-sm">New: {{ selectedBatch.summary?.new_people ?? 0 }}</div>
                        <div class="text-sm">Exact: {{ selectedBatch.summary?.exact_matches ?? 0 }}</div>
                        <div class="text-sm">Possible: {{ selectedBatch.summary?.possible_matches ?? 0 }}</div>
                    </div>
                    <div class="rounded-lg border border-surface-200 p-3 dark:border-surface-700">
                        <div class="text-xs uppercase tracking-wide text-surface-500">Applied</div>
                        <div class="mt-2 text-sm">{{ formatDateTime(selectedBatch.applied_at) }}</div>
                    </div>
                </div>

                <div class="mb-4 flex flex-col gap-3 rounded-lg border border-surface-200 p-3 dark:border-surface-700 md:flex-row md:items-center md:justify-between">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <Checkbox v-model="applyForm.include_possible_matches" binary />
                        <span>Include possible matches when applying</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <Button
                            label="Delete Batch"
                            severity="danger"
                            outlined
                            @click="deleteBatch(selectedBatch)"
                        />
                        <Button
                            label="Apply Batch"
                            :loading="applyForm.processing"
                            :disabled="!canApplySelected || applyForm.processing"
                            @click="applySelectedBatch"
                        />
                    </div>
                </div>

                <div class="mb-4 rounded-lg border border-surface-200 p-3 dark:border-surface-700">
                    <div class="grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium">Row Status</label>
                            <Select
                                v-model="rowFilters.row_status"
                                :options="rowStatusOptions"
                                option-label="label"
                                option-value="value"
                                class="w-full"
                                show-clear
                                placeholder="All statuses"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium">Error Contains</label>
                            <InputText
                                v-model="rowFilters.error_query"
                                class="w-full"
                                placeholder="Duplicate member ID"
                                @keyup.enter="applyRowFilters"
                            />
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <Checkbox v-model="rowFilters.error_only" binary />
                                <span>Only rows with error</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-end gap-2">
                        <Button
                            label="Clear Filters"
                            severity="secondary"
                            outlined
                            @click="clearRowFilters"
                        />
                        <Button
                            label="Apply Filters"
                            @click="applyRowFilters"
                        />
                    </div>
                </div>

                <p class="mb-3 text-xs text-surface-500">
                    Showing up to {{ maxRowsShown }} rows for review.
                </p>
                <DataTable :value="selectedRows" data-key="id" responsive-layout="scroll">
                    <Column header="Row">
                        <template #body="{ data }">{{ data.row_number }}</template>
                    </Column>
                    <Column header="Status">
                        <template #body="{ data }">
                            <Tag :severity="rowStatusSeverity(data.status)" :value="data.status" />
                        </template>
                    </Column>
                    <Column header="Incoming Person">
                        <template #body="{ data }">
                            <div class="font-medium">{{ incomingName(data) }}</div>
                            <div class="text-xs text-surface-500">{{ data.normalized_payload?.member_number || 'No member #' }}</div>
                        </template>
                    </Column>
                    <Column header="Match Strategy">
                        <template #body="{ data }">{{ data.match_strategy || '—' }}</template>
                    </Column>
                    <Column header="Matched Record">
                        <template #body="{ data }">{{ personLabel(data) }}</template>
                    </Column>
                    <Column header="Error">
                        <template #body="{ data }">{{ data.error_message || '—' }}</template>
                    </Column>
                    <Column header="Actions">
                        <template #body="{ data }">
                            <Button
                                label="Edit"
                                size="small"
                                outlined
                                @click="openEditRowDialog(data)"
                            />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog
            v-model:visible="editRowDialogVisible"
            modal
            header="Edit Staged Import Row"
            class="w-full sm:w-[56rem]"
            @hide="closeEditRowDialog"
        >
            <div class="space-y-4">
                <div v-if="editingRow" class="rounded-lg border border-surface-200 bg-surface-50 p-3 text-sm dark:border-surface-700 dark:bg-surface-800">
                    <div class="font-medium">Row #{{ editingRow.row_number }}</div>
                    <div class="mt-1 text-surface-600 dark:text-surface-300">
                        Update normalized data, save, then apply the batch again.
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Member ID</label>
                        <InputText v-model="editRowForm.member_number" class="w-full" />
                        <p v-if="editRowForm.errors.member_number" class="mt-1 text-sm text-red-500">{{ editRowForm.errors.member_number }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Status</label>
                        <Select
                            v-model="editRowForm.status"
                            :options="memberStatusOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                            show-clear
                        />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Birth Date</label>
                        <InputText v-model="editRowForm.birth_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">First Name</label>
                        <InputText v-model="editRowForm.first_name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Middle Name</label>
                        <InputText v-model="editRowForm.middle_name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Last Name</label>
                        <InputText v-model="editRowForm.last_name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Suffix</label>
                        <InputText v-model="editRowForm.suffix" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Preferred Name</label>
                        <InputText v-model="editRowForm.preferred_name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Email</label>
                        <InputText v-model="editRowForm.email" type="email" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Phone</label>
                        <InputText v-model="editRowForm.phone" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">EA Date</label>
                        <InputText v-model="editRowForm.ea_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">FC Date</label>
                        <InputText v-model="editRowForm.fc_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">MM Date</label>
                        <InputText v-model="editRowForm.mm_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Honorary Date</label>
                        <InputText v-model="editRowForm.honorary_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Demit Date</label>
                        <InputText v-model="editRowForm.demit_date" type="date" class="w-full" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-2 block text-sm font-medium">Review Notes</label>
                        <Textarea v-model="editRowForm.review_notes" rows="3" class="w-full" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <Checkbox v-model="editRowForm.is_deceased" binary />
                        <span>Marked deceased</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <Checkbox v-model="editRowForm.past_master" binary />
                        <span>Past Master</span>
                    </label>
                    <div v-if="editRowForm.is_deceased" class="w-full md:w-64">
                        <label class="mb-2 block text-sm font-medium">Death Date</label>
                        <InputText v-model="editRowForm.death_date" type="date" class="w-full" />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    text
                    @click="closeEditRowDialog"
                />
                <Button
                    label="Save Row"
                    :loading="editRowForm.processing"
                    @click="saveRowEdits"
                />
            </template>
        </Dialog>
    </div>
</template>
