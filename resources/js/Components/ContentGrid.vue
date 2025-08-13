<script setup lang="ts">
import { computed, defineProps } from 'vue'
import {tileRegistry} from '@/Components/Tiles/registry'

type Tile = {
    id: number
    type: string
    slug: string
    title?: string|null
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
}>();

const gridStyle = computed(() => ({
    display: 'grid',
    gridTemplateColumns: `repeat(${props.cols ?? 4}, minmax(0, 1fr))`,
    gap: props.gap ?? '1rem',
}));
</script>

<template>
    <div :style="gridStyle">
        <template v-for="tile in tiles" :key="tile.slug">
            <component
                :is="tileRegistry[tile.type]"
                v-if="tileRegistry[tile.type]"
                class="rounded-2xl shadow p-4 bg-surface-0 dark:bg-surface-900"
                :style="{
                    gridColumn: `${tile.col_start} / span ${tile.col_span}`,
                    gridRow: `${tile.row_start} / span ${tile.row_span}`,
                }"
                :title="tile.title ?? ''"
                :config="tile.config ?? {}"
            />
            <div
                v-else
                class="rounded-2xl border-dashed border p-4 text-sm text-surface-500"
                :style="{
                    gridColumn: `${tile.col_start} / span ${tile.col_span}`,
                    gridRow: `${tile.row_start} / span ${tile.row_span}`,
                }"
            >
                Unknown tile type: <b>{{ tile.type }}</b>
            </div>
        </template>
    </div>
</template>

<style scoped>

</style>
