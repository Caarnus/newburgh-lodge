<script setup lang="ts">

const props = defineProps({
    text: String,
    clickable: { type: Boolean, default: false },
    clicked: {type: Boolean, default: false },
    limitHeight: {type: Boolean, default: true }
})

const emit = defineEmits(['click'])

const handleClick = () => {
    if (props.clickable && !props.clicked) emit('click')
}
</script>

<template>
    <div
        class="w-full h-full text-white text-2xl font-bold flex items-center justify-center text-center rounded shadow transition cursor-pointer"
        :class="{
            'h-full' : limitHeight,
            'bg-blue-950 hover:bg-blue-950' : props.clicked,
            'bg-blue-700 hover:bg-blue-800' : !props.clicked,
            'opacity-50 pointer-events-none': !clickable
        }"
        @click="handleClick"
    >
        {{ text }}
    </div>
</template>

<style scoped>

</style>
