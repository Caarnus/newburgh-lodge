<!-- TileLinks.vue -->
<script setup lang="ts">
import TileCard from './TileCard.vue'

const props = defineProps<{
    title?: string | null; config: {
        items: Array<{
            label: string, url: string;
        }>;
        show_title?: boolean;
        show_badge?: boolean;
    }
}>()
</script>

<template>
    <TileCard :title="title || 'Links'" :showTitle="props.config?.show_title ?? true"
              :showBadge="props.config?.show_badge ?? false" badge="links" :fill="true">
        <ul class="grid grid-cols-1 gap-2">
            <li v-for="(it, i) in config.items" :key="i">
                <a :href="it.url"
                   class="inline-flex max-w-full items-center gap-2 truncate text-blue-600 underline decoration-1 hover:no-underline">
                    <i class="pi pi-link text-[0.9em]"/>
                    <span class="truncate">{{ it.label }}</span>
                </a>
            </li>
        </ul>
    </TileCard>
</template>
