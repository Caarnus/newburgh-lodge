<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue'
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
import Editor from 'primevue/editor'
import FileUpload from 'primevue/fileupload'
import Select from 'primevue/select'

type ExistingImage = {
    path: string
    url: string
}

type FundraiserDto = {
    id: number
    title: string
    category_id: number | null
    sort_order: number
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

type FundraiserCategory = {
    id: number
    name: string
    slug: string
}

type SelectedImagePreview = {
    name: string
    url: string
}

const props = defineProps<{
    fundraiser: FundraiserDto | null
    categories: FundraiserCategory[]
    qr_download_url: string | null
    public_url: string | null
}>()

const page = usePage()
const isEdit = computed(() => !!props.fundraiser?.id)
const pageTitle = computed(() => (isEdit.value ? 'Edit Fundraiser' : 'Create Fundraiser'))
const descriptionMode = ref<'visual' | 'html'>('visual')
const selectedImagePreviews = ref<SelectedImagePreview[]>([])

function toDateValue(iso: string | null): string {
    if (!iso) return ''
    const date = new Date(iso)
    if (Number.isNaN(date.getTime())) return ''

    const pad = (n: number) => String(n).padStart(2, '0')

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

const startsAtDate = ref(toDateValue(props.fundraiser?.starts_at ?? null))
const endsAtDate = ref(toDateValue(props.fundraiser?.ends_at ?? null))
const fallbackCategoryId = props.categories[0]?.id ?? null

const form = useForm({
    title: props.fundraiser?.title ?? '',
    category_id: props.fundraiser?.category_id ?? fallbackCategoryId,
    sort_order: props.fundraiser?.sort_order ?? 0,
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
    _method: '' as '' | 'put',
})

function revokePreviewUrls() {
    for (const preview of selectedImagePreviews.value) {
        URL.revokeObjectURL(preview.url)
    }
    selectedImagePreviews.value = []
}

function setSelectedImages(files: File[]) {
    form.images = files
    revokePreviewUrls()

    selectedImagePreviews.value = files.map((file) => ({
        name: file.name,
        url: URL.createObjectURL(file),
    }))
}

function onImageSelect(event: any) {
    setSelectedImages(Array.isArray(event?.files) ? event.files : [])
}

function onImageRemove(event: any) {
    if (Array.isArray(event?.files)) {
        setSelectedImages(event.files)
        return
    }

    const removed = event?.file as File | undefined
    if (!removed) {
        return
    }

    setSelectedImages(
        form.images.filter((file) => !(file.name === removed.name && file.size === removed.size))
    )
}

function onImageClear() {
    setSelectedImages([])
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
    form.starts_at = startsAtDate.value || null
    form.ends_at = endsAtDate.value || null
    form.category_id = form.category_id ? Number(form.category_id) : null
    form.sort_order = Number(form.sort_order ?? 0)
    form.goal_amount = Number(form.goal_amount ?? 0)
    form.raised_amount = Number(form.raised_amount ?? 0)

    if (isEdit.value && props.fundraiser?.id) {
        form._method = 'put'
        form.post(route('admin.fundraisers.update', props.fundraiser.id), {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => {
                form._method = ''
            },
        })
        return
    }

    form._method = ''
    form.post(route('admin.fundraisers.store'), {
        preserveScroll: true,
        forceFormData: true,
    })
}

onUnmounted(() => {
    revokePreviewUrls()
})
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
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Category</label>
                                <Select
                                    v-model="form.category_id"
                                    :options="props.categories"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Select a category"
                                    class="w-full mt-1"
                                />
                                <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-500">{{ form.errors.category_id }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Slug</label>
                                <InputText v-model="form.slug" class="w-full mt-1" :invalid="!!form.errors.slug" />
                                <p class="mt-1 text-xs text-surface-500 dark:text-surface-400">
                                    Leave blank to auto-generate from the title.
                                </p>
                                <p v-if="form.errors.slug" class="mt-1 text-sm text-red-500">{{ form.errors.slug }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Sort Order</label>
                                <InputNumber v-model="form.sort_order" class="w-full mt-1" :min="0" :max="1000000" />
                                <p v-if="form.errors.sort_order" class="mt-1 text-sm text-red-500">{{ form.errors.sort_order }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Short Description</label>
                                <Textarea v-model="form.short_description" rows="3" class="w-full mt-1" :invalid="!!form.errors.short_description" />
                                <p v-if="form.errors.short_description" class="mt-1 text-sm text-red-500">{{ form.errors.short_description }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Full Description</label>
                                    <div class="flex gap-2">
                                        <Button
                                            label="Visual"
                                            size="small"
                                            :severity="descriptionMode === 'visual' ? undefined : 'secondary'"
                                            :outlined="descriptionMode !== 'visual'"
                                            @click="descriptionMode = 'visual'"
                                        />
                                        <Button
                                            label="HTML"
                                            size="small"
                                            :severity="descriptionMode === 'html' ? undefined : 'secondary'"
                                            :outlined="descriptionMode !== 'html'"
                                            @click="descriptionMode = 'html'"
                                        />
                                    </div>
                                </div>

                                <div v-if="descriptionMode === 'visual'" class="pv-editor rounded-md border border-[color:var(--p-content-border-color)] overflow-hidden mt-1">
                                    <Editor v-model="form.description" class="pv-editor__inner" />
                                </div>
                                <Textarea
                                    v-else
                                    v-model="form.description"
                                    rows="12"
                                    class="w-full mt-1 font-mono text-sm"
                                    :invalid="!!form.errors.description"
                                />
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
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Starts On (optional)</label>
                                <input v-model="startsAtDate" type="date" class="mt-1 w-full rounded-md border-surface-300 dark:border-surface-700 dark:bg-surface-900" />
                                <p v-if="form.errors.starts_at" class="mt-1 text-sm text-red-500">{{ form.errors.starts_at }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Ends On (optional)</label>
                                <input v-model="endsAtDate" type="date" class="mt-1 w-full rounded-md border-surface-300 dark:border-surface-700 dark:bg-surface-900" />
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

                            <FileUpload
                                mode="advanced"
                                name="images[]"
                                accept="image/*"
                                :multiple="true"
                                :maxFileSize="5242880"
                                :customUpload="true"
                                :showUploadButton="false"
                                chooseLabel="Choose Images"
                                cancelLabel="Clear"
                                @select="onImageSelect"
                                @remove="onImageRemove"
                                @clear="onImageClear"
                            />

                            <p v-if="form.errors.images" class="mt-1 text-sm text-red-500">{{ form.errors.images }}</p>
                            <p v-if="form.errors['images.0']" class="mt-1 text-sm text-red-500">{{ form.errors['images.0'] }}</p>

                            <div v-if="selectedImagePreviews.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="preview in selectedImagePreviews"
                                    :key="preview.url"
                                    class="rounded-lg border border-surface-200 dark:border-surface-700 p-2 space-y-2"
                                >
                                    <img :src="preview.url" :alt="preview.name" class="w-full aspect-[4/3] object-cover rounded" />
                                    <p class="text-xs text-surface-500 dark:text-surface-400 truncate">{{ preview.name }}</p>
                                </div>
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
                                    <img :src="image.url" alt="Fundraiser image" class="w-full aspect-[4/3] object-cover rounded" />
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

<style scoped>
.pv-editor__inner :deep(.ql-container) {
    min-height: 14rem;
}
</style>
