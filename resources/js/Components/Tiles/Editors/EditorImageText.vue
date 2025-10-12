<script setup lang="ts">
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
const props = defineProps<{ config: {
        image_url?: string; alt?: string; text_html?: string; link_url?: string; link_label?: string;
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
            <div class="col-span-2">
                <label class="block text-sm mb-1">Text HTML</label>
                <Textarea v-model="props.config.text_html" rows="4" @update:modelValue="emit('update:config', props.config)" class="w-full"/>
            </div>
            <label class="flex items-center gap-2"><span class="w-24">Link URL</span><InputText v-model="props.config.link_url" class="flex-1" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2"><span class="w-24">Link Label</span><InputText v-model="props.config.link_label" class="flex-1" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2"><span class="w-24">Show title</span><ToggleSwitch v-model="props.config.show_title" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2"><span class="w-24">Show badge</span><ToggleSwitch v-model="props.config.show_badge" @update:modelValue="emit('update:config', props.config)" /></label>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <label class="flex items-center gap-2"><span class="w-24">Image fit</span><Select v-model="props.config.fit" :options="fitOptions" optionLabel="label" optionValue="value" class="w-48" @update:modelValue="emit('update:config', props.config)" /></label>
            <label class="flex items-center gap-2" v-if="props.config.fit==='cover'"><span class="w-24">Focus</span><Select v-model="props.config.object_position" :options="posOptions" optionLabel="label" optionValue="value" class="w-40" @update:modelValue="emit('update:config', props.config)" /></label>
        </div>
    </div>
</template>
