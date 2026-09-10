<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import SelectButton from 'primevue/selectbutton'

const options = [{ label: 'Hole Sponsor', value: 'hole_sponsor' }, { label: 'Team', value: 'team' }]
const form = useForm({ registration_type: 'hole_sponsor', name: '', email: '', phone: '', team_name: '', team_members: ['', '', '', ''] })
const success = computed(() => usePage().props.flash?.success)
const submit = () => form.post(route('golf-scramble-2027.store'), { preserveScroll: true, onSuccess: () => form.reset() })
</script>

<template>
    <AppLayout title="Golf Scramble 2027">
        <div class="py-12"><div class="max-w-2xl mx-auto sm:px-6 lg:px-8"><div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 sm:p-8">
            <h1 class="text-3xl font-semibold">Golf Scramble 2027</h1>
            <p class="mt-4 text-surface-700">Register your hole sponsorship or four-person team.</p>
            <div v-if="success" class="mt-5 p-3 rounded-md border border-green-200 bg-green-50 text-green-900">{{ success }}</div>
            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <SelectButton v-model="form.registration_type" :options="options" option-label="label" option-value="value" aria-label="Registration type" />
                <div v-if="form.registration_type === 'hole_sponsor'"><label for="name" class="font-medium">Sponsor name</label><InputText id="name" v-model="form.name" class="mt-1 w-full" required/><small v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</small></div>
                <template v-else><div><label for="team-name" class="font-medium">Team name</label><InputText id="team-name" v-model="form.team_name" class="mt-1 w-full" required/><small v-if="form.errors.team_name" class="text-red-600">{{ form.errors.team_name }}</small></div><div v-for="(_, index) in form.team_members" :key="index"><label :for="`member-${index}`" class="font-medium">Team member {{ index + 1 }}<span v-if="index === 0"> (lead/contact)</span></label><InputText :id="`member-${index}`" v-model="form.team_members[index]" class="mt-1 w-full" required/><small v-if="form.errors[`team_members.${index}`]" class="text-red-600">{{ form.errors[`team_members.${index}`] }}</small></div></template>
                <div><label for="phone" class="font-medium">Contact number</label><InputText id="phone" v-model="form.phone" class="mt-1 w-full" required autocomplete="tel"/><small v-if="form.errors.phone" class="text-red-600">{{ form.errors.phone }}</small></div>
                <div><label for="email" class="font-medium">Email address</label><InputText id="email" v-model="form.email" type="email" class="mt-1 w-full" required autocomplete="email"/><small v-if="form.errors.email" class="text-red-600">{{ form.errors.email }}</small></div>
                <Button type="submit" label="Register" :loading="form.processing" />
            </form>
        </div></div></div>
    </AppLayout>
</template>
