<!-- TileText.vue -->
<script setup lang="ts">
import TileCard from './TileCard.vue'

const props = defineProps<{
    title?: string | null; config: {
        html: string;
        show_title?: boolean;
        show_badge?: boolean;
    }
}>()
</script>

<template>
    <TileCard :title="title || 'Announcement'" badge="text" :showTitle="props.config?.show_title ?? true"
              :showBadge="props.config?.show_badge ?? false" :fill="true">
        <div class="prose prose-sm dark:prose-invert max-w-none" v-html="config.html"></div>
    </TileCard>
</template>
