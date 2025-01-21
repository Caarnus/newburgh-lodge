<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
</script>

<template>
    <AppLayout title="Officers">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Lodge Officers
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="dark:bg-surface-900 dark:text-surface-200 font-sans">
                        <div class="max-w-3xl mx-auto p-6">
                            <section class="mb-6">
                                <table class="mt-4 mx-auto w-full max-w-lg text-left">
                                    <thead>
                                    <tr class="text-primary-700 border-b border-gray-700">
                                        <th class="px-4 py-2 text-right">Title</th>
                                        <th class="px-4 py-2 text-left">Name</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-700">
                                    <tr>
                                        <td class="px-4 py-2 text-right">Worshipful Master:</td>
                                        <td class="px-4 py-2">David Brickey II</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Senior Warden:</td>
                                        <td class="px-4 py-2">Mark Lewellyn, PM</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Junior Warden:</td>
                                        <td class="px-4 py-2">Tom Metzger, PM</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Treasurer:</td>
                                        <td class="px-4 py-2">Chadd Huffman</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Secretary:</td>
                                        <td class="px-4 py-2">Chad Hostetter, PM</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Senior Deacon:</td>
                                        <td class="px-4 py-2">Jonathan Harris</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Junior Deacon:</td>
                                        <td class="px-4 py-2">Casey Garrison</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Senior Steward:</td>
                                        <td class="px-4 py-2">Bryan Bodkin</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Junior Steward:</td>
                                        <td class="px-4 py-2">Open</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Tyler:</td>
                                        <td class="px-4 py-2">Rob Harris</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Chaplain:</td>
                                        <td class="px-4 py-2">Robert Barnes</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Trustee 1:</td>
                                        <td class="px-4 py-2">Ken Mitz, PM</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Trustee 2:</td>
                                        <td class="px-4 py-2">Rod McDonald</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-right">Trustee 3:</td>
                                        <td class="px-4 py-2">Tim Putnam</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
