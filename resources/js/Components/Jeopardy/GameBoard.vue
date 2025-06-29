<script setup lang="ts">
import { ref, computed } from 'vue'
import GameCard from './GameCard.vue'
import QuestionModal from './QuestionModal.vue'

const props = defineProps({
    board: {
        type: Object,
        required: true
    }
})

const activeQuestion = ref(null)

const openQuestion = (question) => {
    activeQuestion.value = question
}

// Extract category names
const categoryList = computed(() => Object.keys(props.board))

// For each category, sort questions by difficulty ascending
const sortedQuestionsByCategory = computed(() => {
    const result = {}

    categoryList.value.forEach(category => {
        const questionsObj = props.board[category]
        const questionsArray = Object.values(questionsObj || {})

        // Sort ascending by difficulty
        questionsArray.sort((a, b) => a.difficulty - b.difficulty)

        result[category] = questionsArray
    })

    return result
})

// Find the highest number of rows (questions per category)
const maxRowCount = computed(() => {
    return Math.max(
        ...categoryList.value.map(cat => {
            return Object.keys(props.board[cat]).length
        })
    )
})

// Get the question object at a given [category][row]
const getQuestion = (category, row) => {
    return props.board[category]?.[row-1] || null
}

// Flatten to grid cells, row by row
const gridCells = computed(() => {
    const cells = []

    for (let row = 0; row < maxRowCount.value; row++) {
        for (const category of categoryList.value) {
            const question = sortedQuestionsByCategory.value[category]?.[row] || null
            cells.push({ category, row, question })
        }
    }

    return cells
})
</script>

<template>
    <div class="p-4 w-full max-w-screen-xl mx-auto">
        <!-- Category Titles -->
        <div
            class="grid gap-2 mb-2"
            :style="`grid-template-columns: repeat(${categoryList.length}, minmax(0, 1fr))`"
        >
            <div
                v-for="category in categoryList"
                :key="category"
                class="text-center font-bold text-xl text-white bg-blue-900 p-2 rounded"
            >
                {{ category }}
            </div>
        </div>

        <!-- Questions Grid -->
        <div
            class="grid gap-2"
            :style="`grid-template-columns: repeat(${categoryList.length}, minmax(0, 1fr))`"
        >
            <GameCard
                v-for="({ category, row, question }) in gridCells"
                :key="`${category}-${row}`"
                :text="question ? '$' + (question.difficulty * 100) : ''"
                :clickable="!!question"
                @click="question && openQuestion(question)"
            />
        </div>

        <!-- Question Modal -->
        <QuestionModal
            v-if="activeQuestion"
            :question="activeQuestion"
            @close="activeQuestion = null"
        />
    </div>
</template>

<style scoped>

</style>
