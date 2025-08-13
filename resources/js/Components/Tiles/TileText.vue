<script setup lang="ts">
const props = defineProps<{ title?: string, config: { html?: string, align?: 'left' | 'center' | 'right' } }>()
</script>

<template>
    <article>
        <h2 v-if="title" class="text-xl font-semibold mb-2">{{ title }}</h2>
        <div class="prose dark:prose-invert max-w-none"
             :class="{
                'text-left': (props.config?.align ?? 'left') === 'left',
                'text-center': props.config?.align === 'center',
                'text-right': props.config?.align === 'right',
             }"
             v-html="props.config?.html ?? ''"/>
    </article>
</template>

<style scoped>

</style>
