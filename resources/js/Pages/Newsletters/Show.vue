<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Divider from 'primevue/divider'

type Newsletter = {
    id: number
    title: string
    body?: string | null
    created_at: string
    issue?: string | null
    author?: string | null
}

const $page = usePage()
const props = defineProps<{ newsletter: Newsletter }>()

const title = computed(() => props.newsletter.title || 'Newsletter')
const publishedDate = computed(() =>
    props.newsletter.created_at ? new Date(props.newsletter.created_at).toLocaleDateString() : ''
)
const html = computed(() => props.newsletter.body ?? '')
const orgName = computed(() => (($page.props as any)?.site?.orgName || 'Newburgh Lodge No. 174 F. & A.M.') as string)
const label = computed(() => (($page.props as any)?.site?.newsletterLabel || 'Newsletter') as string)
const subhead = computed(() =>
    props.newsletter.issue ? `${props.newsletter.issue} • ${publishedDate.value}` : publishedDate.value
)
const byLine = computed(() =>
    props.newsletter.author ? `By: ${props.newsletter.author}` : ''
)

function printPage() {
    window.print()
}
</script>

<template>
    <AppLayout :title="title">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ label }}
                </h2>
                <div class="hidden sm:flex gap-2 print:hidden">
                    <Link :href="route?.('newsletters.index')">
                        <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text />
                    </Link>
                    <Button icon="pi pi-print" label="Print" @click="printPage" />
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- mobile actions -->
            <div class="sm:hidden mb-4 flex gap-2 print:hidden">
                <Link :href="route?.('newsletters.index')">
                    <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text class="w-full" />
                </Link>
                <Button icon="pi pi-print" label="Print" class="w-full" @click="printPage" />
            </div>

            <Card class="print-area shadow-lg rounded-xl overflow-hidden bg-white dark:bg-gray-900 print:bg-white">
                <!-- header slot -->
                <template #header>
                    <div class="px-6 sm:px-10 pt-8 text-center">
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-wide text-gray-900 dark:text-gray-100 uppercase">
                            {{ orgName }}
                        </h1>
                        <p class="mt-1 text-sm sm:text-base text-gray-700 dark:text-gray-300">
                            {{ title }}
                        </p>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{ subhead }}
                        </p>
                        <p v-if="byLine" class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{ byLine }}
                        </p>
                        <Divider class="mt-4" />
                    </div>
                </template>

                <!-- content slot -->
                <template #content>
                    <div class="px-6 sm:px-10 pb-8">
                        <article
                            class="newsletter-content prose prose-neutral dark:prose-invert max-w-none text-gray-800 dark:text-gray-100"
                            v-html="html"
                        />
                    </div>
                </template>

                <!-- footer slot -->
                <template #footer>
                    <div class="px-6 sm:px-10 pb-8 text-center text-xs text-gray-500 dark:text-gray-400">
                        <Divider class="mb-2" />
                        <p>
                            {{ orgName }}<span v-if="publishedDate"> • Published {{ publishedDate }}</span>
                        </p>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Screen typography polish */
:deep(.newsletter-content) {
    font-family: ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;
    line-height: 1.7;
}
:deep(.newsletter-content h2) { margin-top: 1.25rem; }
:deep(.newsletter-content img) { border-radius: 0.25rem; }

/* Optional: drop cap for first paragraph on screen */
:deep(.newsletter-content > p:first-of-type)::first-letter {
    float: left;
    font-size: 3.25rem;
    line-height: 0.9;
    padding-right: 0.25rem;
    padding-top: 0.15rem;
    font-weight: 700;
    color: var(--tw-prose-headings);
}

/* Print styles to mimic the PDF look */
@media print {
    @page {
        size: Letter;
        margin: 0.5in;
    }

    /* hide app chrome */
    .p-button,
    [data-pc-section="header"],
    [data-pc-section="footer"],
    nav,
    header[role="banner"],

    .print-area, .print-area * {
        background: #ffffff !important;
        color: #000000 !important;
        box-shadow: none !important;
    }

    /* Two columns with tidy flow */
    .newsletter-content {
        column-count: 2;
        column-gap: 0.35in;
        orphans: 3;
        widows: 3;
    }

    /* Avoid awkward breaks */
    .newsletter-content h1,
    .newsletter-content h2,
    .newsletter-content h3,
    .newsletter-content h4,
    .newsletter-content figure,
    .newsletter-content table,
    .newsletter-content blockquote,
    .newsletter-content ul,
    .newsletter-content ol {
        break-inside: avoid;
    }
}

/* Optional wide-screen preview spacing between columns */
@media (min-width: 1280px) {
    .newsletter-content { column-gap: 2rem; }
}
</style>
