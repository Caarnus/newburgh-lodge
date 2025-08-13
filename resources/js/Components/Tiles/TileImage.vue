<script setup lang="ts">
const props = defineProps<{ title?: string, config: { image_url?: string, alt?: string, caption?: string, link_url?: string } }>()
</script>

<template>
    <figure>
        <a :href="props.config?.link_url || '#'" @click.prevent="!props.config?.link_url">
            <img :src="props.config?.image_url" :alt="props.config?.alt ?? ''" class="w-full h-64 object-cover rounded-xl">
        </a>
        <figcaption v-if="props.title || props.config?.caption" class="mt-2 text-sm opacity-75">
            <strong v-if="props.title">{{ props.title }}</strong>
            <span v-if="props.title && props.config?.caption"> — </span>
            <span v-if="props.config?.caption">{{ props.config.caption }}</span>
        </figcaption>
    </figure>
</template>

<style scoped>

</style>
