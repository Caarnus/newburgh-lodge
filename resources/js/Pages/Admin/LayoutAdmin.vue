<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { useToast } from 'primevue'

import AppLayout from '@/Layouts/AppLayout.vue'
import ContentGrid from '@/Components/ContentGrid.vue'
import TileEditorDialog from '@/Components/Tiles/TileEditorDialog.vue'

import Button from 'primevue/button'

const page = usePage()
const toast = useToast()

type Tile = {
    id: number | string | null
    page: string
    type: string
    slug: string
    title?: string | null
    config: Record<string, any>
    col_start: number
    row_start: number
    col_span: number
    row_span: number
    sort: number
    enabled: boolean
}

// grid config (match public)
const cols = ref<number>(4)
const rowHeightPx = ref<number>(120)

// reactive local copy (so we can reorder without committing immediately)
const tiles = ref<Tile[]>([])
const deep = (v:any)=> JSON.parse(JSON.stringify(v ?? []))
onMounted(()=> { tiles.value = deep(page.props.tiles) })
watch(()=> page.props.tiles, (next)=> { tiles.value = deep(next) })

// dialog/editor
const showEdit = ref(false)
const selected = ref<Tile | null>(null)

function clampLayout(t: Tile) {
    const total = cols.value || 4
    t.col_start = Math.min(Math.max(1, t.col_start ?? 1), total)
    const maxSpan = Math.max(1, total - t.col_start + 1)
    t.col_span  = Math.min(Math.max(1, t.col_span ?? 1), maxSpan)
    t.row_start = Math.max(1, t.row_start ?? 1)
    t.row_span  = Math.max(1, t.row_span ?? 1)
}

function newTile() {
    const sort = tiles.value.length ? Math.max(...tiles.value.map(x => x.sort || 0)) + 10 : 0
    selected.value = {
        id: null, page: 'welcome', type: 'text', slug: '',
        title: '', config: { html: '<p>New tile</p>' },
        col_start: 1, row_start: 1, col_span: 1, row_span: 1,
        sort, enabled: true,
    }
    showEdit.value = true
}

function editTile(t: Tile) {
    selected.value = JSON.parse(JSON.stringify(t))
    showEdit.value = true
}

function deleteTile(t: Tile) {
    if (!t.id) return
    router.delete(route('admin.content.destroy', t.id), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Tile deleted' })
            router.reload({ only: ['tiles'], preserveScroll: true })
        },
    })
}

function saveFromDialog(updated:any) {
    const t = updated
    const method = t.id ? 'put' : 'post'
    const url = t.id ? route('admin.content.update', t.id) : route('admin.content.store')
    router[method](url, t, {
        onSuccess: () => {
            toast.add({ severity:'success', summary:'Saved' })
            showEdit.value = false
            router.reload({ only:['tiles'], preserveScroll:true })
        }
    })
}


function moveUp(idx: number) {
    if (idx <= 0) return
    const list = tiles.value
    ;[list[idx - 1], list[idx]] = [list[idx], list[idx - 1]]
}

function moveDown(idx: number) {
    const list = tiles.value
    if (idx >= list.length - 1) return
        ;[list[idx + 1], list[idx]] = [list[idx], list[idx + 1]]
}

function saveLayout() {
    const payload = tiles.value.map((t, i) => {
        const copy = { ...t }; clampLayout(copy as Tile)
        return {
            id: copy.id,
            sort: i * 10,
            col_start: copy.col_start,
            row_start: copy.row_start,
            col_span:  copy.col_span,
            row_span:  copy.row_span,
        }
    })
    router.post(route('admin.content.reorder'), { tiles: payload }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Layout updated' })
            router.reload({ only: ['tiles'], preserveScroll: true })
        }
    })
}

// badges (admin preview)
function badgeClass(type?: string) {
    const t = (type ?? '').toLowerCase()
    if (t.includes('image')) return 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300'
    if (t.includes('text')) return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
    if (t.includes('news') || t.includes('newsletter')) return 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
    return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
}

