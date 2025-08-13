<script setup lang="ts">
import { onMounted, ref, watch, computed } from 'vue'
import {route} from "ziggy-js";

type EventItem = {
    id: number|string
    title: string
    start: string // ISO8601
    end?: string  // ISO8601
    url?: string
    category?: string|null
}

const props = defineProps<{
    title?: string
    config: {
        days_ahead?: number
        categories?: string[]
        limit?: number
        endpoint?: string
    }
}>()

const loading = ref(false)
const error = ref<string|null>(null)
const events = ref<EventItem[]>([])

const daysAhead = computed(() => props.config?.days_ahead ?? 30)
const categories = computed(() => props.config?.categories ?? [])
const limit = computed(() => props.config?.limit ?? 5)

function resolveEndpoint(): string {
    return route('api.events.fetch')
}

async function fetchEvents() {
    loading.value = true
    error.value = null
    try {
        const endpoint = resolveEndpoint()
        const params = new URLSearchParams()
        params.set('days_ahead', String(daysAhead.value))
        if (categories.value?.length) {
            for (const c of categories.value) params.append('categories[]', c)
        }
        params.set('limit', String(limit.value))
        const res = await fetch(`${endpoint}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const data = await res.json()
        events.value = Array.isArray(data) ? data : (data.data ?? [])
    } catch (e:any) {
        error.value = e?.message ?? 'Failed to load events'
    } finally {
        loading.value = false
    }
}

onMounted(fetchEvents)
watch([daysAhead, categories, limit], fetchEvents)
</script>

<template>
    <section>
        <h2 class="text-xl font-semibold mb-2">{{ title ?? 'Upcoming Events' }}</h2>

        <div v-if="loading" class="text-sm opacity-75">Loading…</div>
        <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>

        <ul v-else class="space-y-3">
            <li v-for="e in events" :key="e.id" class="flex items-start gap-3">
                <div class="shrink-0 text-xs px-2 py-1 rounded border">
                    {{ new Date(e.start).toLocaleDateString(undefined, { month: 'short', day: '2-digit' }) }}
                </div>
                <div class="min-w-0">
                    <div class="font-medium truncate">
                        <a v-if="e.url" :href="e.url" class="underline">{{ e.title }}</a>
                        <span v-else>{{ e.title }}</span>
                    </div>
                    <div class="text-xs opacity-75">
                        <template v-if="e.end">
                            {{ new Date(e.start).toLocaleTimeString(undefined, { hour: 'numeric', minute:'2-digit' }) }}
                            –
                            {{ new Date(e.end).toLocaleTimeString(undefined, { hour: 'numeric', minute:'2-digit' }) }}
                        </template>
                        <template v-else>
                            {{ new Date(e.start).toLocaleTimeString(undefined, { hour: 'numeric', minute:'2-digit' }) }}
                        </template>
                        <span v-if="e.category"> · {{ e.category }}</span>
                    </div>
                </div>
            </li>
        </ul>
    </section>
</template>

<style scoped>

</style>
