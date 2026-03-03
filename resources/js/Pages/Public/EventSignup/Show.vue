<script setup>
import { computed, onMounted, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Divider from 'primevue/divider'

const props = defineProps({
    page: Object,
    event: Object,
})

const startedAt = ref(null)

onMounted(() => {
    startedAt.value = new Date().toISOString()
})

const form = useForm({
    name: '',
    email: '',
    phone: '',
    remind_week_before: true,
    remind_day_before: true,
    remind_hour_before: true,

    // Honeypot fields
    website: '',
    hp_started_at: '',
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

function submit() {
    form.hp_started_at = startedAt.value || new Date().toISOString()

    form.post(route('public.signup.store', props.page.slug), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'email', 'phone')
            form.remind_week_before = true
            form.remind_day_before = true
            form.remind_hour_before = true
            startedAt.value = new Date().toISOString()
        },
    })
}
</script>

<template>
    <div class="min-h-screen bg-surface-50">
        <div class="max-w-3xl mx-auto px-4 py-10">
            <Card class="overflow-hidden">
                <template #header>
                    <div v-if="page.cover_image_url" class="w-full">
                        <img :src="page.cover_image_url" alt="" class="h-full w-full object-cover" />
                    </div>
                </template>

                <template #title>
                    <div class="text-2xl md:text-3xl font-semibold text-surface-900">
                        {{ page.title }}
                    </div>
                </template>

                <template #content>
                    <!-- Event meta -->
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

                    <div v-if="page.description" class="mt-5 text-surface-700 whitespace-pre-line">
                        {{ page.description }}
                    </div>

                    <Message v-if="flashSuccess" class="mt-6" severity="success">
                        {{ flashSuccess }}
                    </Message>

                    <Divider class="my-6" />

                    <form class="space-y-5" @submit.prevent="submit">
                        <!-- Honeypot (kept in DOM, visually hidden) -->
                        <div class="hidden" aria-hidden="true">
                            <input v-model="form.website" type="text" autocomplete="off" tabindex="-1" />
                            <input v-model="form.hp_started_at" type="text" autocomplete="off" tabindex="-1" />
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-surface-900 mb-2">
                                Name (optional)
                            </label>
                            <InputText
                                v-model="form.name"
                                class="w-full"
                                :class="{ 'p-invalid': !!form.errors.name }"
                                autocomplete="name"
                            />
                            <small v-if="form.errors.name" class="p-error block mt-2">
                                {{ form.errors.name }}
                            </small>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-surface-900 mb-2">
                                Email
                            </label>
                            <InputText
                                v-model="form.email"
                                class="w-full"
                                :class="{ 'p-invalid': !!form.errors.email }"
                                autocomplete="email"
                                inputmode="email"
                            />
                            <small v-if="form.errors.email" class="p-error block mt-2">
                                {{ form.errors.email }}
                            </small>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-surface-900 mb-2">
                                Phone (optional)
                            </label>
                            <InputText
                                v-model="form.phone"
                                class="w-full"
                                :class="{ 'p-invalid': !!form.errors.phone }"
                                autocomplete="tel"
                            />
                            <small v-if="form.errors.phone" class="p-error block mt-2">
                                {{ form.errors.phone }}
                            </small>
                        </div>

                        <!-- Reminders -->
                        <div class="rounded-xl border border-surface-200 p-4">
                            <div class="font-medium text-surface-900 mb-3">
                                Email reminders
                            </div>

                            <div class="space-y-3 text-surface-700">
                                <div class="flex items-center gap-3">
                                    <Checkbox v-model="form.remind_week_before" inputId="remind_week_before" binary />
                                    <label for="remind_week_before" class="cursor-pointer select-none">
                                        1 week before
                                    </label>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Checkbox v-model="form.remind_day_before" inputId="remind_day_before" binary />
                                    <label for="remind_day_before" class="cursor-pointer select-none">
                                        1 day before
                                    </label>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Checkbox v-model="form.remind_hour_before" inputId="remind_hour_before" binary />
                                    <label for="remind_hour_before" class="cursor-pointer select-none">
                                        1 hour before
                                    </label>
                                </div>
                            </div>

                            <small
                                v-if="form.errors.remind_week_before || form.errors.remind_day_before || form.errors.remind_hour_before"
                                class="p-error block mt-3"
                            >
                                Please review your reminder selections.
                            </small>
                        </div>

                        <!-- Honeypot / timing errors -->
                        <Message
                            v-if="form.errors.hp_started_at || form.errors.website"
                            class="mt-2"
                            severity="error"
                        >
                            {{ form.errors.hp_started_at || form.errors.website }}
                        </Message>

                        <div class="pt-2">
                            <Button
                                type="submit"
                                label="Sign up"
                                icon="pi pi-check"
                                :loading="form.processing"
                            />
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </div>
</template>
