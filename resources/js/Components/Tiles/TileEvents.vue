<!-- TileEvents.vue -->
<script setup lang="ts">
import TileCard from './TileCard.vue'

const props = defineProps<{
    title?: string | null;
    config: {
        events: Array<{ id: number | string, title: string, start: string, location?: string, url: string }>;
        view_all_url?: string;
        show_title?: boolean;
        show_badge?: boolean;
    }
}>()

function fmtDate(iso: string) {
    const d = new Date(iso);
    return new Intl.DateTimeFormat(undefined, {month: 'short', day: 'numeric'}).format(d)
}

function fmtTime(iso: string) {
    const d = new Date(iso);
    return new Intl.DateTimeFormat(undefined, {hour: 'numeric', minute: '2-digit'}).format(d)
}
</script>

<template>
    <TileCard :title="title || 'Upcoming events'" badge="events" :showTitle="props.config?.show_title ?? true"
              :showBadge="props.config?.show_badge ?? false" :fill="true">
        <ul class="space-y-2">
            <li v-for="ev in config.events" :key="ev.id" class="flex items-start gap-3">
                <div
                    class="mt-0.5 flex flex-col items-center rounded-lg bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <span>{{ fmtDate(ev.start) }}</span>
                    <span class="font-normal text-[11px] opacity-75">{{ fmtTime(ev.start) }}</span>
                </div>
                <div class="min-w-0">
                    <a :href="ev.url"
                       class="block truncate font-medium text-zinc-900 hover:underline dark:text-zinc-100">{{
                            ev.title
                        }}</a>
                    <div v-if="ev.location" class="text-xs text-zinc-500">{{ ev.location }}</div>
                </div>
            </li>
        </ul>
        <template #footer v-if="config.view_all_url">
            <a :href="config.view_all_url" class="inline-flex items-center gap-1">
                View all events <i class="pi pi-arrow-right text-[0.9em]"/>
            </a>
        </template>
    </TileCard>
</template>
