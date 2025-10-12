<script setup lang="ts">
import TileCard from './TileCard.vue'

type ImgConfig = {
    image_url: string;
    alt?: string;
    url?: string;
    caption?: string;
    show_title?: boolean;
    show_badge?: boolean;
    fit?: 'scale-down' | 'contain' | 'cover'
    object_position?: 'center' | 'top' | 'bottom' | 'left' | 'right'
}

const props = defineProps<{ title?: string|null; config: ImgConfig }>()
const fit = props.config.fit ?? 'scale-down'
</script>

<template>
    <TileCard
        :title="(config.show_title ?? true) ? title : null"
        :showTitle="config.show_title ?? true"
        :showBadge="config.show_badge ?? false"
        :href="config.url || null"
        :fill="true"
    >
        <figure class="h-full min-h-0">
            <!-- COVER: fill tile height, crop (old behavior) -->
            <div v-if="fit==='cover'" class="relative h-full overflow-hidden rounded-xl">
                <img
                    :src="config.image_url"
                    :alt="config.alt || title || 'Image'"
                    class="absolute inset-0 h-full w-full object-cover"
                    :class="{
            'object-center': !config.object_position || config.object_position==='center',
            'object-top': config.object_position==='top',
            'object-bottom': config.object_position==='bottom',
            'object-left': config.object_position==='left',
            'object-right': config.object_position==='right',
          }"
                    loading="lazy"
                />
            </div>

            <!-- SCALE-DOWN / CONTAIN: no crop, no upscaling -->
            <div v-else class="h-full min-h-0 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 flex items-center justify-center overflow-hidden">
                <!-- Use natural aspect and restrict to tile box -->
                <img
                    :src="config.image_url"
                    :alt="config.alt || title || 'Image'"
                    :class="[
            'max-h-full max-w-full',
            fit==='contain' ? 'object-contain' : 'object-scale-down'
          ]"
                    loading="lazy"
                />
            </div>

            <figcaption v-if="config.caption" class="mt-2 text-xs text-zinc-500">
                {{ config.caption }}
            </figcaption>
        </figure>
    </TileCard>
</template>
