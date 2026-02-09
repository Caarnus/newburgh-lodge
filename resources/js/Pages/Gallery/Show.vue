<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

import Card from 'primevue/card'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'

const $page = usePage()

const props = defineProps({
    album: { type: Object, required: true },
    photos: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
})

const lightboxOpen = ref(false)
const active = ref(null)

function openPhoto(photo) {
    active.value = photo
    lightboxOpen.value = true
}

const canManageEffective = () =>
    !!props.canManage || !!$page.props?.can?.gallery?.manage || !!$page.props?.can?.manageGallery
</script>

<template>
    <AppLayout :title="props.album?.title || 'Gallery'">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('gallery.index')" class="text-sm text-surface-600 hover:underline dark:text-surface-300">
                        ← Gallery
                    </Link>
                    <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                        {{ album.title }}
                    </h2>
                    <Tag v-if="album.visibility === 'members'" value="Members" severity="warning" />
                </div>

                <div v-if="canManageEffective()" class="flex justify-end">
                    <Link :href="route('admin.gallery.index')">
                        <Button icon="pi pi-images" label="Manage" />
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8 space-y-6">
            <Card class="bg-surface-0 dark:bg-surface-900 shadow">
                <template #content>
                    <p v-if="album.description" class="text-surface-700 dark:text-surface-200 mb-4">
                        {{ album.description }}
                    </p>

                    <div v-if="photos.length === 0" class="text-surface-600 dark:text-surface-300">
                        No photos in this album yet.
                    </div>

                    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <button
                            v-for="p in photos"
                            :key="p.id"
                            type="button"
                            class="rounded-lg overflow-hidden border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 hover:shadow transition"
                            @click="openPhoto(p)"
                        >
                            <div class="aspect-square">
                                <img
                                    :src="p.thumb_url"
                                    :alt="p.alt_text || p.caption || 'Photo'"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                />
                            </div>
                        </button>
                    </div>
                </template>
            </Card>

            <Dialog v-model:visible="lightboxOpen" modal :header="active?.caption || 'Photo'" class="w-full sm:w-[56rem]">
                <div v-if="active" class="space-y-3">
                    <img :src="active.url" :alt="active.alt_text || active.caption || 'Photo'" class="w-full rounded-xl" />
                    <p v-if="active.caption" class="text-sm text-surface-700 dark:text-surface-200">
                        {{ active.caption }}
                    </p>
                    <div class="flex justify-end">
                        <Button label="Close" severity="secondary" @click="lightboxOpen = false" />
                    </div>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>
