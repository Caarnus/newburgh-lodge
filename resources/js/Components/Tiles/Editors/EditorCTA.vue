<script setup lang="ts">
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
const props = defineProps<{ config: { label?:string; url?:string; description?:string } }>()
const emit = defineEmits<{ (e:'update:config', v:any):void }>()
</script>

<template>
    <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
            <label class="flex items-center gap-2"><span class="w-24">Button</span><InputText v-model="props.config.label" class="flex-1" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2"><span class="w-24">URL</span><InputText v-model="props.config.url" class="flex-1" @update:modelValue="emit('update:config', props.config)" /></label>
        </div>
        <div>
            <label class="block text-sm mb-1">Description</label>
            <Textarea class="w-full" v-model="props.config.description" rows="3" @update:modelValue="emit('update:config', props.config)" />
        </div>
    </div>
</template>
