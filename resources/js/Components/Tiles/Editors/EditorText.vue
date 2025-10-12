<script setup lang="ts">
import Textarea from 'primevue/textarea'
const props = defineProps<{ config: { html?: string } }>()
const emit = defineEmits<{ (e:'update:config', v:any):void }>()
</script>

<template>
    <div class="space-y-2">
        <label class="block text-sm font-medium">HTML</label>
        <Textarea class="w-full" v-model="props.config.html" rows="8" @update:modelValue="emit('update:config', props.config)" />
    </div>
</template>
