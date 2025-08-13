<script setup lang="ts">
const props = defineProps<{ title?: string, config: {
        cover_image_url?: string, issue_title?: string, issue_date?: string,
        summary_html?: string, link_url?: string, link_label?: string
    } }>()
</script>

<template>
    <article class="flex gap-4">
        <img v-if="props.config?.cover_image_url" :src="props.config.cover_image_url" class="w-28 h-36 object-cover rounded-lg" :alt="props.config?.issue_title ?? 'Newsletter cover'">
        <div class="min-w-0">
            <h2 class="text-xl font-semibold truncate">{{ props.title ?? 'Newsletter' }}</h2>
            <p class="text-sm opacity-70">{{ props.config?.issue_title }} <span v-if="props.config?.issue_date">· {{ props.config.issue_date }}</span></p>
            <div class="prose dark:prose-invert max-w-none mt-2" v-html="props.config?.summary_html ?? ''"/>
            <a v-if="props.config?.link_url" :href="props.config.link_url" class="inline-block mt-3 underline">
                {{ props.config?.link_label ?? 'Read issue' }}
            </a>
        </div>
    </article>
</template>

<style scoped>

</style>
