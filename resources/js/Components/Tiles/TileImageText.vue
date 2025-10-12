<script setup lang="ts">
import TileCard from './TileCard.vue'

type ImgTextConfig = {
    image_url: string
    alt?: string
    text_html?: string
    link_url?: string
    link_label?: string
    show_title?: boolean
    show_badge?: boolean;
    fit?: 'scale-down' | 'contain' | 'cover'
    object_position?: 'center'|'top'|'bottom'|'left'|'right'
}

const props = defineProps<{ title?: string|null; config: ImgTextConfig }>()
const fit = props.config.fit ?? 'scale-down'
</script>

<template>
    <TileCard
        :title="(config.show_title ?? true) ? title : null"
        :showTitle="config.show_title ?? true"
        :showBadge="config.show_badge ?? false"
        :href="config.link_url || null"
        :fill="true"
    >
        <div class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,38%)_1fr] md:h-full md:min-h-0">
            <!-- IMAGE COLUMN -->
            <div class="overflow-hidden rounded-lg md:h-full md:min-h-0">
                <!-- COVER: fills, may crop -->
                <div v-if="fit==='cover'" class="relative w-full h-auto md:h-full">
                    <img
                        :src="config.image_url"
                        :alt="config.alt || title || 'Image'"
                        class="absolute md:static inset-0 h-full w-full object-cover"
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
                <div v-else class="h-full min-h-0 bg-zinc-50 dark:bg-zinc-800/40 flex items-center justify-center">
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
            </div>

            <!-- TEXT COLUMN -->
            <div class="min-w-0 md:h-full md:min-h-0 md:flex md:flex-col">
                <div class="prose prose-sm dark:prose-invert max-w-none md:flex-1 md:min-h-0" v-html="config.text_html"></div>
                <div v-if="config.link_url" class="mt-2 text-sm text-blue-600 underline decoration-1">
                    {{ config.link_label || 'Learn more' }}
                </div>
            </div>
        </div>
    </TileCard>
</template>
