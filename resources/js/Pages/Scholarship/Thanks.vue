<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import Button from "primevue/button";

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const thanksMode = computed(() => page.props.thanksMode ?? "pending");

const title = computed(() => {
    if (thanksMode.value === "verified") return "Application submitted";
    if (thanksMode.value === "already_verified") return "Already submitted";
    return "Check your email";
});

const body = computed(() => {
    if (thanksMode.value === "verified") {
        return "Thanks — your email has been verified and your scholarship application is officially submitted.";
    }
    if (thanksMode.value === "already_verified") {
        return "Our records show this application has already been verified and submitted.";
    }
    return "We’ve sent you a verification email. Please click the verification link to submit your application.";
});
</script>

<template>
    <AppLayout title="Scholarship Application">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">Scholarship Application</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-surface-0 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="dark:bg-surface-900 dark:text-surface-200 font-sans">
                        <div class="p-6 space-y-4">
                            <div v-if="flashSuccess" class="p-3 rounded-md border border-green-200 bg-green-50 text-green-900 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-100">
                                {{ flashSuccess }}
                            </div>

                            <div v-if="flashError" class="p-3 rounded-md border border-red-200 bg-red-50 text-red-900 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-100">
                                {{ flashError }}
                            </div>

                            <h3 class="text-lg font-semibold">{{ title }}</h3>
                            <p class="opacity-80">{{ body }}</p>

                            <ul v-if="thanksMode === 'pending'" class="list-disc pl-6 opacity-80 space-y-1">
                                <li>Check your spam/junk folder.</li>
                                <li>Make sure you entered your email correctly.</li>
                                <li>The verification link will expire (typically within 48 hours).</li>
                            </ul>

                            <div class="pt-2">
                                <Link :href="route('scholarship.apply')">
                                    <Button label="Back to Application" class="bg-primary-600 hover:bg-primary-700 text-surface-0 dark:text-surface-950" />
                                </Link>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
