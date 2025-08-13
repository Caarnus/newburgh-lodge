<script setup lang="ts">
const props = defineProps<{ title?: string, config: { label?:string, url?:string, description?:string } }>()
</script>

<template>
    <div class="text-center py-8">
        <h2 v-if="title" class="text-2xl font-semibold mb-2">{{ title }}</h2>
        <p v-if="props.config?.description" class="opacity-80 mb-4">{{ props.config.description }}</p>
        <a v-if="props.config?.url" :href="props.config.url" class="px-5 py-2 rounded-xl shadow border inline-block">
            {{ props.config?.label ?? 'Learn more' }}
        </a>
    </div>
</template>

<style scoped>

</style>
