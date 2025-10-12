<script setup lang="ts">
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
const props = defineProps<{ config: {
        image_url?: string; alt?: string; url?: string; caption?: string;
        show_title?: boolean; show_badge?: boolean; fit?: 'scale-down'|'contain'|'cover'; object_position?: 'center'|'top'|'bottom'|'left'|'right'
    } }>()
const emit = defineEmits<{ (e:'update:config', v:any):void }>()
const fitOptions = [
    {label:'Scale down (no crop)', value:'scale-down'},
    {label:'Contain (no crop)', value:'contain'},
    {label:'Cover (crop to fill)', value:'cover'},
]
const posOptions = [
    {label:'Center', value:'center'},
    {label:'Top', value:'top'},
    {label:'Bottom', value:'bottom'},
    {label:'Left', value:'left'},
    {label:'Right', value:'right'},
]
</script>

<template>
    <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
            <label class="flex items-center gap-2"><span class="w-24">Image URL</span><InputText v-model="props.config.image_url" class="flex-1" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2"><span class="w-24">Alt</span><InputText v-model="props.config.alt" class="flex-1" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2"><span class="w-24">Link</span><InputText v-model="props.config.url" class="flex-1" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2"><span class="w-24">Caption</span><InputText v-model="props.config.caption" class="flex-1" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2"><span class="w-24">Show title</span><ToggleSwitch v-model="props.config.show_title" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2"><span class="w-24">Show badge</span><ToggleSwitch v-model="props.config.show_badge" @update:modelValue="emit('update:config', props.config)" /></label>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <label class="flex items-center gap-2 "><span class="w-24">Image fit</span><Select v-model="props.config.fit" :options="fitOptions" optionLabel="label" optionValue="value" class="w-full" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2" v-if="props.config.fit==='cover'"><span class="w-24">Focus</span><Select v-model="props.config.object_position" :options="posOptions" optionLabel="label" optionValue="value" class="w-40" @update:modelValue="emit('update:config', props.config)" /></label>
        </div>
    </div>
</template>
