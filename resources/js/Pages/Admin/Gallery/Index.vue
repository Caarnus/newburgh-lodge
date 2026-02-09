<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import Card from 'primevue/card'

import ConfirmsPassword from '@/Components/ConfirmsPassword.vue'
import {route} from "ziggy-js";

type AlbumRow = {
    id: number
    title: string
    slug: string
    description: string | null
    visibility: 'public' | 'members'
    enabled: boolean
    sort: number
    photos_count: number
}

type PhotoRow = {
    id: number
    photo_album_id: number | null
    caption: string | null
    alt_text: string | null
    visibility: 'public' | 'members'
    enabled: boolean
    sort: number
    url: string
    thumb_url: string
}

const props = defineProps<{
    albums: AlbumRow[]
    selectedAlbumId: number | null
    photos: PhotoRow[]
}>()

const selectedAlbumId = ref<number | null>(props.selectedAlbumId ?? null)

watch(() => props.selectedAlbumId, (v) => {
    selectedAlbumId.value = v ?? null
})

const selectedAlbum = computed(() => props.albums.find(a => a.id === selectedAlbumId.value) ?? null)

function selectAlbum(id: number) {
    router.get(route('admin.gallery.index'), { album: id }, { preserveScroll: true, preserveState: true })
}

/** Create Album */
const showCreateAlbum = ref(false)
const albumCreateForm = useForm({
    title: '',
    description: '',
    visibility: 'public' as 'public' | 'members',
    enabled: true,
})

function openCreateAlbum() {
    albumCreateForm.reset()
    showCreateAlbum.value = true
}
function createAlbum() {
    albumCreateForm.post(route('admin.gallery.albums.store'), {
        preserveScroll: true,
        onSuccess: () => (showCreateAlbum.value = false),
    })
}

/** Edit Album */
const showEditAlbum = ref(false)
const editingAlbum = ref<AlbumRow | null>(null)
const albumEditForm = useForm({
    title: '',
    description: '',
    visibility: 'public' as 'public' | 'members',
    enabled: true,
    sort: 0,
})

function openEditAlbum(a: AlbumRow) {
    editingAlbum.value = a
    albumEditForm.title = a.title
    albumEditForm.description = a.description ?? ''
    albumEditForm.visibility = a.visibility
    albumEditForm.enabled = a.enabled
    albumEditForm.sort = a.sort ?? 0
    showEditAlbum.value = true
}
function saveAlbum() {
    if (!editingAlbum.value) return
    albumEditForm.put(route('admin.gallery.albums.update', editingAlbum.value.id), {
        preserveScroll: true,
        onSuccess: () => (showEditAlbum.value = false),
    })
}
function deleteAlbum(a: AlbumRow) {
    router.delete(route('admin.gallery.albums.destroy', a.id), { preserveScroll: true })
}

/** Upload Photo */
const showUpload = ref(false)
const uploadForm = useForm({
    photo: null as File | null,
    photo_album_id: selectedAlbumId.value,
    visibility: (selectedAlbum.value?.visibility ?? 'public') as 'public' | 'members',
    caption: '',
    alt_text: '',
    enabled: true,
})

watch(selectedAlbumId, (id) => {
    uploadForm.photo_album_id = id
    if (selectedAlbum.value) uploadForm.visibility = selectedAlbum.value.visibility
})

function onPickFile(e: Event) {
    const input = e.target as HTMLInputElement
    uploadForm.photo = input.files?.[0] ?? null
}

function openUpload() {
    uploadForm.reset()
    uploadForm.photo_album_id = selectedAlbumId.value
    uploadForm.visibility = (selectedAlbum.value?.visibility ?? 'public') as any
    showUpload.value = true
}

function uploadPhoto() {
    uploadForm.photo_album_id = selectedAlbumId.value
    uploadForm.post(route('admin.gallery.photos.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => (showUpload.value = false),
    })
}

/** Edit Photo */
const showEditPhoto = ref(false)
const editingPhoto = ref<PhotoRow | null>(null)
const photoEditForm = useForm({
    photo_album_id: selectedAlbumId.value as number | null,
    visibility: 'public' as 'public' | 'members',
    caption: '',
    alt_text: '',
    enabled: true,
    sort: 0,
})

function openEditPhoto(p: PhotoRow) {
    editingPhoto.value = p
    photoEditForm.photo_album_id = p.photo_album_id
    photoEditForm.visibility = p.visibility
    photoEditForm.caption = p.caption ?? ''
    photoEditForm.alt_text = p.alt_text ?? ''
    photoEditForm.enabled = p.enabled
    photoEditForm.sort = p.sort ?? 0
    showEditPhoto.value = true
}

function savePhoto() {
    if (!editingPhoto.value) return
    photoEditForm.put(route('admin.gallery.photos.update', editingPhoto.value.id), {
        preserveScroll: true,
        onSuccess: () => (showEditPhoto.value = false),
    })
}

