<script setup lang="ts">
import {usePage} from "@inertiajs/vue3";
import ContentGrid from "@/Components/ContentGrid.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

const page = usePage()
const tiles = page.props.tiles as any[]
const grid = page.props.grid as { cols:number, gap:string }
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">
                Home
            </h2>
        </template>

        <ContentGrid :tiles="tiles" :cols="grid?.cols" :gap="grid?.gap" class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-surface-900 dark:text-surface-100"/>
    </AppLayout>
</template>

<style scoped>

</style>
