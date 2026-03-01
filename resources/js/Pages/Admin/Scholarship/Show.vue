<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, ref } from "vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";

import Card from "primevue/card";
import Divider from "primevue/divider";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputNumber from "primevue/inputnumber";
import Textarea from "primevue/textarea";
import Tag from "primevue/tag";

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const props = defineProps({
    application: Object,
    myReview: Object,
});

const form = useForm({
    score: props.myReview?.score ?? null,
    notes: props.myReview?.notes ?? "",
});

const saving = ref(false);

const save = () => {
    saving.value = true;
    form.post(route("manage.scholarships.review.upsert", props.application.id), {
        preserveScroll: true,
        onFinish: () => (saving.value = false),
    });
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
</script>

<template>
    <AppLayout title="Review Application">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl leading-tight">Review Application</h2>
                <Link :href="route('manage.scholarships.index')">
                    <Button label="Back" icon="pi pi-arrow-left" severity="secondary" outlined />
                </Link>
            </div>
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

                            <Card>
                                <template #title>
                                    <div class="flex items-center gap-3">
                                        <div>{{ application.first_name }} {{ application.last_name }}</div>
                                        <Tag :value="application.status" :severity="statusSeverity(application.status)" />
                                    </div>
                                </template>
                                <template #content>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <div class="font-medium">Email</div>
                                            <div class="opacity-80">{{ application.email }}</div>
                                        </div>
                                        <div>
                                            <div class="font-medium">Phone</div>
                                            <div class="opacity-80">{{ application.phone ?? "—" }}</div>
                                        </div>
                                        <div>
                                            <div class="font-medium">School</div>
                                            <div class="opacity-80">{{ application.current_school ?? "—" }}</div>
                                        </div>

                                        <div>
                                            <div class="font-medium">Education</div>
                                            <div class="opacity-80">{{ application.education_level ?? "—" }}</div>
                                        </div>
                                        <div>
                                            <div class="font-medium">GPA</div>
                                            <div class="opacity-80">{{ application.gpa ?? "—" }} <span class="opacity-70">{{ application.gpa_scale ? `(${application.gpa_scale})` : "" }}</span></div>
                                        </div>
                                        <div>
                                            <div class="font-medium">Lodge Relationship</div>
                                            <div class="opacity-80">{{ application.lodge_relationship ?? "—" }}</div>
                                        </div>
                                    </div>

                                    <Divider />

                                    <div class="space-y-3">
                                        <div>
                                            <div class="font-medium">Reason</div>
                                            <div class="opacity-90 whitespace-pre-line">{{ application.reason ?? "—" }}</div>
                                        </div>
                                        <div>
                                            <div class="font-medium">Activities</div>
                                            <div class="opacity-90 whitespace-pre-line">{{ application.activities ?? "—" }}</div>
                                        </div>
                                        <div>
                                            <div class="font-medium">Awards</div>
                                            <div class="opacity-90 whitespace-pre-line">{{ application.awards ?? "—" }}</div>
                                        </div>
                                    </div>

                                    <Divider />

                                    <div>
                                        <div class="font-medium mb-2">Attachments</div>
                                        <div class="flex flex-wrap gap-2">
                                            <a v-for="(a, idx) in (application.attachments ?? [])" :key="idx" :href="a.url" target="_blank" rel="noopener">
                                                <Button icon="pi pi-download" :label="a.name" severity="secondary" outlined />
                                            </a>
                                            <span v-if="!application.attachments || application.attachments.length === 0" class="opacity-70">No attachments.</span>
                                        </div>
                                    </div>
                                </template>
                            </Card>

                            <Card>
                                <template #title>My Score</template>
                                <template #content>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="flex flex-col">
                                            <label class="font-medium text-surface-700 dark:text-surface-300">Score (0.0 – 10.0)</label>
                                            <InputNumber
                                                v-model="form.score"
                                                class="mt-1 w-full"
                                                :min="0"
                                                :max="10"
                                                :step="0.1"
                                                :minFractionDigits="1"
                                                :maxFractionDigits="1"
                                            />
                                            <small v-if="form.errors.score" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.score }}</small>
                                        </div>

                                        <div class="flex flex-col">
                                            <label class="font-medium text-surface-700 dark:text-surface-300">Reason / Notes</label>
                                            <Textarea v-model="form.notes" rows="4" class="mt-1 w-full" autoResize />
                                            <small v-if="form.errors.notes" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.notes }}</small>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <Button
                                            label="Save Score"
                                            icon="pi pi-save"
                                            class="bg-primary-600 hover:bg-primary-700 text-surface-0 dark:text-surface-950"
                                            :loading="saving || form.processing"
                                            @click="save"
                                        />
                                    </div>
                                </template>
                            </Card>

                            <Card>
                                <template #title>All Reviews</template>
                                <template #content>
                                    <DataTable :value="application.reviews ?? []" responsiveLayout="scroll">
                                        <Column header="Reviewer">
                                            <template #body="{ data }">
                                                {{ data.user?.name ?? "—" }}
                                            </template>
                                        </Column>
                                        <Column header="Score">
                                            <template #body="{ data }">
                                                {{ Number(data.score).toFixed(2) }}
                                            </template>
                                        </Column>
                                        <Column header="Notes">
                                            <template #body="{ data }">
                                                <div class="whitespace-pre-line">{{ data.notes ?? "—" }}</div>
                                            </template>
                                        </Column>
                                        <Column header="Updated">
                                            <template #body="{ data }">
                                                {{ data.updated_at }}
                                            </template>
                                        </Column>
                                    </DataTable>
                                </template>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