function deletePhoto(p: PhotoRow) {
    router.delete(route('admin.gallery.photos.destroy', p.id), { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="Gallery Management">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                    Gallery Management
                </h2>

                <div class="flex items-center gap-2">
                    <Link :href="route('gallery.index')">
                        <Button icon="pi pi-eye" label="View Gallery" severity="secondary" />
                    </Link>
                    <Button icon="pi pi-folder-plus" label="Add Album" @click="openCreateAlbum" />
                    <Button
                        icon="pi pi-upload"
                        label="Upload Photo"
                        :disabled="!selectedAlbumId"
                        @click="openUpload"
                    />
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <!-- Album List -->
            <Card class="bg-surface-0 dark:bg-surface-900 shadow">
                <template #content>
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-4">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-bold text-surface-900 dark:text-surface-100">Albums</h3>
                            <Tag v-if="selectedAlbum" :value="selectedAlbum.visibility" :severity="selectedAlbum.visibility === 'members' ? 'warning' : 'success'" />
                        </div>

                        <Dropdown
                            v-model="selectedAlbumId"
                            :options="props.albums"
                            optionLabel="title"
                            optionValue="id"
                            placeholder="Select album"
                            class="w-full sm:w-96"
                            @update:modelValue="selectAlbum"
                        />
                    </div>

                    <DataTable :value="props.albums" paginator :rows="10" striped-rows responsive-layout="scroll" dataKey="id">
                        <Column field="title" header="Title" :sortable="true">
                            <template #body="{ data }">
                                <button class="text-primary hover:underline" type="button" @click="selectAlbum(data.id)">
                                    {{ data.title }}
                                </button>
                            </template>
                        </Column>

                        <Column field="visibility" header="Visibility" :sortable="true">
                            <template #body="{ data }">
                                <Tag :value="data.visibility" :severity="data.visibility === 'members' ? 'warning' : 'success'" />
                            </template>
                        </Column>

                        <Column field="photos_count" header="Photos" :sortable="true" />

                        <Column header="Actions">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-pencil" text rounded v-tooltip.top="'Edit album'" @click="openEditAlbum(data)" />
                                    <ConfirmsPassword @confirmed="() => deleteAlbum(data)">
                                        <Button icon="pi pi-trash" text rounded severity="danger" v-tooltip.top="'Delete album'" />
                                    </ConfirmsPassword>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Photos -->
            <Card class="bg-surface-0 dark:bg-surface-900 shadow">
                <template #content>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-surface-900 dark:text-surface-100">
                            Photos
                            <span v-if="selectedAlbum" class="text-surface-500 dark:text-surface-400 font-normal">
                                — {{ selectedAlbum.title }}
                            </span>
                        </h3>
                    </div>

                    <div v-if="!selectedAlbumId" class="text-surface-600 dark:text-surface-300">
                        Select an album to manage photos.
                    </div>

                    <div v-else-if="props.photos.length === 0" class="text-surface-600 dark:text-surface-300">
                        No photos in this album yet.
                    </div>

                    <DataTable
                        v-else
                        :value="props.photos"
                        paginator
                        :rows="12"
                        striped-rows
                        responsive-layout="scroll"
                        dataKey="id"
                    >
                        <Column header="Preview">
                            <template #body="{ data }">
                                <img :src="data.thumb_url" alt="Thumbnail" class="h-14 w-14 object-cover rounded-lg border border-surface-200 dark:border-surface-700" />
                            </template>
                        </Column>

                        <Column field="caption" header="Caption" :sortable="true">
                            <template #body="{ data }">
                                <span class="text-surface-800 dark:text-surface-100">
                                    {{ data.caption || '—' }}
                                </span>
                            </template>
                        </Column>

                        <Column field="visibility" header="Visibility" :sortable="true">
                            <template #body="{ data }">
                                <Tag :value="data.visibility" :severity="data.visibility === 'members' ? 'warning' : 'success'" />
                            </template>
                        </Column>

                        <Column header="Actions">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-pencil" text rounded v-tooltip.top="'Edit photo'" @click="openEditPhoto(data)" />
                                    <ConfirmsPassword @confirmed="() => deletePhoto(data)">
                                        <Button icon="pi pi-trash" text rounded severity="danger" v-tooltip.top="'Delete photo'" />
                                    </ConfirmsPassword>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>

        <!-- Create Album Dialog -->
        <Dialog v-model:visible="showCreateAlbum" header="Create Album" modal class="w-full sm:w-[34rem]">
            <div class="space-y-4">
                <div>
                    <label class="text-sm">Title</label>
                    <InputText v-model="albumCreateForm.title" class="w-full" />
                    <p v-if="albumCreateForm.errors.title" class="text-red-500 text-sm mt-1">{{ albumCreateForm.errors.title }}</p>
                </div>

                <div>
                    <label class="text-sm">Description</label>
                    <Textarea v-model="albumCreateForm.description" rows="4" class="w-full" />
                    <p v-if="albumCreateForm.errors.description" class="text-red-500 text-sm mt-1">{{ albumCreateForm.errors.description }}</p>
                </div>

                <div>
                    <label class="text-sm">Visibility</label>
                    <Dropdown v-model="albumCreateForm.visibility" :options="['public','members']" class="w-full" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button icon="pi pi-times-circle" severity="secondary" text @click="showCreateAlbum=false" />
                    <ConfirmsPassword @confirmed="createAlbum">
                        <Button icon="pi pi-check" aria-label="Create" v-tooltip.top="'Create album'" />
                    </ConfirmsPassword>
                </div>
            </div>
        </Dialog>

        <!-- Edit Album Dialog -->
        <Dialog v-model:visible="showEditAlbum" header="Edit Album" modal class="w-full sm:w-[34rem]">
            <div class="space-y-4">
                <div>
                    <label class="text-sm">Title</label>
                    <InputText v-model="albumEditForm.title" class="w-full" />
                    <p v-if="albumEditForm.errors.title" class="text-red-500 text-sm mt-1">{{ albumEditForm.errors.title }}</p>
                </div>

                <div>
                    <label class="text-sm">Description</label>
                    <Textarea v-model="albumEditForm.description" rows="4" class="w-full" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm">Visibility</label>
                        <Dropdown v-model="albumEditForm.visibility" :options="['public','members']" class="w-full" />
                    </div>
                    <div>
                        <label class="text-sm">Sort</label>
                        <InputText v-model="albumEditForm.sort" class="w-full" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button icon="pi pi-times-circle" severity="secondary" text @click="showEditAlbum=false" />
                    <ConfirmsPassword @confirmed="saveAlbum">
                        <Button icon="pi pi-save" aria-label="Save" v-tooltip.top="'Save changes'" />
                    </ConfirmsPassword>
                </div>
            </div>
        </Dialog>

        <!-- Upload Photo Dialog -->
        <Dialog v-model:visible="showUpload" header="Upload Photo" modal class="w-full sm:w-[34rem]">
            <div class="space-y-4">
                <div v-if="!selectedAlbumId" class="text-surface-600 dark:text-surface-300">
                    Select an album first.
                </div>

                <div v-else>
                    <div class="mb-3 text-sm text-surface-600 dark:text-surface-300">
                        Uploading to: <strong class="text-surface-900 dark:text-surface-100">{{ selectedAlbum?.title }}</strong>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <input type="file" accept="image/*" @change="onPickFile" class="block w-full text-sm" />
                            <p v-if="uploadForm.errors.photo" class="text-red-500 text-sm mt-1">{{ uploadForm.errors.photo }}</p>
                        </div>

                        <div>
                            <label class="text-sm">Visibility</label>
                            <Dropdown v-model="uploadForm.visibility" :options="['public','members']" class="w-full" />
                        </div>

                        <div>
                            <label class="text-sm">Caption</label>
                            <InputText v-model="uploadForm.caption" class="w-full" />
                        </div>

                        <div>
                            <label class="text-sm">Alt text</label>
                            <InputText v-model="uploadForm.alt_text" class="w-full" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button icon="pi pi-times-circle" severity="secondary" text @click="showUpload=false" />
                        <ConfirmsPassword @confirmed="uploadPhoto">
                            <Button icon="pi pi-upload" aria-label="Upload" :disabled="!uploadForm.photo" v-tooltip.top="'Upload photo'" />
                        </ConfirmsPassword>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- Edit Photo Dialog -->
        <Dialog v-model:visible="showEditPhoto" header="Edit Photo" modal class="w-full sm:w-[36rem]">
            <div v-if="editingPhoto" class="space-y-4">
                <img :src="editingPhoto.url" alt="Photo" class="w-full rounded-xl border border-surface-200 dark:border-surface-700" />

                <div>
                    <label class="text-sm">Album</label>
                    <Dropdown
                        v-model="photoEditForm.photo_album_id"
                        :options="props.albums"
                        optionLabel="title"
                        optionValue="id"
                        class="w-full"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm">Visibility</label>
                        <Dropdown v-model="photoEditForm.visibility" :options="['public','members']" class="w-full" />
                    </div>
                    <div>
                        <label class="text-sm">Sort</label>
                        <InputText v-model="photoEditForm.sort" class="w-full" />
                    </div>
                </div>

                <div>
                    <label class="text-sm">Caption</label>
                    <InputText v-model="photoEditForm.caption" class="w-full" />
                </div>

                <div>
                    <label class="text-sm">Alt text</label>
                    <InputText v-model="photoEditForm.alt_text" class="w-full" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button icon="pi pi-times-circle" severity="secondary" text @click="showEditPhoto=false" />
                    <ConfirmsPassword @confirmed="savePhoto">
                        <Button icon="pi pi-save" aria-label="Save" v-tooltip.top="'Save changes'" />
                    </ConfirmsPassword>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
