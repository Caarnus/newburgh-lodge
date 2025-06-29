<script setup>
import {onMounted, ref} from "vue";
import {route} from "ziggy-js";
import axios from "axios";
import GameBoard from "@/Components/Jeopardy/GameBoard.vue";

const board = ref({});
const isLoading = ref(true);
const error = ref(null);

const getNewBoard = async () => {
    isLoading.value = true
    error.value = null

    try {
        const response = await axios.get(route('jeopardy.board'))
        board.value = response.data.board
    } catch (err) {
        error.value = 'Failed to load game board.'
        console.error(err)
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    getNewBoard();
})
</script>

<template>
    <div class="min-h-screen bg-gray-900 text-white flex flex-col items-center justify-center">
        <h1 class="text-4xl font-bold mb-6">Masonic Jeopardy!</h1>

        <div v-if="isLoading" class="text-xl text-blue-300">Loading board...</div>
        <div v-else-if="error" class="text-red-500 text-lg">
            {{ error }}
            <button
                @click="getNewBoard"
                class="ml-4 px-3 py-1 bg-blue-700 rounded hover:bg-blue-800 transition"
            >
                Retry
            </button>
        </div>

        <GameBoard v-else :board="board" />
    </div>
</template>

<style scoped>

</style>
