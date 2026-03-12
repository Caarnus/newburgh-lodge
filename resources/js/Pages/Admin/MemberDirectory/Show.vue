<script>
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
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
import QuickContactLogModal from '@/Components/MemberDirectory/QuickContactLogModal.vue';

const props = defineProps({
    person: { type: Object, required: true },
    memberStatusOptions: { type: Array, default: () => [] },
    relationshipTypeOptions: { type: Array, default: () => [] },
    fromSection: { type: String, default: null },
});

const page = usePage();
const backRoute = computed(() => ({
    all: 'manage.member-directory.all.index',
    members: 'manage.member-directory.members.index',
    widows: 'manage.member-directory.widows.index',
    orphans: 'manage.member-directory.orphans.index',
    relatives: 'manage.member-directory.relatives.index',
    others: 'manage.member-directory.others.index',
}[props.fromSection] ?? 'manage.member-directory.index'));

const backLabel = computed(() => ({
    all: 'Back to All People',
    members: 'Back to Members',
    widows: 'Back to Widows',
    orphans: 'Back to Orphans',
    relatives: 'Back to Relatives',
    others: 'Back to Others',
}[props.fromSection] ?? 'Back to Directory'));

const classifications = computed(() => [
    props.person.classifications?.is_member ? 'Member' : null,
    props.person.classifications?.is_widow ? 'Widow/Widower' : null,
    props.person.classifications?.is_orphan ? 'Orphan' : null,
    props.person.classifications?.is_relative ? 'Relative' : null,
].filter(Boolean));

const canManageRecords = computed(() => Boolean(page.props?.can?.manage?.people?.updateRecords));
const canManageRelationships = canManageRecords;
const canLogContacts = computed(() => Boolean(page.props?.can?.manage?.people?.logContacts));
const canEditContacts = computed(() => Boolean(page.props?.can?.manage?.people?.editContacts));
const formatDate = (value) => value ? new Date(`${value}T00:00:00`).toLocaleDateString() : '—';
const parseDateTime = (value) => {
    if (!value) {
        return null;
    }

    const normalized = String(value).includes('T')
        ? String(value)
        : String(value).replace(' ', 'T');
    const parsed = new Date(normalized);

    return Number.isNaN(parsed.getTime()) ? null : parsed;
};
const formatDateTime = (value) => {
    const parsed = parseDateTime(value);
    return parsed ? parsed.toLocaleString() : '—';
};
const toDateTimeLocal = (value) => {
    const parsed = parseDateTime(value);
    if (!parsed) {
        return '';
    }

    const year = parsed.getFullYear();
    const month = String(parsed.getMonth() + 1).padStart(2, '0');
    const day = String(parsed.getDate()).padStart(2, '0');
    const hours = String(parsed.getHours()).padStart(2, '0');
    const minutes = String(parsed.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const addRelationshipDialogVisible = ref(false);
const editRelationshipDialogVisible = ref(false);
const editingRelationship = ref(null);
const editRecordDialogVisible = ref(false);
const quickLogVisible = ref(false);
const editContactDialogVisible = ref(false);
const editingContactLog = ref(null);

const contactTypeOptions = [
    { label: 'Call', value: 'call' },
    { label: 'Text', value: 'text' },
    { label: 'Email', value: 'email' },
    { label: 'Visit', value: 'visit' },
    { label: 'Other', value: 'other' },
];

const relatedSearch = ref('');
const searchingRelated = ref(false);
const relatedOptions = ref([]);
const relatedPersonModeOptions = [
    { label: 'Use Existing Person', value: 'existing' },
    { label: 'Create New Person', value: 'new' },
];

const createRelationshipForm = useForm({
    related_person_mode: 'existing',
    related_person_id: null,
    relationship_type: null,
    inverse_relationship_type: null,
    is_primary: false,
    notes: '',
    new_person_first_name: '',
    new_person_middle_name: '',
    new_person_last_name: '',
    new_person_suffix: '',
    new_person_preferred_name: '',
    new_person_email: '',
    new_person_phone: '',
    new_person_notes: '',
    new_person_is_deceased: false,
    new_person_death_date: '',
    from: props.fromSection,
});

const editRelationshipForm = useForm({
    relationship_type: null,
    inverse_relationship_type: null,
    is_primary: false,
    notes: '',
    from: props.fromSection,
});

const editRecordForm = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',
    preferred_name: '',
    email: '',
    phone: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    state: '',
    postal_code: '',
    birth_date: '',
    notes: '',
    is_deceased: false,
    death_date: '',
    member_profile: {
        member_number: '',
        status: '',
        ea_date: '',
        fc_date: '',
        mm_date: '',
        demit_date: '',
        can_auto_match_registration: true,
        directory_visible: true,
    },
});

