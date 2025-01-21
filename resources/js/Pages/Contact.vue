<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import {Button, InputText, Textarea, useToast} from "primevue";
import {ref} from "vue";
import {router} from "@inertiajs/vue3";

const toast = useToast();

const form = ref({
    name: '',
    email: '',
    message: ''
});
const loading = ref(false);

const submitForm = () => {
    loading.value = true;
    router.post(route('contact'), form.value, {
        onFinish: () => {
            loading.value = false;
        },
        onSuccess: () => {
            form.value = { name: '', email: '', message: '' };
            toast.add({
                severity: "success",
                summary: "Submitted",
                detail: "Your request has been submitted, thank you.",
                life: 3000,
            })
        }
    });
};

</script>

<template>
    <AppLayout title="Contact">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Contact Us
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="dark:bg-surface-900 dark:text-surface-200 font-sans">
                        <div class="max-w-3xl mx-auto p-6">
                            <form @submit.prevent="submitForm" class="space-y-4">
                                <div class="flex flex-col">
                                    <label for="name" class="font-medium text-gray-700">Name</label>
                                    <InputText
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Your full name"
                                        class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                        required
                                    />
                                </div>

                                <div class="flex flex-col">
                                    <label for="email" class="font-medium text-gray-700">Email</label>
                                    <InputText
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="Your email address"
                                        class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                        required
                                    />
                                </div>

                                <div class="flex flex-col">
                                    <label for="message" class="font-medium text-gray-700">Message</label>
                                    <Textarea
                                        id="message"
                                        v-model="form.message"
                                        rows="5"
                                        placeholder="Write your message here..."
                                        class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                        required
                                    />
                                </div>

                                <Button
                                    label="Send Message"
                                    class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-md"
                                    :loading="loading"
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

<style scoped>

</style>
