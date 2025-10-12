<script setup lang="ts">
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
const props = defineProps<{ config: {
        newsletter_id?: number|string|null
        read_label?: string
        show_title?: boolean
        show_badge?: boolean
        cover_fit?: 'scale-down'|'contain'|'cover'
        object_position?: 'center'|'top'|'bottom'|'left'|'right'
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
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3">
            <label class="flex items-center gap-2">
                <span class="w-28">Newsletter ID</span>
                <InputText v-model="props.config.newsletter_id" class="flex-1" @update:modelValue="emit('update:config', props.config)" />
            </label>
            <label class="flex items-center gap-2">
                <span class="w-28">Read label</span>
                <InputText v-model="props.config.read_label" class="flex-1" placeholder="Read issue" @update:modelValue="emit('update:config', props.config)" />
            </label>
            <label class="flex items-center gap-2">
                <span class="w-28">Show title</span>
                <ToggleSwitch v-model="props.config.show_title" @update:modelValue="emit('update:config', props.config)" />
            </label>
            <label class="flex items-center gap-2">
                <span class="w-28">Show badge</span>
                <ToggleSwitch v-model="props.config.show_badge" @update:modelValue="emit('update:config', props.config)" />
            </label>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <label class="flex items-center gap-2">
                <span class="w-28">Image fit</span>
                <Select v-model="props.config.cover_fit" :options="fitOptions" optionLabel="label" optionValue="value" class="w-48" @update:modelValue="emit('update:config', props.config)" />
            </label>
            <label class="flex items-center gap-2" v-if="props.config.cover_fit === 'cover'">
                <span class="w-28">Focus</span>
                <Select v-model="props.config.object_position" :options="posOptions" optionLabel="label" optionValue="value" class="w-40" @update:modelValue="emit('update:config', props.config)" />
            </label>
        </div>
    </div>
</template>
