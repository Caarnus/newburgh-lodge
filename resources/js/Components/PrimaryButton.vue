<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-surface-800 border border-transparent rounded-md font-semibold text-xs text-surface-0 uppercase tracking-widest hover:bg-surface-700 focus:bg-surface-700 active:bg-surface-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
