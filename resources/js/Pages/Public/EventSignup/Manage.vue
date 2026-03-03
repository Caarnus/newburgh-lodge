<script setup>
import { computed } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'

import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Divider from 'primevue/divider'

const props = defineProps({
    signup: Object,
    subscriber: Object,
    page: Object,
    event: Object,
    unsubscribe_url: String,
})

const flashSuccess = computed(() => usePage().props.flash?.success)

// Important: submit back to the CURRENT signed URL (keeps signature query params)
const actionUrl = computed(() => window.location.pathname + window.location.search)

const startsLabel = computed(() => {
    if (!props.event?.starts_at) return null
    return new Date(props.event.starts_at).toLocaleString()
})

const form = useForm({
    remind_week_before: !!props.signup.remind_week_before,
    remind_day_before: !!props.signup.remind_day_before,
    remind_hour_before: !!props.signup.remind_hour_before,
})

function submit() {
    form.patch(actionUrl.value, {
        preserveScroll: true,
    })
}
</script>

<template>
    <div class="min-h-screen bg-surface-50">
        <div class="max-w-3xl mx-auto px-4 py-10">
            <Card>
                <template #title>
                    <div class="text-2xl font-semibold text-surface-900">
                        Manage signup — {{ page.title }}
                    </div>
                </template>

                <template #content>
                    <div class="text-surface-700 space-y-2">
                        <div>
                            <span class="font-medium text-surface-900">Email:</span>
                            {{ subscriber.email }}
                        </div>

                        <div v-if="startsLabel">
                            <span class="font-medium text-surface-900">Starts:</span>
                            {{ startsLabel }}
                        </div>

                        <div v-if="event?.location">
                            <span class="font-medium text-surface-900">Location:</span>
                            {{ event.location }}
                        </div>

                        <Message v-if="signup.status === 'canceled'" severity="warn" class="mt-3">
                            This signup has been canceled. You can still adjust preferences, but no reminders will be sent unless you re-sign up.
                        </Message>
                    </div>

                    <Message v-if="flashSuccess" severity="success" class="mt-5">
                        {{ flashSuccess }}
                    </Message>

                    <Divider class="my-6" />

                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="rounded-xl border border-surface-200 p-4">
                            <div class="font-medium text-surface-900 mb-3">Email reminders</div>

                            <div class="space-y-3 text-surface-700">
                                <div class="flex items-center gap-3">
                                    <Checkbox v-model="form.remind_week_before" inputId="wk" binary />
                                    <label for="wk" class="cursor-pointer select-none">1 week before</label>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Checkbox v-model="form.remind_day_before" inputId="day" binary />
                                    <label for="day" class="cursor-pointer select-none">1 day before</label>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Checkbox v-model="form.remind_hour_before" inputId="hr" binary />
                                    <label for="hr" class="cursor-pointer select-none">1 hour before</label>
                                </div>
                            </div>

                            <small v-if="form.hasErrors" class="p-error block mt-3">
                                Please review your selections.
                            </small>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <Button
                                type="submit"
                                label="Save preferences"
                                icon="pi pi-save"
                                :loading="form.processing"
                            />

                            <Link :href="unsubscribe_url" class="sm:ml-auto">
                                <Button
                                    type="button"
                                    label="Remove my signup"
                                    icon="pi pi-times"
                                    severity="danger"
                                    outlined
                                />
                            </Link>
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </div>
</template>
