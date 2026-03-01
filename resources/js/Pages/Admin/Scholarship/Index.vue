<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";

import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Tag from "primevue/tag";
import InputText from "primevue/inputtext";
import Dialog from "primevue/dialog";
import Paginator from "primevue/paginator";
import Select from "primevue/select";

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const props = defineProps({
    applications: Object,
    filters: Object,
    statusOptions: Array,
    cycleYearOptions: Array,
});

const local = ref({
    cycle_year: props.filters?.cycle_year ?? new Date().getFullYear(),
    status: props.filters?.status ?? "",
    search: props.filters?.search ?? "",
});

const statusOptionsNoAll = computed(() => (props.statusOptions ?? []).filter(o => o.value !== ""));

const applyFilters = (pageNum = 1) => {
    router.get(
        route("manage.scholarships.index"),
        { ...local.value, page: pageNum },
        { preserveState: true, replace: true }
    );
};

const clearFilters = () => {
    local.value.status = "";
    local.value.search = "";
    applyFilters(1);
};

const first = computed(() => ((props.applications.current_page - 1) * props.applications.per_page) || 0);

const onPage = (e) => {
    applyFilters(e.page + 1);
};

const statusSeverity = (status) => {
    switch (status) {
        case "new": return "info";
        case "in_review": return "warning";
        case "finalist": return "success";
        case "awarded": return "success";
        case "declined": return "danger";
        case "pending_verification": return "secondary";
        default: return "secondary";
    }
};

const statusLabel = (status) => {
    switch (status) {
        case "": return "All";
        case "new": return "New";
        case "in_review": return "In Review";
        case "finalist": return "Finalist";
        case "awarded": return "Awarded";
        case "declined": return "Declined";
        case "pending_verification": return "Pending";
        default: return status;
    }
};

const updatingStatusId = ref(null);

const updateStatus = (app, newStatus) => {
    if (!app?.id || !newStatus || newStatus === app.status) return;

    updatingStatusId.value = app.id;

    router.patch(
        route("manage.scholarships.status.update", app.id),
        { status: newStatus },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                updatingStatusId.value = null;
            },
        }
    );
};

const deleteDialogVisible = ref(false);
const deleteTarget = ref(null);

const openDelete = (app) => {
    deleteTarget.value = app;
    deleteDialogVisible.value = true;
};

const confirmDelete = () => {
    if (!deleteTarget.value?.id) return;

    router.delete(route("manage.scholarships.destroy", deleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogVisible.value = false;
            deleteTarget.value = null;
        },
    });
};

</script>

<template>
    <AppLayout title="Scholarship Applications">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">Scholarship Applications</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-surface-0 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="dark:bg-surface-900 dark:text-surface-200 font-sans">
                        <div class="p-6 space-y-4">
                            <div v-if="flashSuccess" class="p-3 rounded-md border border-green-200 bg-green-50 text-green-900 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-100">
                                {{ flashSuccess }}
                            </div>
                            <div v-if="flashError" class="p-3 rounded-md border border-red-200 bg-red-50 text-red-900 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-100">
                                {{ flashError }}
                            </div>

                            <!-- Filters -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                                <div class="flex flex-col">
                                    <label class="font-medium text-surface-700 dark:text-surface-300">Cycle Year</label>
                                    <Select v-model="local.cycle_year" :options="cycleYearOptions" optionLabel="label" optionValue="value" class="mt-1 w-full" />
                                </div>

                                <div class="flex flex-col">
                                    <label class="font-medium text-surface-700 dark:text-surface-300">Status</label>
                                    <Select v-model="local.status" :options="statusOptions" optionLabel="label" optionValue="value" class="mt-1 w-full" />
                                </div>

                                <div class="flex flex-col md:col-span-2">
                                    <label class="font-medium text-surface-700 dark:text-surface-300">Search</label>
                                    <InputText v-model="local.search" class="mt-1 w-full" placeholder="Name or email..." @keyup.enter="applyFilters(1)" />
                                </div>

                                <div class="md:col-span-4 flex gap-2 justify-end">
                                    <Button label="Clear" severity="secondary" outlined @click="clearFilters" />
                                    <Button label="Apply" class="bg-primary-600 hover:bg-primary-700 text-surface-0 dark:text-surface-950" @click="applyFilters(1)" />
                                </div>
                            </div>

                            <DataTable :value="applications.data" class="mt-2" responsiveLayout="scroll">
                                <Column header="Status">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-2 min-w-[12rem]">
                                            <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                                            <Select
                                                :modelValue="data.status"
                                                :options="statusOptionsNoAll"
                                                optionLabel="label"
                                                optionValue="value"
                                                placeholder="Set status..."
                                                class="w-full"
                                                :disabled="updatingStatusId === data.id"
                                                @update:modelValue="val => updateStatus(data, val)"
                                            />
                                            <small v-if="updatingStatusId === data.id" class="opacity-70">Updating…</small>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Applicant">
                                    <template #body="{ data }">
                                        <div class="font-medium">{{ data.first_name }} {{ data.last_name }}</div>
                                        <div class="text-sm opacity-70">{{ data.email }}</div>
                                    </template>
                                </Column>

                                <Column field="current_school" header="School" />
                                <Column field="education_level" header="Education" />
                                <Column field="gpa" header="GPA" />

                                <Column header="Avg / #">
                                    <template #body="{ data }">
                                        <div class="font-medium">
                                            {{ data.reviews_avg_score ? Number(data.reviews_avg_score).toFixed(2) : "—" }}
                                            <span class="opacity-70">/ {{ data.reviews_count }}</span>
                                        </div>
                                        <div class="text-sm opacity-70">My: {{ data.my_score ?? "—" }}</div>
                                    </template>
                                </Column>

                                <Column header="Action">
                                    <template #body="{ data }">
                                        <Link :href="route('manage.scholarships.show', data.id)">
                                            <Button label="Review" icon="pi pi-chevron-right" class="bg-primary-600 hover:bg-primary-700 text-surface-0 dark:text-surface-950" size="small" />
                                        </Link>

                                        <Button
                                            class="ml-2"
                                            icon="pi pi-trash"
                                            severity="danger"
                                            outlined
                                            size="small"
                                            @click="openDelete(data)"
                                        />
                                    </template>
                                </Column>
                            </DataTable>

                            <div class="flex justify-end">
                                <Paginator
                                    :first="first"
                                    :rows="applications.per_page"
                                    :totalRecords="applications.total"
                                    @page="onPage"
                                />
                            </div>

                            <!-- Delete confirmation dialog -->
                            <Dialog
                                v-model:visible="deleteDialogVisible"
                                modal
                                header="Delete application"
                                :style="{ width: '32rem' }"
                            >
                                <div class="space-y-3">
                                    <p class="opacity-80">
                                        This will remove the application from the review queue and delete any uploaded attachments.
                                    </p>
                                    <p v-if="deleteTarget" class="font-medium">
                                        {{ deleteTarget.first_name }} {{ deleteTarget.last_name }} ({{ deleteTarget.email }})
                                    </p>
                                </div>

                                <template #footer>
                                    <div class="flex gap-2 justify-end">
                                        <Button label="Cancel" severity="secondary" outlined @click="deleteDialogVisible = false" />
                                        <Button label="Delete" severity="danger" @click="confirmDelete" />
                                    </div>
                                </template>
                            </Dialog>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
