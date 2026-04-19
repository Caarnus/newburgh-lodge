<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

type FundraiserShow = {
    id: number
    title: string
    slug: string
    short_description: string | null
    description: string | null
    goal_amount: number
    raised_amount: number
    progress_percent: number
    image_urls: string[]
}

const props = defineProps<{
    fundraiser: FundraiserShow
}>()

const currency = computed(() =>
    new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 })
)

const imageViewerVisible = ref(false)
const currentImageIndex = ref(0)

const currentImageUrl = computed(() => props.fundraiser.image_urls[currentImageIndex.value] ?? null)
const imageCount = computed(() => props.fundraiser.image_urls.length)

function progressWidth(value: number): string {
    return `${Math.max(0, Math.min(100, value))}%`
}

function openImageViewer(index: number) {
    currentImageIndex.value = index
    imageViewerVisible.value = true
}

function showNextImage() {
    if (imageCount.value < 2) return
    currentImageIndex.value = (currentImageIndex.value + 1) % imageCount.value
}

function showPreviousImage() {
    if (imageCount.value < 2) return
    currentImageIndex.value = (currentImageIndex.value - 1 + imageCount.value) % imageCount.value
}
</script>

<template>
    <AppLayout :title="props.fundraiser.title">
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-xl text-surface-800 dark:text-surface-100 leading-tight">
                    {{ props.fundraiser.title }}
                </h2>
                <Link :href="route('fundraisers.index')">
                    <Button label="All Fundraisers" icon="pi pi-arrow-left" text />
                </Link>
            </div>
        </template>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <Card class="shadow-sm border border-surface-200 dark:border-surface-700">
                <template #content>
                    <div class="space-y-2 mb-6">
                        <div class="flex items-center justify-between text-sm text-surface-600 dark:text-surface-300">
                            <span>Raised {{ currency.format(props.fundraiser.raised_amount) }}</span>
                            <span>Goal {{ currency.format(props.fundraiser.goal_amount) }}</span>
                        </div>
                        <div class="h-4 rounded-full bg-surface-200 dark:bg-surface-700 overflow-hidden">
                            <div class="h-full bg-emerald-500 transition-all" :style="{ width: progressWidth(props.fundraiser.progress_percent) }" />
                        </div>
                        <div class="text-xs text-surface-500 dark:text-surface-400 text-right">
                            {{ props.fundraiser.progress_percent.toFixed(1) }}%
                        </div>
                    </div>

                    <div v-if="props.fundraiser.description" class="fundraiser-description text-surface-800 dark:text-surface-100 mb-8" v-html="props.fundraiser.description" />

                    <div v-if="props.fundraiser.image_urls.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <button
                            v-for="(imageUrl, idx) in props.fundraiser.image_urls"
                            :key="`${imageUrl}-${idx}`"
                            type="button"
                            class="rounded-xl overflow-hidden border border-surface-200 dark:border-surface-700 bg-surface-0"
                            @click="openImageViewer(idx)"
                        >
                            <img
                                :src="imageUrl"
                                alt="Fundraiser image"
                                class="w-full aspect-[4/3] object-cover"
                            >
                        </button>
                    </div>
                </template>
            </Card>
        </div>

        <Dialog
            v-model:visible="imageViewerVisible"
            modal
            dismissableMask
            :draggable="false"
            :style="{ width: 'min(96vw, 1100px)' }"
            :breakpoints="{ '960px': '96vw' }"
        >
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="text-sm text-surface-600 dark:text-surface-300">
                        Image {{ currentImageIndex + 1 }} of {{ imageCount }}
                    </div>
                    <Button
                        icon="pi pi-times"
                        text
                        rounded
                        severity="secondary"
                        @click="imageViewerVisible = false"
                    />
                </div>
            </template>

            <div class="relative">
                <img
                    v-if="currentImageUrl"
                    :src="currentImageUrl"
                    alt="Fundraiser image"
                    class="w-full max-h-[76vh] object-contain rounded-lg bg-black/90"
                >

                <button
                    v-if="imageCount > 1"
                    type="button"
                    class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-black/60 text-white h-10 w-10"
                    @click="showPreviousImage"
                >
                    <i class="pi pi-chevron-left" />
                </button>

                <button
                    v-if="imageCount > 1"
                    type="button"
                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-black/60 text-white h-10 w-10"
                    @click="showNextImage"
                >
                    <i class="pi pi-chevron-right" />
                </button>
            </div>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.fundraiser-description :deep(p),
.fundraiser-description :deep(ul),
.fundraiser-description :deep(ol) {
    margin-bottom: 1rem;
}
</style>

