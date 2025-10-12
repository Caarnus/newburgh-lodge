<!-- resources/js/Components/Tiles/TileNewsletter.vue -->
<script setup lang="ts">
import {ref, onMounted} from 'vue'
import TileCard from './TileCard.vue'

type Newsletter = {
    id: number|string
    title: string
    published_at: string
    excerpt?: string
    cover_image_url?: string|null
    url: string
}

type Config = {
    newsletter?: Newsletter | null
    newsletter_id?: number|string|null
    read_label?: string
    show_title?: boolean;
    show_badge?: boolean;
    cover_fit?: 'scale-down' | 'contain' | 'cover'
    object_position?: 'center' | 'top' | 'bottom' | 'left' | 'right'
}

const props     = defineProps<{ title?: string | null; config: Config }>()
const loading   = ref(false)
const item      = ref<Newsletter | null>(props.config.newsletter ?? null)
const fit       = props.config.cover_fit ?? 'scale-down'

onMounted(async () => {
    if (!item.value && props.config.newsletter_id) {
        loading.value = true
        try {
            const res = await fetch(`/api/newsletters/${props.config.newsletter_id}`)
            if (res.ok) item.value = await res.json()
        } finally {
            loading.value = false
        }
    }
})

function fmtDate(iso?: string) {
    if (!iso) return ''
    const d = new Date(iso)
    return new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'short', day: 'numeric' }).format(d)
}
</script>

<template>
    <TileCard
        :title="(props.config.show_title ?? true) ? (title || 'Compass Points') : null"
        :showTitle="props.config.show_title ?? true"
        :showBadge="false"
        :href="item?.url || null"
        :fill="true"
    >
        <template #default>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-[96px_1fr]">
                <!-- Cover image -->
                <div
                    v-if="item?.cover_image_url"
                    class="overflow-hidden rounded-lg bg-zinc-50 dark:bg-zinc-800/40 flex items-center justify-center h-40 md:h-24 md:w-24"
                >
                    <!-- No-crop modes (default): scale-down / contain -->
                    <img
                        v-if="fit !== 'cover'"
                        :src="item.cover_image_url"
                        :alt="item.title"
                        :class="[
              'max-h-full max-w-full',
              fit === 'contain' ? 'object-contain' : 'object-scale-down'
            ]"
                        loading="lazy"
                    />
                    <!-- Cover mode (crop to fill) -->
                    <img
                        v-else
                        :src="item.cover_image_url"
                        :alt="item.title"
                        class="h-full w-full object-cover"
                        :class="{
              'object-center': !props.config.object_position || props.config.object_position==='center',
              'object-top': props.config.object_position==='top',
              'object-bottom': props.config.object_position==='bottom',
              'object-left': props.config.object_position==='left',
              'object-right': props.config.object_position==='right',
            }"
                        loading="lazy"
                    />
                </div>

                <!-- Text block -->
                <div class="min-w-0">
                    <div class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                        {{ item?.title || (loading ? 'Loading…' : 'Latest newsletter') }}
                    </div>
                    <div class="mt-0.5 text-xs text-zinc-500">{{ fmtDate(item?.published_at) }}</div>
                    <p v-if="item?.excerpt" class="mt-2 line-clamp-3 text-sm">
                        {{ item.excerpt }}
                    </p>
                </div>
            </div>
        </template>

        <template #footer>
      <span class="inline-flex items-center gap-1">
        {{ props.config.read_label ?? 'Read issue' }}
        <i class="pi pi-arrow-right text-[0.9em]" />
      </span>
        </template>
    </TileCard>
</template>