const editContactForm = useForm({
    contacted_at: '',
    contact_type: null,
    notes: '',
    from: props.fromSection,
});

const populateEditRecordForm = () => {
    editRecordForm.first_name = props.person.first_name || '';
    editRecordForm.middle_name = props.person.middle_name || '';
    editRecordForm.last_name = props.person.last_name || '';
    editRecordForm.suffix = props.person.suffix || '';
    editRecordForm.preferred_name = props.person.preferred_name || '';
    editRecordForm.email = props.person.email || '';
    editRecordForm.phone = props.person.phone || '';
    editRecordForm.address_line_1 = props.person.address_line_1 || '';
    editRecordForm.address_line_2 = props.person.address_line_2 || '';
    editRecordForm.city = props.person.city || '';
    editRecordForm.state = props.person.state || '';
    editRecordForm.postal_code = props.person.postal_code || '';
    editRecordForm.birth_date = props.person.birth_date || '';
    editRecordForm.notes = props.person.notes || '';
    editRecordForm.is_deceased = Boolean(props.person.is_deceased);
    editRecordForm.death_date = props.person.death_date || '';

    editRecordForm.member_profile = {
        member_number: props.person.member_profile?.member_number || '',
        status: props.person.member_profile?.status || '',
        ea_date: props.person.member_profile?.ea_date || '',
        fc_date: props.person.member_profile?.fc_date || '',
        mm_date: props.person.member_profile?.mm_date || '',
        demit_date: props.person.member_profile?.demit_date || '',
        can_auto_match_registration: Boolean(props.person.member_profile?.can_auto_match_registration ?? true),
        directory_visible: Boolean(props.person.member_profile?.directory_visible ?? true),
    };
};

const resetCreateRelationshipForm = () => {
    createRelationshipForm.reset();
    createRelationshipForm.clearErrors();
    createRelationshipForm.related_person_mode = 'existing';
    createRelationshipForm.from = props.fromSection;
    relatedSearch.value = '';
    relatedOptions.value = [];
};

const openAddRelationshipDialog = () => {
    resetCreateRelationshipForm();
    addRelationshipDialogVisible.value = true;
};

const closeAddRelationshipDialog = () => {
    addRelationshipDialogVisible.value = false;
    resetCreateRelationshipForm();
};

const onRelatedModeChange = (mode) => {
    createRelationshipForm.related_person_mode = mode;
    createRelationshipForm.clearErrors();

    if (mode === 'new') {
        createRelationshipForm.related_person_id = null;
    } else {
        createRelationshipForm.new_person_first_name = '';
        createRelationshipForm.new_person_middle_name = '';
        createRelationshipForm.new_person_last_name = '';
        createRelationshipForm.new_person_suffix = '';
        createRelationshipForm.new_person_preferred_name = '';
        createRelationshipForm.new_person_email = '';
        createRelationshipForm.new_person_phone = '';
        createRelationshipForm.new_person_notes = '';
        createRelationshipForm.new_person_is_deceased = false;
        createRelationshipForm.new_person_death_date = '';
    }
};

const searchRelatedPeople = async () => {
    const query = relatedSearch.value.trim();
    if (query.length < 2) {
        relatedOptions.value = [];
        return;
    }

    searchingRelated.value = true;
    try {
        const response = await window.axios.get(route('manage.member-directory.people.search-for-user-link'), {
            params: { q: query },
        });

        relatedOptions.value = (response.data || [])
            .filter((relatedPerson) => relatedPerson.id !== props.person.id)
            .map((relatedPerson) => ({
                id: relatedPerson.id,
                label: relatedPerson.member_profile?.member_number
                    ? `${relatedPerson.display_name} (#${relatedPerson.member_profile.member_number})`
                    : relatedPerson.display_name,
                subtitle: relatedPerson.email || relatedPerson.phone || 'No email/phone',
            }));
    } finally {
        searchingRelated.value = false;
    }
};

const submitCreateRelationship = () => {
    createRelationshipForm.from = props.fromSection;
    createRelationshipForm.post(
        route('manage.member-directory.people.relationships.store', { person: props.person.id }),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeAddRelationshipDialog(),
        },
    );
};

