<script setup lang="ts">
const props = defineProps({
    text: String,
    clickable: { type: Boolean, default: false }
})

const emit = defineEmits(['click'])

const handleClick = () => {
    if (props.clickable) emit('click')
}
</script>

<template>
    <div
        class="flex items-center justify-center text-white text-lg font-bold aspect-square rounded transition-transform duration-300 cursor-pointer bg-blue-700 hover:bg-blue-800"
        @click="handleClick"
    >
        {{ text }}
    </div>
</template>

<style scoped>

</style>
