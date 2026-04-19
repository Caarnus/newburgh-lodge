<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import ToggleSwitch from 'primevue/toggleswitch'
import Message from 'primevue/message'

type ExistingImage = {
    path: string
    url: string
}

type FundraiserDto = {
    id: number
    title: string
    slug: string
    short_description: string | null
    description: string | null
    goal_amount: number
    raised_amount: number
    is_active: boolean
    starts_at: string | null
    ends_at: string | null
    images: ExistingImage[]
}

const props = defineProps<{
    fundraiser: FundraiserDto | null
    qr_download_url: string | null
    public_url: string | null
}>()

const page = usePage()
const isEdit = computed(() => !!props.fundraiser?.id)
const pageTitle = computed(() => (isEdit.value ? 'Edit Fundraiser' : 'Create Fundraiser'))

function toDateTimeLocalValue(iso: string | null): string {
    if (!iso) return ''
    const date = new Date(iso)
    if (Number.isNaN(date.getTime())) return ''

    const pad = (n: number) => String(n).padStart(2, '0')

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

const startsAtLocal = ref(toDateTimeLocalValue(props.fundraiser?.starts_at ?? null))
const endsAtLocal = ref(toDateTimeLocalValue(props.fundraiser?.ends_at ?? null))
const selectedImageNames = ref<string[]>([])

const form = useForm({
    title: props.fundraiser?.title ?? '',
    slug: props.fundraiser?.slug ?? '',
    short_description: props.fundraiser?.short_description ?? '',
    description: props.fundraiser?.description ?? '',
    goal_amount: props.fundraiser?.goal_amount ?? 1000,
    raised_amount: props.fundraiser?.raised_amount ?? 0,
    is_active: props.fundraiser?.is_active ?? true,
    starts_at: props.fundraiser?.starts_at ?? null as string | null,
    ends_at: props.fundraiser?.ends_at ?? null as string | null,
    images: [] as File[],
    remove_image_paths: [] as string[],
})

function onPickImages(event: Event) {
    const input = event.target as HTMLInputElement
    const files = Array.from(input.files ?? [])
    form.images = files
    selectedImageNames.value = files.map((file) => file.name)
}

function updateRemoval(path: string, checked: boolean) {
    if (checked) {
        if (!form.remove_image_paths.includes(path)) {
            form.remove_image_paths.push(path)
        }
        return
    }

    form.remove_image_paths = form.remove_image_paths.filter((value) => value !== path)
}

function onToggleRemove(path: string, event: Event) {
    updateRemoval(path, (event.target as HTMLInputElement).checked)
}

function submit() {
    form.starts_at = startsAtLocal.value || null
    form.ends_at = endsAtLocal.value || null

    if (isEdit.value && props.fundraiser?.id) {
        form.put(route('admin.fundraisers.update', props.fundraiser.id), {
            preserveScroll: true,
            forceFormData: true,
        })
        return
    }

    form.post(route('admin.fundraisers.store'), {
        preserveScroll: true,
        forceFormData: true,
    })
}
</script>

<template>
    <AppLayout :title="pageTitle">
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                    {{ pageTitle }}
                </h2>
                <div class="hidden sm:flex gap-2">
                    <Link :href="route('admin.fundraisers.index')">
                        <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text />
                    </Link>
                    <Button icon="pi pi-save" :label="isEdit ? 'Update' : 'Create'" @click="submit" />
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="sm:hidden mb-4 flex gap-2">
                <Link :href="route('admin.fundraisers.index')">
                    <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text class="w-full" />
                </Link>
                <Button icon="pi pi-save" :label="isEdit ? 'Update' : 'Create'" class="w-full" @click="submit" />
            </div>

            <Card class="shadow-lg rounded-xl overflow-hidden bg-surface-0 dark:bg-surface-900">
                <template #header>
                    <div class="px-6 sm:px-10 pt-8">
                        <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-100">{{ pageTitle }}</h3>
                        <Divider class="mt-4" />
                    </div>
                </template>

                <template #content>
                    <form class="px-6 sm:px-10 pb-8 space-y-6" @submit.prevent="submit">
                        <Message v-if="page.props.flash?.success" severity="success">
                            {{ page.props.flash.success }}
                        </Message>

                        <div v-if="props.public_url" class="text-sm text-surface-600 dark:text-surface-300">
                            Public page:
                            <a :href="props.public_url" class="underline" target="_blank" rel="noopener noreferrer">{{ props.public_url }}</a>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Title</label>
                                <InputText v-model="form.title" class="w-full mt-1" :invalid="!!form.errors.title" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">{{ form.errors.title }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Slug</label>
                                <InputText v-model="form.slug" class="w-full mt-1" :invalid="!!form.errors.slug" />
                                <p class="mt-1 text-xs text-surface-500 dark:text-surface-400">
                                    Leave blank to auto-generate from the title.
                                </p>
                                <p v-if="form.errors.slug" class="mt-1 text-sm text-red-500">{{ form.errors.slug }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Short Description</label>
                                <Textarea v-model="form.short_description" rows="3" class="w-full mt-1" :invalid="!!form.errors.short_description" />
                                <p v-if="form.errors.short_description" class="mt-1 text-sm text-red-500">{{ form.errors.short_description }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Full Description</label>
                                <Textarea v-model="form.description" rows="7" class="w-full mt-1" :invalid="!!form.errors.description" />
                                <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Goal Amount</label>
                                <InputNumber v-model="form.goal_amount" mode="currency" currency="USD" locale="en-US" class="w-full mt-1" :min="1" />
                                <p v-if="form.errors.goal_amount" class="mt-1 text-sm text-red-500">{{ form.errors.goal_amount }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Raised Amount</label>
                                <InputNumber v-model="form.raised_amount" mode="currency" currency="USD" locale="en-US" class="w-full mt-1" :min="0" />
                                <p v-if="form.errors.raised_amount" class="mt-1 text-sm text-red-500">{{ form.errors.raised_amount }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Starts At (optional)</label>
                                <input v-model="startsAtLocal" type="datetime-local" class="mt-1 w-full rounded-md border-surface-300 dark:border-surface-700 dark:bg-surface-900" />
                                <p v-if="form.errors.starts_at" class="mt-1 text-sm text-red-500">{{ form.errors.starts_at }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Ends At (optional)</label>
                                <input v-model="endsAtLocal" type="datetime-local" class="mt-1 w-full rounded-md border-surface-300 dark:border-surface-700 dark:bg-surface-900" />
                                <p v-if="form.errors.ends_at" class="mt-1 text-sm text-red-500">{{ form.errors.ends_at }}</p>
                            </div>

                            <div class="sm:col-span-2 flex items-center gap-3">
                                <ToggleSwitch v-model="form.is_active" inputId="fundraiser-active" />
                                <label for="fundraiser-active" class="text-sm text-surface-700 dark:text-surface-300">
                                    Fundraiser is active and visible to the public
                                </label>
                            </div>
                        </div>

                        <Divider />

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-base font-semibold text-surface-900 dark:text-surface-100">Images</h4>
                                <a v-if="props.qr_download_url" :href="props.qr_download_url">
                                    <Button label="Download QR PNG" icon="pi pi-qrcode" severity="secondary" outlined />
                                </a>
                            </div>

                            <div>
                                <input type="file" accept="image/*" multiple class="block w-full text-sm" @change="onPickImages" />
                                <p v-if="form.errors.images" class="mt-1 text-sm text-red-500">{{ form.errors.images }}</p>
                                <p v-if="form.errors['images.0']" class="mt-1 text-sm text-red-500">{{ form.errors['images.0'] }}</p>
                                <p v-if="selectedImageNames.length" class="mt-2 text-sm text-surface-600 dark:text-surface-300">
                                    Selected: {{ selectedImageNames.join(', ') }}
                                </p>
                            </div>

                            <div
                                v-if="props.fundraiser?.images?.length"
                                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                            >
                                <div
                                    v-for="image in props.fundraiser.images"
                                    :key="image.path"
                                    class="rounded-lg border border-surface-200 dark:border-surface-700 p-2 space-y-2"
                                >
                                    <img :src="image.url" alt="Fundraiser image" class="w-full h-44 object-cover rounded" />
                                    <label class="flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300">
                                        <input
                                            type="checkbox"
                                            :checked="form.remove_image_paths.includes(image.path)"
                                            @change="onToggleRemove(image.path, $event)"
                                        >
                                        Remove this image
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Link :href="route('admin.fundraisers.index')">
                                <Button label="Cancel" severity="secondary" text />
                            </Link>
                            <Button :label="isEdit ? 'Update Fundraiser' : 'Create Fundraiser'" icon="pi pi-save" :loading="form.processing" @click="submit" />
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
