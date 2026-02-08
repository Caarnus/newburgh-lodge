<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Button from "primevue/button";

const page = usePage();
const loading = ref(false);

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const form = useForm({
    name: "",
    email: "",
    phone: "",
    subject: "",
    message: "",
});

const submitForm = () => {
    loading.value = true;

    form.post(route("contact.submit"), {
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        },
        onSuccess: () => {
            // Keep contact info filled in, clear the actual message fields
            form.reset("subject", "message");
        },
    });
};
</script>

<template>
    <AppLayout title="Contact">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">
                Contact Us
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-surface-0 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="dark:bg-surface-900 dark:text-surface-200 font-sans">
                        <div class="max-w-3xl mx-auto p-6">
                            <div v-if="flashSuccess" class="mb-4 p-3 rounded-md border border-green-200 bg-green-50 text-green-900 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-100">
                                {{ flashSuccess }}
                            </div>

                            <div v-if="flashError" class="mb-4 p-3 rounded-md border border-red-200 bg-red-50 text-red-900 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-100">
                                {{ flashError }}
                            </div>

                            <form @submit.prevent="submitForm" class="space-y-4">
                                <div class="flex flex-col">
                                    <label for="name" class="font-medium text-surface-700 dark:text-surface-300">Name</label>
                                    <InputText
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Your full name"
                                        class="mt-1 w-full border-surface-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                    />
                                    <small v-if="form.errors.name" class="mt-1 text-red-600 dark:text-red-400">
                                        {{ form.errors.name }}
                                    </small>
                                </div>

                                <div class="flex flex-col">
                                    <label for="email" class="font-medium text-surface-700 dark:text-surface-300">Email *</label>
                                    <InputText
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="Your email address"
                                        class="mt-1 w-full border-surface-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                        required
                                        autocomplete="email"
                                    />
                                    <small v-if="form.errors.email" class="mt-1 text-red-600 dark:text-red-400">
                                        {{ form.errors.email }}
                                    </small>
                                </div>

                                <div class="flex flex-col">
                                    <label for="phone" class="font-medium text-surface-700 dark:text-surface-300">Phone *</label>
                                    <InputText
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="(555) 555-5555"
                                        class="mt-1 w-full border-surface-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                        required
                                        autocomplete="tel"
                                    />
                                    <small v-if="form.errors.phone" class="mt-1 text-red-600 dark:text-red-400">
                                        {{ form.errors.phone }}
                                    </small>
                                </div>

                                <div class="flex flex-col">
                                    <label for="subject" class="font-medium text-surface-700 dark:text-surface-300">Subject *</label>
                                    <InputText
                                        id="subject"
                                        v-model="form.subject"
                                        placeholder="What can we help with?"
                                        class="mt-1 w-full border-surface-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                        required
                                    />
                                    <small v-if="form.errors.subject" class="mt-1 text-red-600 dark:text-red-400">
                                        {{ form.errors.subject }}
                                    </small>
                                </div>

                                <div class="flex flex-col">
                                    <label for="message" class="font-medium text-surface-700 dark:text-surface-300">Message *</label>
                                    <Textarea
                                        id="message"
                                        v-model="form.message"
                                        rows="6"
                                        placeholder="Write your message here..."
                                        class="mt-1 w-full border-surface-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                        required
                                        autoResize
                                    />
                                    <small v-if="form.errors.message" class="mt-1 text-red-600 dark:text-red-400">
                                        {{ form.errors.message }}
                                    </small>
                                </div>

                                <Button
                                    label="Send Message"
                                    class="w-full bg-primary-600 hover:bg-primary-700 text-surface-0 dark:text-surface-950 font-medium py-2 px-4 rounded-md"
                                    :loading="loading || form.processing"
                                    type="submit"
                                />
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