function previewText(tile: Tile) {
    const t = (tile.type ?? '').toLowerCase()
    if (t.includes('newsletter') || t.includes('news')) return 'Newsletter / Compass Points preview'
    if (t.includes('cta')) return 'Call-to-Action preview'
    if (t.includes('events')) return 'Events preview'
    if (t.includes('links')) return 'Links preview'
    if (t.includes('image')) return 'Image tile preview'
    return 'Text tile preview'
}

const totalCols = () => cols.value || 4
const maxColSpan = (colStart: number) => Math.max(1, totalCols() - Math.max(1, colStart) + 1)

// keep values in range as user types
function normalizeSelectedLayout() {
    const s = selected.value
    if (!s) return
    // col_start
    s.col_start = Math.min(Math.max(1, Number(s.col_start) || 1), totalCols())
    // col_span depends on col_start
    const maxSpan = maxColSpan(s.col_start)
    s.col_span = Math.min(Math.max(1, Number(s.col_span) || 1), maxSpan)
    // rows
    s.row_start = Math.max(1, Number(s.row_start) || 1)
    s.row_span  = Math.max(1, Number(s.row_span)  || 1)
}

watch(() => selected.value?.col_start, () => normalizeSelectedLayout())


</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl leading-tight">Configure Home Page</h2>
                <div class="flex gap-2">
                    <Button label="New Tile" @click="newTile" />
                    <Button label="Save Layout" severity="secondary" @click="saveLayout" />
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-surface-900 dark:text-surface-100">
            <ContentGrid
                :tiles="tiles"
                :cols="cols"
                :row-height-px="rowHeightPx"
                :fill="true"
            >
                <template #tile="{ tile }">
                    <div class="h-full flex flex-col rounded-2xl border border-zinc-200/70 bg-white shadow-sm dark:border-zinc-800/60 dark:bg-zinc-900">
                        <!-- 3-col grid header prevents overlap on small tiles -->
                        <div class="grid grid-cols-[1fr_auto_auto] items-center gap-2 border-b border-zinc-100 p-2.5 text-sm dark:border-zinc-800">
                            <!-- Title (truncate with reserved padding) -->
                            <div class="truncate font-medium">
                                {{ tile.title ?? tile.slug ?? 'Untitled tile' }}
                            </div>

                            <!-- Badge -->
                            <span class="justify-self-end rounded-full px-2 py-0.5 text-xs whitespace-nowrap" :class="badgeClass(tile.type)">
                                {{ tile.type }}
                            </span>

                            <!-- Actions (icon-only, compact) -->
                            <div class="flex items-center justify-end gap-1">
                                <Button
                                    icon="pi pi-arrow-up"
                                    size="small"
                                    text
                                    rounded
                                    aria-label="Move up"
                                    @click="moveUp(tiles.indexOf(tile))"
                                />
                                <Button
                                    icon="pi pi-arrow-down"
                                    size="small"
                                    text
                                    rounded
                                    aria-label="Move down"
                                    @click="moveDown(tiles.indexOf(tile))"
                                />
                                <Button
                                    icon="pi pi-pencil"
                                    size="small"
                                    text
                                    rounded
                                    aria-label="Edit"
                                    @click="editTile(tile)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    size="small"
                                    text
                                    rounded
                                    severity="danger"
                                    aria-label="Delete"
                                    @click="deleteTile(tile)"
                                />
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 p-3 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ previewText(tile) }}
                        </div>
                    </div>
                </template>

            </ContentGrid>
        </div>

        <TileEditorDialog
            v-model:visible="showEdit"
            :tile="selected"
            :cols="cols"
            :rowHeightPx="rowHeightPx"
            :typeOptions="[
                { label: 'Call To Action', value: 'cta' },
                { label: 'Events',         value: 'events' },
                { label: 'Image',          value: 'image' },
                { label: 'Image + Text',   value: 'image_text' },
                { label: 'Links',          value: 'links' },
                { label: 'Newsletter',     value: 'newsletter' },
                { label: 'Text',           value: 'text' },
              ]"
            @save="saveFromDialog"
        />
    </AppLayout>
</template>

<style scoped>
/* none */
</style>
