<script setup lang="ts">
import { computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import InputText from 'primevue/inputtext'
import InputSwitch from 'primevue/inputswitch'
import Textarea from 'primevue/textarea'
import Editor from 'primevue/editor' // Rich text editor

type Newsletter = {
    id?: number
    title?: string
    issue?: string | null
    summary?: string | null
    body?: string | null
    is_public?: boolean
}

const $page = usePage()
const props = defineProps<{ newsletter: Newsletter | null; label?: string }>()

const isEdit = computed(() => !!props.newsletter?.id)
const pageTitle = computed(() => isEdit.value ? `Edit ${props.label ?? 'Newsletter'}` : `Create ${props.label ?? 'Newsletter'}`)

const form = useForm<Required<Newsletter>>({
    title: props.newsletter?.title ?? '',
    issue: props.newsletter?.issue ?? '',
    summary: props.newsletter?.summary ?? '',
    body: props.newsletter?.body ?? '',
    is_public: props.newsletter?.is_public ?? true,
})

function submit() {
    if (isEdit.value && props.newsletter?.id) {
        form.put(route('newsletters.update', props.newsletter.id), {
            preserveScroll: true,
        })
    } else {
        form.post(route('newsletters.store'), { preserveScroll: true })
    }
}
</script>

<template>
    <AppLayout :title="pageTitle">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                    {{ pageTitle }}
                </h2>
                <div class="hidden sm:flex gap-2">
                    <Link :href="route?.('newsletters.index')">
                        <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text />
                    </Link>
                    <Button icon="pi pi-save" :label="isEdit ? 'Update' : 'Create'" @click="submit" />
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="sm:hidden mb-4 flex gap-2">
                <Link :href="route?.('newsletters.index')">
                    <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text class="w-full" />
                </Link>
                <Button icon="pi pi-save" :label="isEdit ? 'Update' : 'Create'" class="w-full" @click="submit" />
            </div>

            <Card class="shadow-lg rounded-xl overflow-hidden bg-surface-0 dark:bg-surface-900">
                <template #header>
                    <div class="px-6 sm:px-10 pt-8">
                        <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-100">
                            {{ isEdit ? 'Edit' : 'New' }} {{ $page.props?.site?.newsletterLabel ?? 'Newsletter' }}
                        </h3>
                        <Divider class="mt-4" />
                    </div>
                </template>

                <template #content>
                    <form class="px-6 sm:px-10 pb-8 space-y-6" @submit.prevent="submit">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Title</label>
                            <InputText v-model="form.title" class="w-full mt-1" :invalid="!!form.errors.title" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">{{ form.errors.title }}</p>
                        </div>

                        <!-- Issue + Public toggle -->
                        <div class="flex flex-wrap items-center gap-6">
                            <!-- Issue -->
                            <div class="flex-1 min-w-[200px]">
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">
                                    Issue (e.g. May 2025)
                                </label>
                                <InputText
                                    v-model="form.issue"
                                    class="w-full mt-1"
                                    :invalid="!!form.errors.issue"
                                />
                                <p v-if="form.errors.issue" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.issue }}
                                </p>
                            </div>

                            <!-- Public toggle -->
                            <div class="flex items-center gap-3 pt-6 sm:pt-[1.625rem]">
                                <label for="is_public" class="text-sm text-surface-700 dark:text-surface-300 select-none">
                                    Public
                                </label>
                                <InputSwitch
                                    v-model="form.is_public"
                                    inputId="is_public"
                                    class="scale-90 origin-center"
                                />
                            </div>
                        </div>

                        <!-- Summary -->
                        <div>
                            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Summary</label>
                            <Textarea v-model="form.summary" class="w-full mt-1" rows="3" :invalid="!!form.errors.summary" />
                            <p v-if="form.errors.summary" class="mt-1 text-sm text-red-500">{{ form.errors.summary }}</p>
                        </div>

                        <!-- Body (HTML) -->
                        <div>
                            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Body</label>
<!--                            <Editor v-model="form.body" class="mt-2" :pt="{ toolbar: { class: 'sticky top-0 z-10' } }" />-->
                            <div class="pv-editor rounded-md border border-[color:var(--p-content-border-color)] overflow-hidden">
                                <Editor v-model="form.body" class="pv-editor__inner" />
                            </div>
                            <p v-if="form.errors.body" class="mt-1 text-sm text-red-500">{{ form.errors.body }}</p>
                            <p class="mt-2 text-xs text-surface-500 dark:text-surface-400">
                                Printing will automatically format into two columns (letter size), matching the PDF layout.
                            </p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Link :href="route?.('newsletters.index')">
                                <Button label="Cancel" severity="secondary" text />
                            </Link>
                            <Button :label="isEdit ? 'Update' : 'Create'" icon="pi pi-save" @click="submit" />
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
