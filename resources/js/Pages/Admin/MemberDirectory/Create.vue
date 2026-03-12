<script>
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';

const props = defineProps({
    memberStatusOptions: { type: Array, default: () => [] },
    relationshipTypeOptions: { type: Array, default: () => [] },
});

const form = useForm({
    record_type: 'member',

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

    member_number: '',
    member_status: null,
    ea_date: '',
    fc_date: '',
    mm_date: '',
    demit_date: '',
    can_auto_match_registration: true,
    directory_visible: true,

    related_person_id: null,
    relationship_type: null,
    inverse_relationship_type: null,
    relationship_is_primary: false,
    relationship_notes: '',
});

const relatedSearch = ref('');
const searchingRelated = ref(false);
const relatedOptions = ref([]);

const showMemberFields = computed(() => form.record_type === 'member');
const showRelativeFields = computed(() => form.record_type === 'relative');

const recordTypeOptions = [
    { label: 'Member', value: 'member' },
    { label: 'Relative', value: 'relative' },
    { label: 'General Person', value: 'person' },
];

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

        relatedOptions.value = (response.data || []).map((person) => ({
            id: person.id,
            label: person.member_profile?.member_number
                ? `${person.display_name} (#${person.member_profile.member_number})`
                : person.display_name,
            subtitle: person.email || person.phone || 'No email/phone',
        }));
    } finally {
        searchingRelated.value = false;
    }
};

const submit = () => {
    form.post(route('manage.member-directory.people.store'));
};
</script>

<template>
    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm text-surface-500 dark:text-surface-400">
                    <Link :href="route('manage.member-directory.members.index')" class="hover:underline">Back to Directory</Link>
                </div>
                <h1 class="mt-2 text-3xl font-semibold text-surface-900 dark:text-surface-0">Create Person</h1>
                <p class="mt-2 text-surface-600 dark:text-surface-300">
                    Add a new member, relative, or general person record.
                </p>
            </div>
            <Button label="Save Person" :loading="form.processing" @click="submit" />
        </div>

        <Card>
            <template #title>Record Type</template>
            <template #content>
                <Select
                    v-model="form.record_type"
                    :options="recordTypeOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full md:w-80"
                />
                <p v-if="form.errors.record_type" class="mt-1 text-sm text-red-500">{{ form.errors.record_type }}</p>
            </template>
        </Card>

        <Card>
            <template #title>Person Details</template>
            <template #content>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium">First Name</label>
                        <InputText v-model="form.first_name" class="w-full" />
                        <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-500">{{ form.errors.first_name }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Middle Name</label>
                        <InputText v-model="form.middle_name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Last Name</label>
                        <InputText v-model="form.last_name" class="w-full" />
                        <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-500">{{ form.errors.last_name }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Preferred Name</label>
                        <InputText v-model="form.preferred_name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Suffix</label>
                        <InputText v-model="form.suffix" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Birth Date</label>
                        <InputText v-model="form.birth_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Email</label>
                        <InputText v-model="form.email" type="email" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Phone</label>
                        <InputText v-model="form.phone" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Address Line 1</label>
                        <InputText v-model="form.address_line_1" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Address Line 2</label>
                        <InputText v-model="form.address_line_2" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">City</label>
                        <InputText v-model="form.city" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">State</label>
                        <InputText v-model="form.state" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Postal Code</label>
                        <InputText v-model="form.postal_code" class="w-full" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-2 block text-sm font-medium">Notes</label>
                        <Textarea v-model="form.notes" rows="4" class="w-full" />
                    </div>
                    <div class="md:col-span-3 flex flex-wrap items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <Checkbox v-model="form.is_deceased" binary />
                            <span>Marked deceased</span>
                        </label>
                        <div v-if="form.is_deceased" class="w-full md:w-64">
                            <label class="mb-2 block text-sm font-medium">Death Date</label>
                            <InputText v-model="form.death_date" type="date" class="w-full" />
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <Card v-if="showMemberFields">
            <template #title>Member Profile</template>
            <template #content>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Member Number</label>
                        <InputText v-model="form.member_number" class="w-full" />
                        <p v-if="form.errors.member_number" class="mt-1 text-sm text-red-500">{{ form.errors.member_number }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Status</label>
                        <Select
                            v-model="form.member_status"
                            :options="memberStatusOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                            show-clear
                        />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">EA Date</label>
                        <InputText v-model="form.ea_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">FC Date</label>
                        <InputText v-model="form.fc_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">MM Date</label>
                        <InputText v-model="form.mm_date" type="date" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Demit Date</label>
                        <InputText v-model="form.demit_date" type="date" class="w-full" />
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <Checkbox v-model="form.can_auto_match_registration" binary />
                            <span>Auto-match registration by email</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <Checkbox v-model="form.directory_visible" binary />
                            <span>Visible in directory</span>
                        </label>
                    </div>
                </div>
            </template>
        </Card>

        <Card v-if="showRelativeFields">
            <template #title>Relationship</template>
            <template #content>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Find Related Person</label>
                        <div class="flex gap-2">
                            <InputText
                                v-model="relatedSearch"
                                class="w-full"
                                placeholder="Name, email, phone, or member #"
                                @keyup.enter="searchRelatedPeople"
                            />
                            <Button
                                label="Search"
                                :loading="searchingRelated"
                                outlined
                                @click="searchRelatedPeople"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Related Person</label>
                        <Select
                            v-model="form.related_person_id"
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
                        <p v-if="form.errors.related_person_id" class="mt-1 text-sm text-red-500">{{ form.errors.related_person_id }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">New Person's Relationship to Related Person</label>
                        <Select
                            v-model="form.relationship_type"
                            :options="relationshipTypeOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                            show-clear
                        />
                        <p v-if="form.errors.relationship_type" class="mt-1 text-sm text-red-500">{{ form.errors.relationship_type }}</p>
                        <p class="mt-1 text-xs text-surface-500">
                            Example: if the new person is the member's child, choose Child.
                        </p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Related Person's Relationship to New Person (Optional)</label>
                        <Select
                            v-model="form.inverse_relationship_type"
                            :options="relationshipTypeOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                            show-clear
                        />
                        <p class="mt-1 text-xs text-surface-500">
                            In the same example above, choose Parent here.
                        </p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Relationship Notes</label>
                        <Textarea v-model="form.relationship_notes" rows="3" class="w-full" />
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <Checkbox v-model="form.relationship_is_primary" binary />
                            <span>Primary relationship</span>
                        </label>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
