<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Divider from 'primevue/divider'

const props = defineProps({
    sheet: Object,
    event: Object,
    roles: Array,
})

const flashSuccess = computed(() => usePage().props.flash?.success)

const startsLabel = computed(() => {
    if (!props.event?.starts_at) return null
    return new Date(props.event.starts_at).toLocaleString()
})

const endsLabel = computed(() => {
    if (!props.event?.ends_at) return null
    return new Date(props.event.ends_at).toLocaleString()
})

const form = useForm({
    name: '',
    email: '',
    slot_ids: [],
})

function toggleSlot(slotId, checked) {
    const ids = new Set(form.slot_ids || [])
    if (checked) {
        ids.add(slotId)
    } else {
        ids.delete(slotId)
    }
    form.slot_ids = Array.from(ids)
}

function isSlotChecked(slotId) {
    return (form.slot_ids || []).includes(slotId)
}

function submit() {
    form.post(route('public.volunteer-signups.store', props.sheet.slug), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('slot_ids')
        },
    })
}
</script>

<template>
    <div class="min-h-screen bg-surface-50">
        <div class="max-w-4xl mx-auto px-4 py-10">
            <Card>
                <template #title>
                    <div class="text-2xl font-semibold text-surface-900">
                        {{ sheet.title }}
                    </div>
                </template>

                <template #content>
                    <div class="text-surface-700 space-y-2">
                        <div v-if="startsLabel">
                            <span class="font-medium text-surface-900">Starts:</span>
                            {{ startsLabel }}
                        </div>
                        <div v-if="endsLabel">
                            <span class="font-medium text-surface-900">Ends:</span>
                            {{ endsLabel }}
                        </div>
                        <div v-if="event?.location">
                            <span class="font-medium text-surface-900">Location:</span>
                            {{ event.location }}
                        </div>
                    </div>

                    <div v-if="sheet.description" class="mt-4 text-surface-700 whitespace-pre-line">
                        {{ sheet.description }}
                    </div>

                    <Message v-if="flashSuccess" severity="success" class="mt-4">
                        {{ flashSuccess }}
                    </Message>

                    <Divider class="my-6" />

                    <form class="space-y-5" @submit.prevent="submit">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-surface-900 mb-2">Name</label>
                                <InputText v-model="form.name" class="w-full" :class="{ 'p-invalid': !!form.errors.name }" />
                                <small v-if="form.errors.name" class="p-error block mt-2">{{ form.errors.name }}</small>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-surface-900 mb-2">Email</label>
                                <InputText v-model="form.email" class="w-full" :class="{ 'p-invalid': !!form.errors.email }" />
                                <small v-if="form.errors.email" class="p-error block mt-2">{{ form.errors.email }}</small>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h3 class="text-base font-semibold text-surface-900">Volunteer Roles & Time Slots</h3>

                            <div
                                v-for="role in roles"
                                :key="role.id"
                                class="rounded-md border border-surface-200 p-4"
                            >
                                <div class="font-semibold text-surface-900">{{ role.title }}</div>
                                <div v-if="role.description" class="text-sm text-surface-600 mt-1">
                                    {{ role.description }}
                                </div>

                                <div class="mt-3 space-y-2">
                                    <label
                                        v-for="slot in role.slots"
                                        :key="slot.id"
                                        class="flex items-center gap-3"
                                    >
                                        <Checkbox
                                            :modelValue="isSlotChecked(slot.id)"
                                            :binary="true"
                                            :disabled="slot.remaining_count <= 0 && !isSlotChecked(slot.id)"
                                            @update:modelValue="(checked) => toggleSlot(slot.id, checked)"
                                        />
                                        <span class="text-sm">
                                            {{ slot.starts_at }} - {{ slot.ends_at }}
                                            ({{ slot.remaining_count }} open / {{ slot.needed_count }} total)
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <small v-if="form.errors.slot_ids" class="p-error block">
                            {{ form.errors.slot_ids }}
                        </small>

                        <Button type="submit" label="Sign Up to Volunteer" icon="pi pi-check" :loading="form.processing" />
                    </form>
                </template>
            </Card>
        </div>
    </div>
</template>
