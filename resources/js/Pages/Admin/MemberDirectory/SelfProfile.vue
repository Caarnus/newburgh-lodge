<script>
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';

const props = defineProps({
    person: { type: Object, required: true },
    canManageRecords: { type: Boolean, default: false },
    memberStatusOptions: { type: Array, default: () => [] },
});

const page = usePage();

const backRoute = computed(() => (
    page.props?.can?.manage?.people?.directory
        ? 'manage.member-directory.index'
        : 'dashboard'
));

const form = useForm({
    first_name: props.person.first_name ?? '',
    middle_name: props.person.middle_name ?? '',
    last_name: props.person.last_name ?? '',
    suffix: props.person.suffix ?? '',
    preferred_name: props.person.preferred_name ?? '',
    display_name_override: props.person.display_name_override ?? '',
    email: props.person.email ?? '',
    phone: props.person.phone ?? '',
    address_line_1: props.person.address_line_1 ?? '',
    address_line_2: props.person.address_line_2 ?? '',
    city: props.person.city ?? '',
    state: props.person.state ?? '',
    postal_code: props.person.postal_code ?? '',
    birth_date: props.person.birth_date ?? '',
    notes: props.person.notes ?? '',
    is_deceased: Boolean(props.person.is_deceased),
    death_date: props.person.death_date ?? '',
    member_profile: {
        member_number: props.person.member_profile?.member_number ?? '',
        status: props.person.member_profile?.status ?? '',
        ea_date: props.person.member_profile?.ea_date ?? '',
        fc_date: props.person.member_profile?.fc_date ?? '',
        mm_date: props.person.member_profile?.mm_date ?? '',
        demit_date: props.person.member_profile?.demit_date ?? '',
        past_master: Boolean(props.person.member_profile?.past_master ?? false),
        can_auto_match_registration: Boolean(props.person.member_profile?.can_auto_match_registration ?? true),
        directory_visible: Boolean(props.person.member_profile?.directory_visible ?? true),
    },
});

const submit = () => {
    const payload = props.canManageRecords
        ? form.data()
        : {
            preferred_name: form.preferred_name,
            email: form.email,
            phone: form.phone,
            address_line_1: form.address_line_1,
            address_line_2: form.address_line_2,
            city: form.city,
            state: form.state,
            postal_code: form.postal_code,
        };

    form.transform(() => payload).patch(route('manage.member-directory.people.update', { person: props.person.id }), {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => form.transform((data) => data),
    });
};

const classificationLabels = computed(() => [
    props.person.classifications?.is_member ? 'Member' : null,
    props.person.classifications?.is_widow ? 'Widow/Widower' : null,
    props.person.classifications?.is_orphan ? 'Orphan' : null,
    props.person.classifications?.is_relative ? 'Relative' : null,
].filter(Boolean));

const formatDate = (value) => value ? new Date(`${value}T00:00:00`).toLocaleDateString() : '—';
</script>

