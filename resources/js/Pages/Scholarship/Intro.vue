<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link } from "@inertiajs/vue3";

import Button from "primevue/button";
import Card from "primevue/card";
import Divider from "primevue/divider";
import Message from "primevue/message";
</script>

<template>
    <AppLayout title="Scholarship">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">Newburgh Lodge Scholarship</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-surface-0 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="dark:bg-surface-900 dark:text-surface-200 font-sans">
                        <div class="p-6 space-y-6">
                            <Card>
                                <template #title>Before you apply</template>
                                <template #content>
                                    <div class="space-y-4 opacity-90 leading-relaxed">
                                        <p>
                                            Newburgh Lodge #174 is proud to support students who are investing in their future and
                                            strengthening the community around them. This scholarship is open to any student who is a
                                            resident of Warrick County, Indiana.
                                        </p>

                                        <Message severity="info" :closable="false">
                                            <div class="space-y-1">
                                                <div class="font-medium">Most important quality we look for:</div>
                                                <div>
                                                    <b>Community involvement.</b> Freemasons place a high value on improving our community
                                                    and its people. We’re looking for applicants who show a pattern of helping others,
                                                    showing up, and contributing in meaningful ways—at school, through service,
                                                    work/volunteering, or local organizations.
                                                </div>
                                            </div>
                                        </Message>

                                        <Divider />

                                        <div class="space-y-2">
                                            <div class="text-lg font-semibold">Eligibility</div>
                                            <ul class="list-disc pl-6 space-y-1">
                                                <li>Open to any student in <b>Warrick County, Indiana</b>.</li>
                                            </ul>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-lg font-semibold">Preferences</div>
                                            <ul class="list-disc pl-6 space-y-1">
                                                <li>
                                                    Preference may be given to applicants who are <b>graduating from high school this year</b> and
                                                    plan to attend a <b>trade school</b> or <b>college</b> in the fall.
                                                </li>
                                                <li>
                                                    Preference may be given to applicants who have a <b>connection to Newburgh Lodge</b>
                                                    (family, friends, involvement with Lodge activities, etc.). A connection is not required
                                                    to apply.
                                                </li>
                                            </ul>
                                        </div>

                                        <Divider />

                                        <div class="space-y-2">
                                            <div class="text-lg font-semibold">Scholarship amount</div>
                                            <ul class="list-disc pl-6 space-y-1">
                                                <li>
                                                    The scholarship will be awarded as either <b>one $1,000 scholarship</b> or <b>two $500 scholarships</b>,
                                                    depending on the applicant pool.
                                                </li>
                                            </ul>

                                            <p class="opacity-90">
                                                If there are multiple worthy applicants, we will rely on other information in the application,
                                                including the reason the applicant applied, awards, letters of recommendation, GPA, and transcript.
                                            </p>
                                        </div>

                                        <Divider />

                                        <div class="space-y-2">
                                            <div class="text-lg font-semibold">What you’ll need</div>
                                            <ul class="list-disc pl-6 space-y-1">
                                                <li>
                                                    A <b>copy of your transcript</b> is required to complete the application.
                                                    (PDF or image is acceptable.)
                                                </li>
                                                <li>
                                                    You’ll answer a short prompt and provide some background information to help us make a decision.
                                                </li>
                                            </ul>
                                        </div>

                                        <Message severity="warn" :closable="false">
                                            <div class="space-y-1">
                                                <div class="font-medium">Email verification required</div>
                                                <div>
                                                    After you submit the application form, we will email you a verification link.
                                                    <b>Your application is not considered submitted until you click that link.</b>
                                                    Please use an email address you can access.
                                                </div>
                                            </div>
                                        </Message>
                                    </div>
                                </template>

                                <template #footer>
                                    <div class="flex flex-col sm:flex-row gap-3 sm:justify-center">
                                        <Link :href="route('scholarship.apply')">
                                            <Button
                                                label="Start Application"
                                                icon="pi pi-arrow-right"
                                                class="bg-primary-600 hover:bg-primary-700 text-surface-0 dark:text-surface-950"
                                            />
                                        </Link>
                                    </div>
                                </template>
                            </Card>

                            <div class="text-xs opacity-70">
                                If you have questions about the scholarship or the application process, please
                                <a class="text-blue-600 underline" :href="route('contact')">contact Newburgh Lodge #174</a>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
