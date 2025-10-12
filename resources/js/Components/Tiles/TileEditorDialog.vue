<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import ToggleSwitch from 'primevue/toggleswitch'
import Button from 'primevue/button'

import { editorRegistry } from './editorRegistry' // see section 2

type Tile = {
    id: number|string|null
    page: string
    type: string
    slug: string
    title?: string|null
    config: Record<string, any>
    col_start: number
    row_start: number
    col_span: number
    row_span: number
    sort: number
    enabled: boolean
}

const props = defineProps<{
    visible: boolean
    tile: Tile|null
    cols: number
    rowHeightPx?: number
    typeOptions: Array<{label:string,value:string}>
}>()

const emit = defineEmits<{
    (e:'update:visible', v:boolean): void
    (e:'save', tile: Tile): void
    (e:'cancel'): void
}>()

const local = ref<Tile | null>(null)
watch(() => props.tile, (t)=> { local.value = t ? JSON.parse(JSON.stringify(t)) : null }, { immediate:true })

const totalCols = computed(()=> props.cols || 4)
const maxColSpan = (colStart: number) => Math.max(1, totalCols.value - Math.max(1, colStart) + 1)

function normalizeLayout() {
    if (!local.value) return
    const s = local.value
    s.col_start = Math.min(Math.max(1, Number(s.col_start) || 1), totalCols.value)
    s.col_span  = Math.min(Math.max(1, Number(s.col_span)  || 1), maxColSpan(s.col_start))
    s.row_start = Math.max(1, Number(s.row_start) || 1)
    s.row_span  = Math.max(1, Number(s.row_span)  || 1)
}

function onSave() {
    if (!local.value) return
    normalizeLayout()
    emit('save', local.value)
}

function onHide() {
    emit('update:visible', false)
    emit('cancel')
}

const open = computed({
    get: () => props.visible,
    set: (v: boolean) => emit('update:visible', v),
})
</script>

<template>
    <Dialog
        v-model:visible="open"
        modal
        :closable="true"
        :draggable="false"
        header="Edit Tile"
        :style="{ width: '56rem' }"
        @hide="open = false"
    >
        <div v-if="local" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- LEFT: Common meta + type-specific editor -->
            <div class="lg:col-span-2 space-y-5">
                <!-- Common meta -->
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-4 space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2">
                            <span class="w-20">Title</span>
                            <InputText v-model="local.title" class="flex-1" />
                        </label>
                        <label class="flex items-center gap-2">
                            <span class="w-20">Slug</span>
                            <InputText v-model="local.slug" class="flex-1" placeholder="(auto if blank)" />
                        </label>
                        <label class="flex items-center gap-2 col-span-2">
                            <span class="w-20">Type</span>
                            <Select v-model="local.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full"/>
                        </label>
                        <label class="flex items-center gap-2">
                            <span class="w-20">Enabled</span>
                            <ToggleSwitch v-model="local.enabled" />
                        </label>
                    </div>
                </div>

                <!-- Type-specific editor -->
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-4">
                    <component
                        :is="editorRegistry[local.type]"
                        v-if="editorRegistry[local.type]"
                        :config="local.config"
                        @update:config="val => (local!.config = val)"
                    />
                    <div v-else class="text-sm text-zinc-500">No editor registered for <b>{{ local.type }}</b>.</div>
                </div>
            </div>

            <!-- RIGHT: Layout controls -->
            <div class="space-y-4">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-4">
                    <div class="text-sm font-semibold mb-3">Layout</div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="min-w-0">
                            <label class="block text-xs mb-1">Column Start</label>
                            <div class="flex items-center gap-2">
                                <InputNumber v-model="local.col_start" :min="1" :max="totalCols" :step="1" :useGrouping="false" showButtons inputClass="w-20" class="w-28" @update:modelValue="normalizeLayout" />
                            </div>
                        </div>
                        <div class="min-w-0">
                            <label class="block text-xs mb-1">Column Span</label>
                            <div class="flex items-center gap-2">
                                <InputNumber v-model="local.col_span" :min="1" :max="maxColSpan(local.col_start)" :step="1" :useGrouping="false" showButtons inputClass="w-20" class="w-28" @update:modelValue="normalizeLayout" />
                            </div>
                        </div>
                        <div class="min-w-0">
                            <label class="block text-xs mb-1">Row Start</label>
                            <div class="flex items-center gap-2">
                                <InputNumber v-model="local.row_start" :min="1" :step="1" :useGrouping="false" showButtons inputClass="w-20" class="w-28" @update:modelValue="normalizeLayout" />
                            </div>
                        </div>
                        <div class="min-w-0">
                            <label class="block text-xs mb-1">Row Span</label>
                            <div class="flex items-center gap-2">
                                <InputNumber v-model="local.row_span" :min="1" :step="1" :useGrouping="false" showButtons inputClass="w-20" class="w-28" @update:modelValue="normalizeLayout" />
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-[11px] opacity-60">
                        Grid: {{ totalCols }} columns · Base row height {{ rowHeightPx ?? 120 }}px
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Cancel" severity="secondary" @click="onHide"/>
                    <Button label="Save" @click="onSave"/>
                </div>
            </div>
        </div>
    </Dialog>
</template>
