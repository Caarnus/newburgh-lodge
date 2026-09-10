<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'

const form = useForm({ name: '', phone: '', email: '' })
const success = computed(() => usePage().props.flash?.success)
const submit = () => form.post(route('open-air-2026.store'), { preserveScroll: true, onSuccess: () => form.reset() })
</script>

<template>
    <AppLayout title="Warrick County Open Air Degree">
        <div class="py-12"><div class="max-w-2xl mx-auto sm:px-6 lg:px-8"><div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 sm:p-8">
            <h1 class="text-3xl font-semibold">Warrick County Open Air Degree</h1>
            <p class="mt-4 text-lg">Saturday, September 26, 2026</p>
            <p class="mt-1">Dinner served at 5:00 p.m. Degree begins at 6:30 p.m.</p>
            <p class="mt-5 text-surface-700">Please register so we can plan for dinner and attendance.</p>
            <div v-if="success" class="mt-5 p-3 rounded-md border border-green-200 bg-green-50 text-green-900">{{ success }}</div>
            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div><label for="name" class="font-medium">Name</label><InputText id="name" v-model="form.name" class="mt-1 w-full" required autocomplete="name"/><small v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</small></div>
                <div><label for="phone" class="font-medium">Phone number</label><InputText id="phone" v-model="form.phone" class="mt-1 w-full" required autocomplete="tel"/><small v-if="form.errors.phone" class="text-red-600">{{ form.errors.phone }}</small></div>
                <div><label for="email" class="font-medium">Email address</label><InputText id="email" v-model="form.email" type="email" class="mt-1 w-full" required autocomplete="email"/><small v-if="form.errors.email" class="text-red-600">{{ form.errors.email }}</small></div>
                <Button type="submit" label="Register" :loading="form.processing" />
            </form>
        </div></div></div>
    </AppLayout>
</template>
