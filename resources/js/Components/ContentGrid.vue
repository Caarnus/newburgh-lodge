<script setup lang="ts">
import { computed, defineProps } from 'vue'
import { tileRegistry } from '@/Components/Tiles/registry'

type Tile = {
    id: number | string
    type: string
    slug: string
    title?: string | null
    config?: any
    col_start: number
    row_start: number
    col_span: number
    row_span: number
}

const props = defineProps<{
    tiles: Tile[],
    cols?: number,
    gap?: string,
    rowHeightPx?: number,
    fill?: boolean,
    align?: 'stretch' | 'start' | 'center' | 'end',
}>()

const gridStyle = computed(() => ({
    display: 'grid',
    gridTemplateColumns: `repeat(${props.cols ?? 4}, minmax(0, 1fr))`,
    gap: props.gap ?? '1rem',
    gridAutoRows: `${props.rowHeightPx ?? 120}px`,
    alignItems: props.align ?? 'stretch',
}))

function toNumber(v: unknown, fallback: number) {
    const n = Number(v)
    return Number.isFinite(n) ? n : fallback
}

/** Placement belongs to the wrapper (so slots can’t break it) */
function tileStyle(tile: Partial<Tile>) {
    const cStart = toNumber((tile as any)?.col_start ?? (tile as any)?.colStart, 1)
    const cSpan  = Math.max(1, toNumber((tile as any)?.col_span  ?? (tile as any)?.colSpan, 1))
    const rStart = toNumber((tile as any)?.row_start ?? (tile as any)?.rowStart, 1)
    const rSpan  = Math.max(1, toNumber((tile as any)?.row_span  ?? (tile as any)?.rowSpan, 1))
    return {
        gridColumn: `${cStart} / span ${cSpan}`,
        gridRow: `${rStart} / span ${rSpan}`,
    } as Record<string, string>
}
</script>

<template>
    <div :style="gridStyle">
        <div
            v-for="tile in tiles"
            :key="tile.slug ?? tile.id"
            class="relative"
            :style="tileStyle(tile)"
        >
            <!-- Admin can override the body via slot; placement stays here -->
            <slot name="tile" :tile="tile">
                <!-- Default/public path via registry.
                     We *optionally* force full-height using a flex wrapper so we don’t require updates
                     inside each tile component. -->
                <div :class="[{ 'h-full flex': !!fill }]">
                    <component
                        :is="tileRegistry[tile.type]"
                        v-if="tileRegistry[tile.type]"
                        :class="[
              'rounded-2xl shadow p-4 bg-surface-0 dark:bg-surface-900',
              // If fill=true, make the component flex to the wrapper height
              { 'flex-1 min-h-0': !!fill }
            ]"
                        :title="tile.title ?? ''"
                        :config="tile.config ?? {}"
                    />
                    <div
                        v-else
                        :class="[
              'rounded-2xl border border-dashed p-4 text-sm text-surface-500',
              { 'flex-1 min-h-0': !!fill }
            ]"
                    >
                        Unknown tile type: <b>{{ tile.type }}</b>
                    </div>
                </div>
            </slot>
        </div>
    </div>
</template>

<style scoped>
/* nothing needed; Tailwind + inline styles cover it */
</style>
s
