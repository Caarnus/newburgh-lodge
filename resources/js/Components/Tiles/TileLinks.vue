<script setup lang="ts">
const props = defineProps<{ title?: string, config: { items?: {label:string, url:string}[] } }>()
</script>

<template>
    <nav>
        <h2 v-if="title" class="text-xl font-semibold mb-3">{{ title }}</h2>
        <ul class="space-y-2">
            <li v-for="(link, id) in (props.config?.items ?? [])" :key="id">
                <a :href="link.url" class="underline">{{ link.label }}</a>
            </li>
        </ul>
    </nav>
</template>

<style scoped>

</style>
