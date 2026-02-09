<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const $page = usePage()

const props = defineProps({
    albums: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
})

const search = ref('')

const filteredAlbums = computed(() => {
    if (!search.value) return props.albums
    const q = search.value.toLowerCase()
    return props.albums.filter(a =>
        (a.title || '').toLowerCase().includes(q) ||
        (a.description || '').toLowerCase().includes(q)
    )
})

const canManageEffective = computed(() => {
    // Support either explicit prop, or a shared `$page.props.can` structure if you later add it
    return !!props.canManage || !!$page.props?.can?.gallery?.manage || !!$page.props?.can?.manageGallery
})
</script>

<template>
    <AppLayout title="Photo Gallery">
        <template #header>
            <div class="flex flex-row justify-between">
                <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                    Photo Gallery
                </h2>
                <div v-if="canManageEffective" class="flex justify-end">
                    <Link :href="route('admin.gallery.index')">
                        <Button icon="pi pi-images" label="Manage" />
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-bold text-surface-900 dark:text-surface-100">
                    Albums
                </h2>
                <span class="p-input-icon-left w-full sm:w-auto">
                    <i class="pi pi-search mr-2" />
                    <InputText v-model="search" placeholder="Search albums..." class="w-full sm:w-64" />
                </span>
            </div>

            <div v-if="filteredAlbums.length === 0" class="bg-surface-0 dark:bg-surface-800 shadow rounded-lg p-6">
                <p class="text-surface-700 dark:text-surface-200">No albums yet.</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="album in filteredAlbums"
                    :key="album.id"
                    :href="route('gallery.show', album.slug)"
                    class="block"
                >
                    <Card class="shadow-lg border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden hover:shadow-xl transition">
                        <template #header>
                            <div class="aspect-[16/10] bg-surface-100 dark:bg-surface-800">
                                <img
                                    v-if="album.cover_url"
                                    :src="album.cover_url"
                                    :alt="album.title"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                />
                            </div>
                        </template>

                        <template #title>
                            <div class="flex items-center justify-between gap-3">
                                <span class="truncate">{{ album.title }}</span>
                                <Tag v-if="album.visibility === 'members'" value="Members" severity="warning" />
                            </div>
                        </template>

                        <template #content>
                            <p v-if="album.description" class="text-sm text-surface-600 dark:text-surface-300 line-clamp-2">
                                {{ album.description }}
                            </p>
                            <p class="mt-2 text-xs text-surface-500 dark:text-surface-400">
                                {{ album.photos_count }} photo{{ album.photos_count === 1 ? '' : 's' }}
                            </p>
                        </template>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