const openEditRelationshipDialog = (relationship) => {
    editingRelationship.value = relationship;
    editRelationshipForm.reset();
    editRelationshipForm.clearErrors();
    editRelationshipForm.relationship_type = relationship.type;
    editRelationshipForm.inverse_relationship_type = relationship.inverse_type;
    editRelationshipForm.is_primary = Boolean(relationship.is_primary);
    editRelationshipForm.notes = relationship.notes || '';
    editRelationshipForm.from = props.fromSection;
    editRelationshipDialogVisible.value = true;
};

const closeEditRelationshipDialog = () => {
    editRelationshipDialogVisible.value = false;
    editingRelationship.value = null;
    editRelationshipForm.reset();
    editRelationshipForm.clearErrors();
    editRelationshipForm.from = props.fromSection;
};

const submitEditRelationship = () => {
    if (!editingRelationship.value) {
        return;
    }

    editRelationshipForm.from = props.fromSection;
    editRelationshipForm.put(
        route('manage.member-directory.people.relationships.update', {
            person: props.person.id,
            relationship: editingRelationship.value.id,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeEditRelationshipDialog(),
        },
    );
};

const deleteRelationship = (relationship) => {
    if (!window.confirm('Remove this relationship?')) {
        return;
    }

    router.delete(
        route('manage.member-directory.people.relationships.destroy', {
            person: props.person.id,
            relationship: relationship.id,
        }),
        {
            data: { from: props.fromSection },
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const openEditRecordDialog = () => {
    populateEditRecordForm();
    editRecordForm.clearErrors();
    editRecordDialogVisible.value = true;
};

const closeEditRecordDialog = () => {
    editRecordDialogVisible.value = false;
    editRecordForm.clearErrors();
};

const submitEditRecord = () => {
    editRecordForm.patch(route('manage.member-directory.people.update', { person: props.person.id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => closeEditRecordDialog(),
    });
};

const openQuickLogDialog = () => {
    quickLogVisible.value = true;
};

const onQuickLogVisibleChange = (value) => {
    quickLogVisible.value = value;
};

const onQuickLogSaved = () => {
    router.reload({
        only: ['person'],
        preserveScroll: true,
        preserveState: true,
    });
};

const openEditContactDialog = (contactLog) => {
    editingContactLog.value = contactLog;
    editContactForm.reset();
    editContactForm.clearErrors();
    editContactForm.contacted_at = toDateTimeLocal(contactLog.contacted_at);
    editContactForm.contact_type = contactLog.contact_type || null;
    editContactForm.notes = contactLog.notes || '';
    editContactForm.from = props.fromSection;
    editContactDialogVisible.value = true;
};

const closeEditContactDialog = () => {
    editContactDialogVisible.value = false;
    editingContactLog.value = null;
    editContactForm.reset();
    editContactForm.clearErrors();
    editContactForm.from = props.fromSection;
};

const submitEditContact = () => {
    if (!editingContactLog.value) {
        return;
    }

    editContactForm.from = props.fromSection;
    editContactForm.put(
        route('manage.member-directory.people.contact-logs.update', {
            person: props.person.id,
            contactLog: editingContactLog.value.id,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeEditContactDialog(),
        },
    );
};
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
            <Button
                v-if="canManageRelationships"
                label="Add Relationship"
                @click="openAddRelationshipDialog"
            />
            <Button
                v-if="canManageRecords"
                label="Edit Record"
                severity="secondary"
                outlined
                @click="openEditRecordDialog"
            />
            <Button
                v-if="canLogContacts"
                label="Quick Log Contact"
                severity="secondary"
                outlined
                @click="openQuickLogDialog"
            />
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
                        <div>
                            <div class="font-medium text-surface-700 dark:text-surface-200">Last Contact</div>
                            <div>{{ formatDateTime(person.last_contact_at) }}</div>
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
                        <Column header="Inverse Type">
                            <template #body="{ data }">{{ data.inverse_label || '—' }}</template>
                        </Column>
                        <Column header="Primary">
                            <template #body="{ data }">{{ data.is_primary ? 'Yes' : 'No' }}</template>
                        </Column>
                        <Column header="Notes" style="min-width: 16rem">
                            <template #body="{ data }">{{ data.notes || '—' }}</template>
                        </Column>
                        <Column v-if="canManageRelationships" header="Actions" style="min-width: 11rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <Button
                                        text
                                        size="small"
                                        label="Edit"
                                        @click="openEditRelationshipDialog(data)"
                                    />
                                    <Button
                                        text
                                        severity="danger"
                                        size="small"
                                        label="Remove"
                                        @click="deleteRelationship(data)"
                                    />
                                </div>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="py-2 text-sm text-surface-500">No relationships recorded.</div>
                        </template>
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
                        <Column header="Notes" style="min-width: 16rem">
                            <template #body="{ data }">{{ data.notes || '—' }}</template>
                        </Column>
                        <template #empty>
                            <div class="py-2 text-sm text-surface-500">No incoming relationships recorded.</div>
                        </template>
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
                    <Column v-if="canEditContacts" header="Actions" style="min-width: 9rem">
                        <template #body="{ data }">
                            <Button
                                text
                                size="small"
                                label="Edit"
                                @click="openEditContactDialog(data)"
                            />
                        </template>
                    </Column>
                    <template #empty>
                        <div class="py-2 text-sm text-surface-500">No contact history yet.</div>
                    </template>
                </DataTable>
            </template>
        </Card>

        <Dialog
            v-model:visible="editRecordDialogVisible"
            modal
            header="Edit Person Record"
            class="w-full sm:w-[52rem]"
            @hide="closeEditRecordDialog"
        >
            <div class="space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium">First Name</label>
                        <InputText v-model="editRecordForm.first_name" class="w-full" />
                        <p v-if="editRecordForm.errors.first_name" class="mt-1 text-sm text-red-500">{{ editRecordForm.errors.first_name }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Middle Name</label>
                        <InputText v-model="editRecordForm.middle_name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Last Name</label>
                        <InputText v-model="editRecordForm.last_name" class="w-full" />
                        <p v-if="editRecordForm.errors.last_name" class="mt-1 text-sm text-red-500">{{ editRecordForm.errors.last_name }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Preferred Name</label>
                        <InputText v-model="editRecordForm.preferred_name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Suffix</label>
                        <InputText v-model="editRecordForm.suffix" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Birth Date</label>
                        <InputText v-model="editRecordForm.birth_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Email</label>
                        <InputText v-model="editRecordForm.email" type="email" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Phone</label>
                        <InputText v-model="editRecordForm.phone" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Address Line 1</label>
                        <InputText v-model="editRecordForm.address_line_1" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Address Line 2</label>
                        <InputText v-model="editRecordForm.address_line_2" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">City</label>
                        <InputText v-model="editRecordForm.city" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">State</label>
                        <InputText v-model="editRecordForm.state" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Postal Code</label>
                        <InputText v-model="editRecordForm.postal_code" class="w-full" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-2 block text-sm font-medium">Notes</label>
                        <Textarea v-model="editRecordForm.notes" rows="3" class="w-full" />
                    </div>
                    <div class="md:col-span-3 flex flex-wrap items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <Checkbox v-model="editRecordForm.is_deceased" binary />
                            <span>Marked deceased</span>
                        </label>
                        <div v-if="editRecordForm.is_deceased" class="w-full md:w-64">
                            <label class="mb-2 block text-sm font-medium">Death Date</label>
                            <InputText v-model="editRecordForm.death_date" type="date" class="w-full" />
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 border-t border-surface-200 pt-4 dark:border-surface-700 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Member Number</label>
                        <InputText v-model="editRecordForm.member_profile.member_number" class="w-full" />
                        <p v-if="editRecordForm.errors['member_profile.member_number']" class="mt-1 text-sm text-red-500">
                            {{ editRecordForm.errors['member_profile.member_number'] }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Status</label>
                        <Select
                            v-model="editRecordForm.member_profile.status"
                            :options="memberStatusOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                            show-clear
                        />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">EA Date</label>
                        <InputText v-model="editRecordForm.member_profile.ea_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">FC Date</label>
                        <InputText v-model="editRecordForm.member_profile.fc_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">MM Date</label>
                        <InputText v-model="editRecordForm.member_profile.mm_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Demit Date</label>
                        <InputText v-model="editRecordForm.member_profile.demit_date" type="date" class="w-full" />
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <Checkbox v-model="editRecordForm.member_profile.can_auto_match_registration" binary />
                            <span>Auto-match registration by email</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <Checkbox v-model="editRecordForm.member_profile.directory_visible" binary />
                            <span>Visible in directory</span>
                        </label>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    text
                    @click="closeEditRecordDialog"
                />
                <Button
                    label="Save Record"
                    :loading="editRecordForm.processing"
                    @click="submitEditRecord"
                />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="addRelationshipDialogVisible"
            modal
            header="Add Relationship"
            class="w-full sm:w-[48rem]"
            @hide="closeAddRelationshipDialog"
        >
            <div class="space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-medium">Related Person Source</label>
                    <Select
                        v-model="createRelationshipForm.related_person_mode"
                        :options="relatedPersonModeOptions"
                        option-label="label"
                        option-value="value"
                        class="w-full md:w-80"
                        @update:modelValue="onRelatedModeChange"
                    />
                    <p v-if="createRelationshipForm.errors.related_person_mode" class="mt-1 text-sm text-red-500">
                        {{ createRelationshipForm.errors.related_person_mode }}
                    </p>
                </div>

                <div v-if="createRelationshipForm.related_person_mode === 'existing'" class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Find Existing Person</label>
                        <div class="flex gap-2">
                            <InputText
                                v-model="relatedSearch"
                                class="w-full"
                                placeholder="Name, email, phone, or member #"
                                @keyup.enter="searchRelatedPeople"
                            />
                            <Button
                                label="Search"
                                outlined
                                :loading="searchingRelated"
                                @click="searchRelatedPeople"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Related Person</label>
                        <Select
                            v-model="createRelationshipForm.related_person_id"
                            :options="relatedOptions"
                            option-label="label"
                            option-value="id"
                            class="w-full"
                            show-clear
                            placeholder="Select person"
                        >
                            <template #option="{ option }">
                                <div>
                                    <div>{{ option.label }}</div>
                                    <div class="text-xs text-surface-500">{{ option.subtitle }}</div>
                                </div>
                            </template>
                        </Select>
                        <p v-if="createRelationshipForm.errors.related_person_id" class="mt-1 text-sm text-red-500">
                            {{ createRelationshipForm.errors.related_person_id }}
                        </p>
                    </div>
                </div>

                <div v-else class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium">First Name</label>
                        <InputText v-model="createRelationshipForm.new_person_first_name" class="w-full" />
                        <p v-if="createRelationshipForm.errors.new_person_first_name" class="mt-1 text-sm text-red-500">
                            {{ createRelationshipForm.errors.new_person_first_name }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Last Name</label>
                        <InputText v-model="createRelationshipForm.new_person_last_name" class="w-full" />
                        <p v-if="createRelationshipForm.errors.new_person_last_name" class="mt-1 text-sm text-red-500">
                            {{ createRelationshipForm.errors.new_person_last_name }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Preferred Name</label>
                        <InputText v-model="createRelationshipForm.new_person_preferred_name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Middle Name</label>
                        <InputText v-model="createRelationshipForm.new_person_middle_name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Suffix</label>
                        <InputText v-model="createRelationshipForm.new_person_suffix" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Phone</label>
                        <InputText v-model="createRelationshipForm.new_person_phone" class="w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium">Email</label>
                        <InputText v-model="createRelationshipForm.new_person_email" type="email" class="w-full" />
                        <p v-if="createRelationshipForm.errors.new_person_email" class="mt-1 text-sm text-red-500">
                            {{ createRelationshipForm.errors.new_person_email }}
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium">Notes</label>
                        <Textarea v-model="createRelationshipForm.new_person_notes" rows="3" class="w-full" />
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <Checkbox v-model="createRelationshipForm.new_person_is_deceased" binary />
                            <span>Marked deceased</span>
                        </label>
                        <div v-if="createRelationshipForm.new_person_is_deceased" class="w-full md:w-64">
                            <label class="mb-2 block text-sm font-medium">Death Date</label>
                            <InputText v-model="createRelationshipForm.new_person_death_date" type="date" class="w-full" />
                            <p v-if="createRelationshipForm.errors.new_person_death_date" class="mt-1 text-sm text-red-500">
                                {{ createRelationshipForm.errors.new_person_death_date }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 border-t border-surface-200 pt-4 dark:border-surface-700 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Relationship Type</label>
                        <Select
                            v-model="createRelationshipForm.relationship_type"
                            :options="relationshipTypeOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                            show-clear
                        />
                        <p v-if="createRelationshipForm.errors.relationship_type" class="mt-1 text-sm text-red-500">
                            {{ createRelationshipForm.errors.relationship_type }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Inverse Relationship Type (Optional)</label>
                        <Select
                            v-model="createRelationshipForm.inverse_relationship_type"
                            :options="relationshipTypeOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                            show-clear
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium">Notes</label>
                        <Textarea v-model="createRelationshipForm.notes" rows="3" class="w-full" />
                        <p v-if="createRelationshipForm.errors.notes" class="mt-1 text-sm text-red-500">
                            {{ createRelationshipForm.errors.notes }}
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <Checkbox v-model="createRelationshipForm.is_primary" binary />
                            <span>Primary relationship</span>
                        </label>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    text
                    @click="closeAddRelationshipDialog"
                />
                <Button
                    label="Save Relationship"
                    :loading="createRelationshipForm.processing"
                    @click="submitCreateRelationship"
                />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="editRelationshipDialogVisible"
            modal
            header="Edit Relationship"
            class="w-full sm:w-[36rem]"
            @hide="closeEditRelationshipDialog"
        >
            <div class="space-y-4">
                <div class="rounded-lg border border-surface-200 bg-surface-50 p-3 text-sm dark:border-surface-700 dark:bg-surface-800">
                    <div class="font-medium text-surface-700 dark:text-surface-100">Related Person</div>
                    <div class="mt-1">{{ editingRelationship?.person?.display_name || '—' }}</div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">Relationship Type</label>
                    <Select
                        v-model="editRelationshipForm.relationship_type"
                        :options="relationshipTypeOptions"
                        option-label="label"
                        option-value="value"
                        class="w-full"
                        show-clear
                    />
                    <p v-if="editRelationshipForm.errors.relationship_type" class="mt-1 text-sm text-red-500">
                        {{ editRelationshipForm.errors.relationship_type }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">Inverse Relationship Type (Optional)</label>
                    <Select
                        v-model="editRelationshipForm.inverse_relationship_type"
                        :options="relationshipTypeOptions"
                        option-label="label"
                        option-value="value"
                        class="w-full"
                        show-clear
                    />
                    <p v-if="editRelationshipForm.errors.inverse_relationship_type" class="mt-1 text-sm text-red-500">
                        {{ editRelationshipForm.errors.inverse_relationship_type }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">Notes</label>
                    <Textarea v-model="editRelationshipForm.notes" rows="3" class="w-full" />
                    <p v-if="editRelationshipForm.errors.notes" class="mt-1 text-sm text-red-500">
                        {{ editRelationshipForm.errors.notes }}
                    </p>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <Checkbox v-model="editRelationshipForm.is_primary" binary />
                        <span>Primary relationship</span>
                    </label>
                </div>
            </div>
            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    text
                    @click="closeEditRelationshipDialog"
                />
                <Button
                    label="Update Relationship"
                    :loading="editRelationshipForm.processing"
                    @click="submitEditRelationship"
                />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="editContactDialogVisible"
            modal
            header="Edit Contact Log"
            class="w-full sm:w-[34rem]"
            @hide="closeEditContactDialog"
        >
            <div class="space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-medium">Contacted At</label>
                    <InputText v-model="editContactForm.contacted_at" type="datetime-local" class="w-full" />
                    <p v-if="editContactForm.errors.contacted_at" class="mt-1 text-sm text-red-500">
                        {{ editContactForm.errors.contacted_at }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">Contact Type</label>
                    <Select
                        v-model="editContactForm.contact_type"
                        :options="contactTypeOptions"
                        option-label="label"
                        option-value="value"
                        class="w-full"
                        show-clear
                    />
                    <p v-if="editContactForm.errors.contact_type" class="mt-1 text-sm text-red-500">
                        {{ editContactForm.errors.contact_type }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">Notes</label>
                    <Textarea v-model="editContactForm.notes" rows="4" class="w-full" />
                    <p v-if="editContactForm.errors.notes" class="mt-1 text-sm text-red-500">
                        {{ editContactForm.errors.notes }}
                    </p>
                </div>
            </div>
            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    text
                    @click="closeEditContactDialog"
                />
                <Button
                    label="Update Log"
                    :loading="editContactForm.processing"
                    @click="submitEditContact"
                />
            </template>
        </Dialog>

        <QuickContactLogModal
            :visible="quickLogVisible"
            :person="person"
            :from-section="fromSection"
            @update:visible="onQuickLogVisibleChange"
            @saved="onQuickLogSaved"
        />
    </div>
</template>
