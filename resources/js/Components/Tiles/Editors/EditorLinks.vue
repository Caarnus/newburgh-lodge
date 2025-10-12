<script setup lang="ts">
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
const props = defineProps<{ config: { items?: Array<{label:string,url:string}> } }>()
const emit = defineEmits<{ (e:'update:config', v:any):void }>()
function add(){ props.config.items ??= []; props.config.items.push({label:'',url:''}); emit('update:config', props.config) }
function del(i:number){ props.config.items!.splice(i,1); emit('update:config', props.config) }
</script>

<template>
    <div class="space-y-3">
        <div class="flex justify-between items-center">
            <div class="text-sm font-medium">Links</div>
            <Button label="Add" size="small" @click="add" />
        </div>
        <div v-if="props.config.items?.length" class="space-y-2">
            <div v-for="(it, i) in props.config.items" :key="i" class="grid grid-cols-12 gap-2">
                <InputText v-model="it.label" class="col-span-5" placeholder="Label" @update:modelValue="emit('update:config', props.config)" />
                <InputText v-model="it.url" class="col-span-6" placeholder="https://…" @update:modelValue="emit('update:config', props.config)" />
                <Button icon="pi pi-trash" text severity="danger" class="col-span-1" @click="del(i)" />
            </div>
        </div>
        <div v-else class="text-sm text-zinc-500">No links yet.</div>
    </div>
</template>
