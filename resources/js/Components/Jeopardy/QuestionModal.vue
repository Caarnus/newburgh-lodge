<script setup lang="ts">
const props = defineProps({
    question: Object
})

const emit = defineEmits(['close'])
const close = () => emit('close')
</script>

<template>
    <div
        class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50"
        @click.self="close"
    >
        <transition
            name="zoom"
            appear
        >
            <div class="bg-white p-8 rounded-lg shadow-2xl text-center max-w-2xl w-full">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">{{ question.category }}</h2>
                <p class="text-xl text-gray-700">{{ question.question }}</p>
                <button
                    class="mt-6 px-4 py-2 bg-blue-700 text-white rounded hover:bg-blue-800"
                    @click="close"
                >
                    Close
                </button>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.zoom-enter-active, .zoom-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}
.zoom-enter-from, .zoom-leave-to {
    transform: scale(0.8);
    opacity: 0;
}
</style>
