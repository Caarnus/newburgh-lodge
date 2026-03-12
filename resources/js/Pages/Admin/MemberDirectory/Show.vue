<script>
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Tag from 'primevue/tag';

const props = defineProps({
    person: { type: Object, required: true },
    fromSection: { type: String, default: null },
});

const backRoute = computed(() => ({
    members: 'manage.member-directory.members.index',
    widows: 'manage.member-directory.widows.index',
    orphans: 'manage.member-directory.orphans.index',
    relatives: 'manage.member-directory.relatives.index',
}[props.fromSection] ?? 'manage.member-directory.index'));

const backLabel = computed(() => ({
    members: 'Back to Members',
    widows: 'Back to Widows',
    orphans: 'Back to Orphans',
    relatives: 'Back to Relatives',
}[props.fromSection] ?? 'Back to Directory'));

const classifications = computed(() => [
    props.person.classifications?.is_member ? 'Member' : null,
    props.person.classifications?.is_widow ? 'Widow/Widower' : null,
    props.person.classifications?.is_orphan ? 'Orphan' : null,
    props.person.classifications?.is_relative ? 'Relative' : null,
].filter(Boolean));

const formatDate = (value) => value ? new Date(`${value}T00:00:00`).toLocaleDateString() : '—';
const formatDateTime = (value) => value ? new Date(value).toLocaleString() : '—';
</script>

<template>
    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm text-surface-500 dark:text-surface-400">
                    <Link :href="route(backRoute)" class="hover:underline">{{ backLabel }}</Link>
                </div>
                <h1 class="mt-2 text-3xl font-semibold text-surface-900 dark:text-surface-0">{{ person.display_name }}</h1>
                <div class="mt-3 flex flex-wrap gap-2">
                    <Tag v-for="label in classifications" :key="label" :value="label" />
                    <Tag :severity="person.is_deceased ? 'danger' : 'success'" :value="person.is_deceased ? 'Deceased' : 'Living'" />
                </div>
            </div>
            <Link
                :href="route(backRoute)"
                class="inline-flex items-center rounded-lg border border-surface-300 px-4 py-2 text-sm font-medium text-surface-700 transition hover:bg-surface-50 dark:border-surface-700 dark:text-surface-100 dark:hover:bg-surface-800"
            >
                {{ backLabel }}
            </Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <template #title>Profile Summary</template>
                <template #content>
                    <div class="grid gap-3 text-sm md:grid-cols-2">
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">Email</div>
                            <div>{{ person.email || '—' }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">Phone</div>
                            <div>{{ person.phone || '—' }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">Birth Date</div>
                            <div>{{ formatDate(person.birth_date) }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">Death Date</div>
                            <div>{{ formatDate(person.death_date) }}</div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="font-medium text-surface-700 dark:text-surface-200">Address</div>
                            <div>{{ person.address_line_1 || '—' }}</div>
                            <div v-if="person.address_line_2">{{ person.address_line_2 }}</div>
                            <div>{{ [person.city, person.state, person.postal_code].filter(Boolean).join(', ') || '—' }}</div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="font-medium text-surface-700 dark:text-surface-200">Notes</div>
                            <div>{{ person.notes || '—' }}</div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Member Details</template>
                <template #content>
                    <div v-if="person.member_profile" class="grid gap-3 text-sm md:grid-cols-2">
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">Member #</div>
                            <div>{{ person.member_profile.member_number || '—' }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">Status</div>
                            <div>{{ person.member_profile.status || '—' }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">Type</div>
                            <div>{{ person.member_profile.member_type || '—' }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">Directory Visible</div>
                            <div>{{ person.member_profile.directory_visible ? 'Yes' : 'No' }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">EA Date</div>
                            <div>{{ formatDate(person.member_profile.ea_date) }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">FC Date</div>
                            <div>{{ formatDate(person.member_profile.fc_date) }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">MM Date</div>
                            <div>{{ formatDate(person.member_profile.mm_date) }}</div>
                        </div>
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">Demit Date</div>
                            <div>{{ formatDate(person.member_profile.demit_date) }}</div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-surface-600 dark:text-surface-300">
                        No member profile is attached to this person.
                    </div>
                </template>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <template #title>Relationships</template>
                <template #content>
                    <DataTable :value="person.relationships" data-key="id" responsive-layout="scroll">
                        <Column header="Type">
                            <template #body="{ data }">{{ data.label || '—' }}</template>
                        </Column>
                        <Column header="Person">
                            <template #body="{ data }">{{ data.person?.display_name || '—' }}</template>
                        </Column>
                        <Column header="Member #">
                            <template #body="{ data }">{{ data.person?.member_number || '—' }}</template>
                        </Column>
                        <Column header="Primary">
                            <template #body="{ data }">{{ data.is_primary ? 'Yes' : 'No' }}</template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #title>Related To This Person</template>
                <template #content>
                    <DataTable :value="person.inverse_relationships" data-key="id" responsive-layout="scroll">
                        <Column header="Type">
                            <template #body="{ data }">{{ data.label || '—' }}</template>
                        </Column>
                        <Column header="Person">
                            <template #body="{ data }">{{ data.person?.display_name || '—' }}</template>
                        </Column>
                        <Column header="Member #">
                            <template #body="{ data }">{{ data.person?.member_number || '—' }}</template>
                        </Column>
                        <Column header="Primary">
                            <template #body="{ data }">{{ data.is_primary ? 'Yes' : 'No' }}</template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>

        <Card>
            <template #title>Contact History</template>
            <template #content>
                <DataTable :value="person.contact_logs" data-key="id" responsive-layout="scroll">
                    <Column header="Contacted At">
                        <template #body="{ data }">{{ formatDateTime(data.contacted_at) }}</template>
                    </Column>
                    <Column header="Type">
                        <template #body="{ data }">{{ data.contact_type || '—' }}</template>
                    </Column>
                    <Column header="Created By">
                        <template #body="{ data }">{{ data.created_by || '—' }}</template>
                    </Column>
                    <Column header="Notes" style="min-width: 18rem">
                        <template #body="{ data }">{{ data.notes || '—' }}</template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </div>
</template>
