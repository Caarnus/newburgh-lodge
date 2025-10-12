<script setup lang="ts">
import { ref, computed } from 'vue'
import GameCard from './GameCard.vue'
import QuestionModal from './QuestionModal.vue'

const props = defineProps({
    board: {
        type: Object,
        required: true
    },
    // Array of bonus questions coming from the API. We dedupe by subcategory and show one button per subcategory.
    bonusQuestions: {
        type: Array as () => any[],
        required: false,
        default: () => []
    }
})

const activeQuestion = ref<any | null>(null)
const usedQuestions = ref<Array<string | number>>([])
const done = ref(false)
const bonusPending = ref(false)

// Ensure the modal visibility is an explicit boolean
const hasActiveQuestion = computed(() => activeQuestion.value !== null)

const openQuestion = (question: any) => {
    activeQuestion.value = question
    if (question && !questionClicked(question.id)) {
        usedQuestions.value.push(question.id)
    }
}

const questionClicked = (questionId: any) => {
    return usedQuestions.value.includes(questionId)
}

const closeQuestion = () => {
    if (activeQuestion.value) {
        const bq = Array.isArray(props.bonusQuestions) ? props.bonusQuestions : []
        const isBonus = bq.some(b => b && activeQuestion.value && b.id === activeQuestion.value.id)
        if (isBonus) {
            done.value = true
        }
    }
    activeQuestion.value = null

    // If all non-bonus questions are used and we're not done, trigger the bonus selection
    if (!done.value && usedQuestions.value.length === totalQuestions.value) {
        bonusPending.value = true
    }
}

const resetQuestion = (question: any) => {
    if (!question) return
    usedQuestions.value = usedQuestions.value.filter(id => id !== question.id)

    // If we had scheduled bonus but not all questions are used anymore, cancel pending
    if (bonusPending.value && usedQuestions.value.length < totalQuestions.value) {
        bonusPending.value = false
    }
    // If a finished bonus was "reset" (edge case), allow continuing
    const bq = Array.isArray(props.bonusQuestions) ? props.bonusQuestions : []
    if (done.value && bq.some(b => b.id === question.id)) {
        done.value = false
    }
}

// Kick off bonus selection when appropriate (e.g., after last normal question)
const startBonusSelection = () => {
    bonusPending.value = true
}

// Subcategory labeling (fallback to 'Bonus' if empty/null)
const subcategoryLabel = (q: any) => (q?.subcategory && String(q.subcategory).trim()) || 'Bonus'

// Build a map of first question per subcategory label
const bonusBySubcategory = computed<Record<string, any>>(() => {
    const map: Record<string, any> = {}
    ;(Array.isArray(props.bonusQuestions) ? props.bonusQuestions : []).forEach(q => {
        const label = subcategoryLabel(q)
        if (!map[label]) map[label] = q
    })
    return map
})

const bonusSubcategories = computed<string[]>(() => Object.keys(bonusBySubcategory.value))

// Clicking a subcategory button immediately starts that bonus question
const pickBonusSubcategoryAndStart = (label: string) => {
    const q = bonusBySubcategory.value[label]
    if (!q) return
    bonusPending.value = false
    openQuestion(q)
}

// Extract category names from the board
const categoryList = computed(() => Object.keys(props.board || {}))

// For each category, sort questions by difficulty ascending
const sortedQuestionsByCategory = computed(() => {
    const result: Record<string, any[]> = {}

    categoryList.value.forEach(category => {
        const questionsObj = (props.board as any)[category]
        const questionsArray = Object.values(questionsObj || {})

        // Sort ascending by difficulty (normal questions have difficulty)
        questionsArray.sort((a: any, b: any) => (a?.difficulty ?? 0) - (b?.difficulty ?? 0))

        result[category] = questionsArray as any[]
    })

    return result
})

// Find the highest number of rows (questions per category)
const maxRowCount = computed(() => {
    const counts = categoryList.value.map(cat => Object.keys((props.board as any)[cat] || {}).length)
    return counts.length ? Math.max(...counts) : 0
})

// Total number of real (non-bonus) questions (handles uneven categories)
const totalQuestions = computed(() => {
    return categoryList.value.reduce((sum, cat) => {
        return sum + Object.keys((props.board as any)[cat] || {}).length
    }, 0)
})

// Flatten to grid cells, row by row
const gridCells = computed(() => {
    const cells: Array<{ category: string; row: number; question: any | null }> = []

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
    <div class="relative flex flex-col p-4 w-full max-w-screen-xl mx-auto"
         :style="`height: calc(100vh - 4rem)`">
        <!-- Bonus Selection Overlay -->
        <transition name="fade">
            <div v-if="bonusPending" class="absolute inset-0 z-50 bg-surface-950/95 flex items-center justify-center p-4">
                <div class="w-full max-w-3xl bg-blue-900 text-surface-0 rounded-2xl p-6 shadow-2xl">
                    <div class="text-3xl font-bold mb-4 text-center">Select Bonus Question</div>

                    <!-- Subcategory buttons (3-column grid), label only -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                        <button
                            v-for="label in bonusSubcategories"
                            :key="label"
                            type="button"
                            class="px-4 py-3 rounded font-semibold transition
                                   bg-blue-800 hover:bg-blue-700 border-2 border-transparent"
                            @click="pickBonusSubcategoryAndStart(label)"
                        >
                            {{ label }}
                        </button>
                    </div>

                    <div class="flex justify-end">
                        <button
                            class="px-4 py-2 rounded bg-surface-700 hover:bg-surface-600"
                            @click="bonusPending = false"
                            type="button"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
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
                    class="flex items-center justify-center font-bold text-3xl text-surface-0 bg-blue-900 p-2 rounded"
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
                    :text="question ? ('$' + (question.difficulty * 100)) : ''"
                    :clickable="!!question"
                    :clicked="question ? questionClicked(question.id) : false"
                    @click="question && openQuestion(question)"
                    @reset="question && resetQuestion(question)"
                />
            </div>
        </div>

        <!-- Question Modal -->
        <QuestionModal
            v-if="hasActiveQuestion"
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
