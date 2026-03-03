<script setup>
import { computed } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'

import Card from 'primevue/card'
import Button from 'primevue/button'
import Message from 'primevue/message'

const props = defineProps({
    signup: Object,
    subscriber: Object,
    page: Object,
    event: Object,
})

const flashSuccess = computed(() => usePage().props.flash?.success)
const actionUrl = computed(() => window.location.pathname + window.location.search)

const startsLabel = computed(() => {
    if (!props.event?.starts_at) return null
    return new Date(props.event.starts_at).toLocaleString()
})

const form = useForm({})

function confirmUnsubscribe() {
    form.post(actionUrl.value, { preserveScroll: true })
}
</script>

<template>
    <div class="min-h-screen bg-surface-50">
        <div class="max-w-2xl mx-auto px-4 py-10">
            <Card>
                <template #title>
                    <div class="text-2xl font-semibold text-surface-900">
                        Remove signup — {{ page.title }}
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
                    </div>

                    <Message v-if="flashSuccess" severity="success" class="mt-5">
                        {{ flashSuccess }}
                    </Message>

                    <Message v-else-if="signup.status === 'canceled'" severity="info" class="mt-5">
                        This signup has already been removed.
                    </Message>

                    <div v-if="!flashSuccess && signup.status !== 'canceled'" class="mt-6 space-y-4">
                        <Message severity="warn">
                            This will cancel your signup and stop future reminders.
                        </Message>

                        <div class="flex gap-3">
                            <Button
                                label="Yes, remove me"
                                icon="pi pi-times"
                                severity="danger"
                                :loading="form.processing"
                                @click="confirmUnsubscribe"
                            />
                            <Link href="javascript:history.back()">
                                <Button label="Go back" outlined />
                            </Link>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
