<!-- TileCTA.vue -->
<script setup lang="ts">
import TileCard from './TileCard.vue'

const props = defineProps<{
    title?: string | null; config: {
        description?: string;
        label: string;
        url: string;
        show_title?: boolean;
        show_badge?: boolean;
    }
}>()
</script>

<template>
    <TileCard :title="title || 'Get involved'" badge="cta" :showTitle="props.config?.show_title ?? true"
              :showBadge="props.config?.show_badge ?? false" :href="config.url" :fill="true">
        <p v-if="config.description" class="mb-3 text-zinc-700 dark:text-zinc-300">
            {{ config.description }}
        </p>
        <a :href="config.url"
           class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
            {{ config.label }}
        </a>
    </TileCard>
</template>
