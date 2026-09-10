<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    openAirRegistrations: Array,
    golfRegistrations: Array,
})
</script>

<template>
    <AppLayout title="Special Registrations">
        <template #header><h2 class="font-semibold text-xl leading-tight">Special Registrations</h2></template>
        <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <section class="bg-surface-0 shadow-xl sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="text-xl font-semibold">Open Air Degree <span class="text-surface-500">({{ openAirRegistrations.length }} registered)</span></h3>
                <table class="w-full mt-4 text-left"><thead><tr class="border-b"><th class="py-2">Name</th><th>Email</th><th>Phone</th><th>Registered</th></tr></thead><tbody><tr v-for="registration in openAirRegistrations" :key="registration.id" class="border-b"><td class="py-2">{{ registration.name }}</td><td>{{ registration.email }}</td><td>{{ registration.phone }}</td><td>{{ new Date(registration.created_at).toLocaleString() }}</td></tr><tr v-if="!openAirRegistrations.length"><td colspan="4" class="py-4 text-surface-500">No registrations yet.</td></tr></tbody></table>
            </section>
            <section class="bg-surface-0 shadow-xl sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="text-xl font-semibold">Golf Scramble <span class="text-surface-500">({{ golfRegistrations.filter((r) => r.registration_type === 'team').length }} teams, {{ golfRegistrations.filter((r) => r.registration_type === 'hole_sponsor').length }} hole sponsors)</span></h3>
                <table class="w-full mt-4 text-left"><thead><tr class="border-b"><th class="py-2">Type</th><th>Name / Team</th><th>Members</th><th>Contact</th><th>Registered</th></tr></thead><tbody><tr v-for="registration in golfRegistrations" :key="registration.id" class="border-b align-top"><td class="py-2 capitalize">{{ registration.registration_type.replace('_', ' ') }}</td><td>{{ registration.team_name || registration.name }}</td><td>{{ registration.team_members?.join(', ') || '—' }}</td><td>{{ registration.email }}<br>{{ registration.phone }}</td><td>{{ new Date(registration.created_at).toLocaleString() }}</td></tr><tr v-if="!golfRegistrations.length"><td colspan="5" class="py-4 text-surface-500">No registrations yet.</td></tr></tbody></table>
            </section>
        </div></div>
    </AppLayout>
</template>
