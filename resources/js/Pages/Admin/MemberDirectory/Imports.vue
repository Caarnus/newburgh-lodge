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
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';

const props = defineProps({
    batches: { type: Array, default: () => [] },
    selectedBatch: { type: Object, default: null },
    selectedRows: { type: Array, default: () => [] },
    maxRowsShown: { type: Number, default: 500 },
});

const fileInput = ref(null);
const uploadForm = useForm({
    file: null,
    source_label: null,
});

const applyForm = useForm({
    include_possible_matches: false,
});

const canApplySelected = computed(() => props.selectedBatch?.status === 'staged');

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

const selectBatch = (id) => {
    router.get(route('manage.member-directory.imports.index'), { batch: id }, {
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

    applyForm.post(route('manage.member-directory.imports.apply', { importBatch: props.selectedBatch.id }), {
        preserveScroll: true,
    });
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
                            <Button label="Review" size="small" outlined @click="selectBatch(data.id)" />
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
                    <Button
                        label="Apply Batch"
                        :loading="applyForm.processing"
                        :disabled="!canApplySelected || applyForm.processing"
                        @click="applySelectedBatch"
                    />
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
                </DataTable>
            </template>
        </Card>
    </div>
</template>
