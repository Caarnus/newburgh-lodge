<script setup lang="ts">
const props = defineProps<{ title?: string, config: {
        image_url?: string, alt?: string, text_html?: string, link_url?: string, link_label?: string
    } }>()
</script>

<template>
    <article class="flex flex-col md:flex-row gap-4">
        <img v-if="props.config?.image_url" :src="props.config.image_url" :alt="props.config?.alt ?? ''" class="w-full md:w-1/3 h-48 object-cover rounded-lg">
        <div class="min-w-0">
            <h2 v-if="title" class="text-xl font-semibold mb-2">{{ title }}</h2>
            <div class="prose dark:prose-invert max-w-none" v-html="props.config?.text_html ?? ''"/>
            <a v-if="props.config?.link_url" :href="props.config.link_url" class="inline-block mt-3 underline">
                {{ props.config?.link_label ?? 'Learn more' }}
            </a>
        </div>
    </article>
</template>

<style scoped>

</style>
