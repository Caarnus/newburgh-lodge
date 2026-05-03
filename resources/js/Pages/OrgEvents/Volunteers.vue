<script setup>
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'
import Checkbox from 'primevue/checkbox'
import Divider from 'primevue/divider'
import Message from 'primevue/message'
import Select from 'primevue/select'

const props = defineProps({
    event: Object,
    sheet: Object,
    templates: Array,
    reminderPageUrl: String,
})

const flashSuccess = computed(() => usePage().props.flash?.success)

function toLocalDateTime(iso) {
    if (!iso) return ''
    const d = new Date(iso)
    const pad = (v) => String(v).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function roleFromSheet(role, roleIndex) {
    return {
        id: role.id ?? null,
        title: role.title ?? '',
        description: role.description ?? '',
        sort_order: Number(role.sort_order ?? roleIndex),
        slots: (role.slots ?? []).map((slot, slotIndex) => ({
            id: slot.id ?? null,
            starts_at: slot.starts_at ?? '07:00',
            ends_at: slot.ends_at ?? '08:00',
            needed_count: Number(slot.needed_count ?? 1),
            sort_order: Number(slot.sort_order ?? slotIndex),
        })),
    }
}

function blankRoles() {
    return [
        {
            id: null,
            title: '',
            description: '',
            sort_order: 0,
            slots: [{ id: null, starts_at: '07:00', ends_at: '08:00', needed_count: 1, sort_order: 0 }],
        },
    ]
}

const form = useForm({
    volunteer_signup_template_id: props.sheet?.volunteer_signup_template_id ?? null,
    is_enabled: props.sheet?.is_enabled ?? false,
    slug: props.sheet?.slug ?? '',
    title_override: props.sheet?.title_override ?? '',
    description: props.sheet?.description ?? '',
    opens_at: toLocalDateTime(props.sheet?.opens_at),
    closes_at: toLocalDateTime(props.sheet?.closes_at),
    remind_week_before: props.sheet?.remind_week_before ?? true,
    remind_day_before: props.sheet?.remind_day_before ?? true,
    roles: (props.sheet?.roles ?? []).length > 0
        ? props.sheet.roles.map((role, index) => roleFromSheet(role, index))
        : blankRoles(),
})

const applyTemplateForm = useForm({
    template_id: props.templates?.[0]?.id ?? null,
})

const saveTemplateForm = useForm({
    name: '',
    description: '',
    sort_order: 0,
    is_active: true,
})

function addRole() {
    form.roles.push({
        id: null,
        title: '',
        description: '',
        sort_order: form.roles.length,
        slots: [{ id: null, starts_at: '07:00', ends_at: '08:00', needed_count: 1, sort_order: 0 }],
    })
}

function removeRole(index) {
    form.roles.splice(index, 1)
}

function addSlot(role) {
    role.slots.push({
        id: null,
        starts_at: '07:00',
        ends_at: '08:00',
        needed_count: 1,
        sort_order: role.slots.length,
    })
}

function removeSlot(role, index) {
    role.slots.splice(index, 1)
}

function submit() {
    form.put(route('events.volunteers.upsert', props.event.id), {
        preserveScroll: true,
    })
}

function applyTemplate() {
    applyTemplateForm.post(route('events.volunteers.apply-template', props.event.id), {
        preserveScroll: true,
        preserveState: false,
    })
}

function saveAsTemplate() {
    saveTemplateForm.post(route('events.volunteers.save-template', props.event.id), {
        preserveScroll: true,
    })
}

function cancelAssignment(assignmentId) {
    if (!confirm('Cancel this volunteer assignment?')) return
    useForm({}).delete(route('events.volunteers.assignments.cancel', [props.event.id, assignmentId]), {
        preserveScroll: true,
    })
}

const hasSheet = computed(() => !!props.sheet)
</script>

<template>
    <AppLayout :title="`Volunteer Signup - ${event.title}`">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Volunteer Signup - {{ event.title }}
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('events.index')">
                        <Button icon="pi pi-arrow-left" label="Calendar" severity="secondary" text />
                    </Link>
                    <a :href="reminderPageUrl">
                        <Button icon="pi pi-bell" label="Reminder Page" severity="secondary" />
                    </a>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <Message v-if="flashSuccess" severity="success">{{ flashSuccess }}</Message>

            <Card v-if="sheet?.registrants?.length">
                <template #title>Current Volunteers</template>
                <template #content>
                    <div class="space-y-4">
                        <div
                            v-for="registrant in sheet.registrants"
                            :key="registrant.id"
                            class="border border-surface-200 rounded-md p-4"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold">{{ registrant.name || 'Volunteer' }}</span>
                                <a
                                    v-if="registrant.person_id"
                                    :href="route('manage.member-directory.people.show', registrant.person_id)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="underline"
                                >
                                    {{ registrant.email }}
                                </a>
                                <span v-else>{{ registrant.email }}</span>
                                <span v-if="registrant.person_display_name" class="text-xs text-gray-500">({{ registrant.person_display_name }})</span>
                            </div>

                            <div class="mt-3 space-y-2">
                                <div
                                    v-for="assignment in registrant.assignments"
                                    :key="assignment.id"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span>
                                        {{ assignment.role_title }}: {{ assignment.starts_at }} - {{ assignment.ends_at }}
                                    </span>
                                    <Button
                                        icon="pi pi-times"
                                        severity="danger"
                                        text
                                        size="small"
                                        @click="cancelAssignment(assignment.id)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Template Tools</template>
                <template #content>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <div class="font-medium">Apply template to this event</div>
                            <Select
                                v-model="applyTemplateForm.template_id"
                                :options="templates"
                                optionLabel="name"
                                optionValue="id"
                                class="w-full"
                            />
                            <Button
                                icon="pi pi-clone"
                                label="Apply Template"
                                :disabled="!applyTemplateForm.template_id"
                                :loading="applyTemplateForm.processing"
                                @click="applyTemplate"
                            />
                        </div>

                        <div class="space-y-3">
                            <div class="font-medium">Save current sheet as template</div>
                            <InputText v-model="saveTemplateForm.name" placeholder="Template name" class="w-full" />
                            <Textarea v-model="saveTemplateForm.description" rows="2" placeholder="Description" class="w-full" />
                            <div class="flex items-center gap-2">
                                <Checkbox v-model="saveTemplateForm.is_active" :binary="true" inputId="tpl_active_save" />
                                <label for="tpl_active_save">Active</label>
                            </div>
                            <Button
                                icon="pi pi-save"
                                label="Save as Template"
                                :loading="saveTemplateForm.processing"
                                @click="saveAsTemplate"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Volunteer Sheet Settings</template>
                <template #content>
                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Slug</label>
                                <InputText v-model="form.slug" class="w-full" />
                                <small v-if="form.errors.slug" class="p-error block mt-2">{{ form.errors.slug }}</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Title Override</label>
                                <InputText v-model="form.title_override" class="w-full" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Description</label>
                            <Textarea v-model="form.description" rows="3" class="w-full" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Opens At (optional)</label>
                                <input v-model="form.opens_at" type="datetime-local" class="p-inputtext p-component w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Closes At (optional)</label>
                                <input v-model="form.closes_at" type="datetime-local" class="p-inputtext p-component w-full" />
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-6">
                            <div class="flex items-center gap-3">
                                <ToggleSwitch v-model="form.is_enabled" inputId="vol_enabled" />
                                <label for="vol_enabled">Enable public volunteer signup</label>
                            </div>
                            <div class="flex items-center gap-3">
                                <Checkbox v-model="form.remind_week_before" :binary="true" inputId="remind_week" />
                                <label for="remind_week">Week-before reminder</label>
                            </div>
                            <div class="flex items-center gap-3">
                                <Checkbox v-model="form.remind_day_before" :binary="true" inputId="remind_day" />
                                <label for="remind_day">Day-before reminder</label>
                            </div>
                        </div>

                        <div v-if="hasSheet && sheet.public_url" class="text-sm">
                            Public signup link:
                            <a :href="sheet.public_url" target="_blank" class="underline">{{ sheet.public_url }}</a>
                        </div>

                        <Divider />

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold">Roles & Time Slots</h3>
                                <Button icon="pi pi-plus" label="Add Role" severity="secondary" @click="addRole" />
                            </div>

                            <div
                                v-for="(role, roleIndex) in form.roles"
                                :key="role.id ?? `role-${roleIndex}`"
                                class="border border-surface-200 rounded-md p-4 space-y-3"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <InputText v-model="role.title" placeholder="Role title" />
                                    <InputNumber v-model="role.sort_order" :min="0" placeholder="Sort order" />
                                    <Button icon="pi pi-trash" label="Remove Role" severity="danger" text @click="removeRole(roleIndex)" />
                                </div>

                                <Textarea v-model="role.description" rows="2" placeholder="Optional role description" class="w-full" />

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium">Time Slots</h4>
                                        <Button size="small" icon="pi pi-plus" label="Add Slot" severity="secondary" @click="addSlot(role)" />
                                    </div>

                                    <div
                                        v-for="(slot, slotIndex) in role.slots"
                                        :key="slot.id ?? `slot-${slotIndex}`"
                                        class="grid grid-cols-1 sm:grid-cols-5 gap-2 items-center"
                                    >
                                        <div>
                                            <label class="text-xs">Start</label>
                                            <input v-model="slot.starts_at" type="time" class="p-inputtext p-component w-full" />
                                        </div>
                                        <div>
                                            <label class="text-xs">End</label>
                                            <input v-model="slot.ends_at" type="time" class="p-inputtext p-component w-full" />
                                        </div>
                                        <div>
                                            <label class="text-xs">Needed</label>
                                            <InputNumber v-model="slot.needed_count" :min="1" :max="500" />
                                        </div>
                                        <div>
                                            <label class="text-xs">Sort</label>
                                            <InputNumber v-model="slot.sort_order" :min="0" />
                                        </div>
                                        <div class="pt-4 sm:pt-0">
                                            <Button icon="pi pi-trash" severity="danger" text @click="removeSlot(role, slotIndex)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.errors.roles" class="p-error text-sm">{{ form.errors.roles }}</div>

                        <div class="flex justify-end">
                            <Button type="submit" icon="pi pi-save" label="Save Volunteer Sheet" :loading="form.processing" />
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
