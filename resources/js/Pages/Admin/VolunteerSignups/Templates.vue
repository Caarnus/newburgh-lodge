<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'
import Divider from 'primevue/divider'
import Message from 'primevue/message'

const props = defineProps({
    templates: Array,
})

const flashSuccess = computed(() => usePage().props.flash?.success)
const selectedTemplateId = ref(props.templates?.[0]?.id ?? null)

const form = useForm({
    name: '',
    description: '',
    sort_order: 0,
    is_active: true,
    roles: [],
})

function newTemplate() {
    selectedTemplateId.value = null
    form.reset()
    form.name = ''
    form.description = ''
    form.sort_order = 0
    form.is_active = true
    form.roles = [
        {
            id: null,
            title: 'Role',
            description: '',
            sort_order: 0,
            slots: [
                { id: null, starts_at: '07:00', ends_at: '08:00', needed_count: 1, sort_order: 0 },
            ],
        },
    ]
}

function loadTemplate(template) {
    selectedTemplateId.value = template.id
    form.name = template.name ?? ''
    form.description = template.description ?? ''
    form.sort_order = Number(template.sort_order ?? 0)
    form.is_active = !!template.is_active
    form.roles = (template.roles ?? []).map((role, roleIndex) => ({
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
    }))
}

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

function saveTemplate() {
    if (selectedTemplateId.value) {
        form.put(route('admin.volunteer-signup-templates.update', selectedTemplateId.value), {
            preserveScroll: true,
        })
        return
    }

    form.post(route('admin.volunteer-signup-templates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
        },
    })
}

function deleteTemplate() {
    if (!selectedTemplateId.value) return
    if (!confirm('Delete this template?')) return

    form.delete(route('admin.volunteer-signup-templates.destroy', selectedTemplateId.value), {
        preserveScroll: true,
        onSuccess: () => {
            newTemplate()
        },
    })
}

if (selectedTemplateId.value) {
    const selected = (props.templates ?? []).find((template) => template.id === selectedTemplateId.value)
    if (selected) loadTemplate(selected)
} else {
    newTemplate()
}
</script>

<template>
    <AppLayout title="Volunteer Templates">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Volunteer Signup Templates</h2>
                <Button icon="pi pi-plus" label="New Template" @click="newTemplate" />
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <Card class="lg:col-span-1">
                <template #title>Templates</template>
                <template #content>
                    <div class="space-y-2">
                        <Button
                            v-for="template in templates"
                            :key="template.id"
                            :label="template.name"
                            class="w-full justify-start"
                            :severity="selectedTemplateId === template.id ? null : 'secondary'"
                            :outlined="selectedTemplateId !== template.id"
                            @click="loadTemplate(template)"
                        />
                    </div>
                </template>
            </Card>

            <Card class="lg:col-span-2">
                <template #title>{{ selectedTemplateId ? 'Edit Template' : 'New Template' }}</template>
                <template #content>
                    <Message v-if="flashSuccess" severity="success" class="mb-4">
                        {{ flashSuccess }}
                    </Message>

                    <form class="space-y-6" @submit.prevent="saveTemplate">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Name</label>
                                <InputText v-model="form.name" class="w-full" :class="{ 'p-invalid': !!form.errors.name }" />
                                <small v-if="form.errors.name" class="p-error block mt-2">{{ form.errors.name }}</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Sort Order</label>
                                <InputNumber v-model="form.sort_order" class="w-full" :min="0" showButtons />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Description</label>
                            <Textarea v-model="form.description" rows="3" class="w-full" />
                        </div>

                        <div class="flex items-center gap-3">
                            <ToggleSwitch v-model="form.is_active" inputId="tpl_active" />
                            <label for="tpl_active" class="text-sm">Template is active</label>
                        </div>

                        <Divider />

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold">Roles & Time Slots</h3>
                                <Button icon="pi pi-plus" label="Add Role" severity="secondary" @click="addRole" />
                            </div>

                            <div
                                v-for="(role, roleIndex) in form.roles"
                                :key="role.id ?? `new-role-${roleIndex}`"
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
                                        <h4 class="font-medium">Slots</h4>
                                        <Button size="small" icon="pi pi-plus" label="Add Slot" severity="secondary" @click="addSlot(role)" />
                                    </div>

                                    <div
                                        v-for="(slot, slotIndex) in role.slots"
                                        :key="slot.id ?? `new-slot-${slotIndex}`"
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

                        <div class="flex justify-between gap-3">
                            <Button
                                v-if="selectedTemplateId"
                                icon="pi pi-trash"
                                label="Delete Template"
                                severity="danger"
                                text
                                @click="deleteTemplate"
                            />
                            <div class="ml-auto">
                                <Button type="submit" icon="pi pi-save" label="Save Template" :loading="form.processing" />
                            </div>
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