<template>
    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm text-surface-500 dark:text-surface-400">
                    <Link :href="route(backRoute)" class="hover:underline">Back</Link>
                </div>
                <h1 class="mt-2 text-3xl font-semibold text-surface-900 dark:text-surface-0">My Member Profile</h1>
                <p class="mt-2 text-surface-600 dark:text-surface-300">
                    Review your directory profile and update allowed fields.
                </p>
            </div>
            <Button label="Save Profile" :loading="form.processing" @click="submit" />
        </div>

        <Card>
            <template #title>Profile Summary</template>
            <template #content>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-medium">{{ person.display_name }}</span>
                    <span
                        v-for="label in classificationLabels"
                        :key="label"
                        class="rounded-full bg-surface-100 px-2 py-1 text-xs text-surface-700 dark:bg-surface-800 dark:text-surface-100"
                    >
                        {{ label }}
                    </span>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Editable Contact Fields</template>
            <template #content>
                <div class="grid gap-4 md:grid-cols-2">
                    <div v-if="canManageRecords">
                        <label class="mb-2 block text-sm font-medium">First Name</label>
                        <InputText v-model="form.first_name" class="w-full" />
                        <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-500">{{ form.errors.first_name }}</p>
                    </div>

                    <div v-if="canManageRecords">
                        <label class="mb-2 block text-sm font-medium">Middle Name</label>
                        <InputText v-model="form.middle_name" class="w-full" />
                    </div>

                    <div v-if="canManageRecords">
                        <label class="mb-2 block text-sm font-medium">Last Name</label>
                        <InputText v-model="form.last_name" class="w-full" />
                        <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-500">{{ form.errors.last_name }}</p>
                    </div>

                    <div v-if="canManageRecords">
                        <label class="mb-2 block text-sm font-medium">Suffix</label>
                        <InputText v-model="form.suffix" class="w-full" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Preferred Name</label>
                        <InputText v-model="form.preferred_name" class="w-full" />
                        <p v-if="form.errors.preferred_name" class="mt-1 text-sm text-red-500">{{ form.errors.preferred_name }}</p>
                    </div>

                    <div v-if="canManageRecords">
                        <label class="mb-2 block text-sm font-medium">Display Name Override</label>
                        <InputText v-model="form.display_name_override" class="w-full" />
                        <p v-if="form.errors.display_name_override" class="mt-1 text-sm text-red-500">{{ form.errors.display_name_override }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Email</label>
                        <InputText v-model="form.email" type="email" class="w-full" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Phone</label>
                        <InputText v-model="form.phone" class="w-full" />
                        <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">{{ form.errors.phone }}</p>
                    </div>

                    <div v-if="canManageRecords">
                        <label class="mb-2 block text-sm font-medium">Birth Date</label>
                        <InputText v-model="form.birth_date" type="date" class="w-full" />
                        <p v-if="form.errors.birth_date" class="mt-1 text-sm text-red-500">{{ form.errors.birth_date }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium">Address Line 1</label>
                        <InputText v-model="form.address_line_1" class="w-full" />
                        <p v-if="form.errors.address_line_1" class="mt-1 text-sm text-red-500">{{ form.errors.address_line_1 }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium">Address Line 2</label>
                        <InputText v-model="form.address_line_2" class="w-full" />
                        <p v-if="form.errors.address_line_2" class="mt-1 text-sm text-red-500">{{ form.errors.address_line_2 }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">City</label>
                        <InputText v-model="form.city" class="w-full" />
                        <p v-if="form.errors.city" class="mt-1 text-sm text-red-500">{{ form.errors.city }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">State</label>
                        <InputText v-model="form.state" class="w-full" />
                        <p v-if="form.errors.state" class="mt-1 text-sm text-red-500">{{ form.errors.state }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Postal Code</label>
                        <InputText v-model="form.postal_code" class="w-full" />
                        <p v-if="form.errors.postal_code" class="mt-1 text-sm text-red-500">{{ form.errors.postal_code }}</p>
                    </div>

                    <div v-if="canManageRecords" class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium">Notes</label>
                        <Textarea v-model="form.notes" rows="4" class="w-full" />
                        <p v-if="form.errors.notes" class="mt-1 text-sm text-red-500">{{ form.errors.notes }}</p>
                    </div>

                    <div v-if="canManageRecords" class="md:col-span-2 flex flex-wrap items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <Checkbox v-model="form.is_deceased" binary />
                            <span>Marked deceased</span>
                        </label>
                        <div v-if="form.is_deceased" class="w-full md:w-64">
                            <label class="mb-2 block text-sm font-medium">Death Date</label>
                            <InputText v-model="form.death_date" type="date" class="w-full" />
                            <p v-if="form.errors.death_date" class="mt-1 text-sm text-red-500">{{ form.errors.death_date }}</p>
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>{{ canManageRecords ? 'Member Profile Fields' : 'Member Profile (Read-Only)' }}</template>
            <template #content>
                <div class="grid gap-4 md:grid-cols-2">
                    <template v-if="canManageRecords">
                        <div>
                            <label class="mb-2 block text-sm font-medium">Member Number</label>
                            <InputText v-model="form.member_profile.member_number" class="w-full" />
                            <p v-if="form.errors['member_profile.member_number']" class="mt-1 text-sm text-red-500">
                                {{ form.errors['member_profile.member_number'] }}
                            </p>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium">Status</label>
                            <Select
                                v-model="form.member_profile.status"
                                :options="memberStatusOptions"
                                option-label="label"
                                option-value="value"
                                class="w-full"
                                show-clear
                            />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">EA Date</div>
                            <div>{{ formatDate(person.member_profile?.ea_date) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">FC Date</div>
                            <div>{{ formatDate(person.member_profile?.fc_date) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">MM Date</div>
                            <div>{{ formatDate(person.member_profile?.mm_date) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">Demit Date</div>
                            <div>{{ formatDate(person.member_profile?.demit_date) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">Past Master</div>
                            <div>{{ person.member_profile?.past_master ? 'Yes' : 'No' }}</div>
                        </div>
                        <div class="md:col-span-2 flex flex-wrap items-center gap-6">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <Checkbox v-model="form.member_profile.can_auto_match_registration" binary />
                                <span>Auto-match registration by email</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <Checkbox v-model="form.member_profile.directory_visible" binary />
                                <span>Visible in directory</span>
                            </label>
                        </div>
                    </template>

                    <template v-else>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">Member Number</div>
                            <div>{{ person.member_profile?.member_number || '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">Status</div>
                            <div>{{ person.member_profile?.status || '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">EA Date</div>
                            <div>{{ formatDate(person.member_profile?.ea_date) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">FC Date</div>
                            <div>{{ formatDate(person.member_profile?.fc_date) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">MM Date</div>
                            <div>{{ formatDate(person.member_profile?.mm_date) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">Demit Date</div>
                            <div>{{ formatDate(person.member_profile?.demit_date) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">Past Master</div>
                            <div>{{ person.member_profile?.past_master ? 'Yes' : 'No' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-surface-700 dark:text-surface-200">Directory Visible</div>
                            <div>{{ person.member_profile?.directory_visible ? 'Yes' : 'No' }}</div>
                        </div>
                        <div class="md:col-span-2 text-sm text-surface-600 dark:text-surface-300">
                            Restricted fields like member status, death data, official notes, relationships, and care logs are managed by authorized staff.
                        </div>
                    </template>
                </div>
            </template>
        </Card>
    </div>
</template>
