<script setup lang="ts">
/* Placeholder: no unique options yet. */
const props = defineProps<{ config: Record<string,any> }>()
const emit = defineEmits<{ (e:'update:config', v:any):void }>()
</script>

<template>
    <div class="text-sm text-zinc-500">This tile has no unique settings yet.</div>
</template>
