<script setup lang="ts">

const props = defineProps({
    text: String,
    clickable: { type: Boolean, default: false },
    clicked: {type: Boolean, default: false }
})

const emit = defineEmits(['click'])

const handleClick = () => {
    if (props.clickable && !props.clicked) emit('click')
}
</script>

<template>
    <div
        class="flex items-center justify-center text-white text-lg font-bold rounded py-10
        transition-transform duration-300 cursor-pointer"
        :class="props.clicked ? 'bg-blue-950 hover:bg-blue-900' : 'bg-blue-700 hover:bg-blue-800'"
        @click="handleClick"
    >
        {{ text }}
    </div>
</template>

<style scoped>

</style>
