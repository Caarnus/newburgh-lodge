<script setup lang="ts">
import { ref, computed } from 'vue'
import GameCard from './GameCard.vue'
import QuestionModal from './QuestionModal.vue'

const props = defineProps({
    board: {
        type: Object,
        required: true
    },
    bonusQuestion: {
        type: Object,
        required: true
    }
})

const activeQuestion = ref(null)
const usedQuestions = ref([])
const done = ref(false);
const bonusPending = ref(false)

const openQuestion = (question) => {
    activeQuestion.value = question
    if (!questionClicked(question.id)) {
        usedQuestions.value.push(question.id);
    }
}

const questionClicked = (questionId) => {
    return usedQuestions.value.filter(x => x === questionId) > 0;
}

const closeQuestion = () => {
    if (activeQuestion.value.id === props.bonusQuestion.id) {
        done.value = true;
    }
    activeQuestion.value = null;

    // If all questions used and not done, trigger bonus
    const total = categoryList.value.length * maxRowCount.value
    if (!done.value && usedQuestions.value.length === total) {
        bonusPending.value = true
    }
}

const startBonusRound = () => {
    bonusPending.value = false
    activeQuestion.value = props.bonusQuestion
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
    <div class="flex flex-col p-4 w-full max-w-screen-xl mx-auto"
         :style="`height: calc(100vh - 4rem)`">
        <!-- Bonus Round Transition -->
        <transition name="fade">
            <div v-if="bonusPending" class="absolute inset-0 z-50 bg-black flex items-center justify-center">
                <GameCard
                    text="BONUS Question"
                    clickable
                    @click="startBonusRound"
                    class="w-full max-w-md h-52 animate-expand-slow text-yellow-400
                    bg-blue-600 hover:bg-blue-800 text-6xl rounded-3xl"
                />
            </div>
        </transition>

        <div v-if="!bonusPending && !done" class="flex-1 flex flex-col overflow-hidden">
            <!-- Category Titles -->
            <div
                class="grid gap-2 mb-2 h-16"
                :style="`grid-template-columns: repeat(${categoryList.length}, minmax(0, 1fr))`"
            >
                <div
                    v-for="category in categoryList"
                    :key="category"
                    class="flex items-center justify-center font-bold text-3xl text-white bg-blue-900 p-2 rounded"
                >
                    {{ category }}
                </div>
            </div>

            <!-- Questions Grid -->
            <div
                class="flex-1 grid gap-2"
                :style="`
                    display: grid;
                    grid-template-columns: repeat(${categoryList.length}, minmax(0, 1fr));
                    grid-template-rows: repeat(${maxRowCount}, minmax(0, 1fr));
                  `"
            >
                <GameCard
                    v-for="({ category, row, question }) in gridCells"
                    :key="`${category}-${row}`"
                    :text="!questionClicked(question.id) && question ? '$' + (question.difficulty * 100) : ''"
                    :clickable="!!question"
                    :clicked="questionClicked(question.id)"
                    @click="question && openQuestion(question)"
                />
            </div>
        </div>

        <!-- Question Modal -->
        <QuestionModal
            v-if="activeQuestion"
            :question="activeQuestion"
            @close="closeQuestion"
        />
    </div>
</template>

<style scoped>
    @keyframes expand-slow {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
    }

    .animate-expand-slow {
        animation: expand-slow 3s ease-in-out infinite;
    }
</style>
