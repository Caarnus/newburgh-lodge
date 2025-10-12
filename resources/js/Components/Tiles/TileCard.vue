<script setup lang="ts">
defineProps<{
    as?: string              // 'article' | 'section' etc.
    title?: string | null
    href?: string | null     // makes the whole card clickable (optional)
    badge?: string | null
    fill?: boolean           // make body fill its tile span; use with ContentGrid :fill="true"
    showTitle?: boolean   // default true
    showBadge?: boolean   // default true
}>()
</script>

<template>
    <component
        :is="as || 'article'"
        class="group relative rounded-2xl border border-zinc-200/70 bg-white shadow-sm dark:border-zinc-800/60 dark:bg-zinc-900 overflow-hidden"
        :class="[{ 'h-full flex flex-col': fill }]"
    >
        <!-- header row -->
        <header
            v-if="(showTitle ?? true) || ((showBadge ?? true) && badge)"
            class="flex items-center justify-between gap-3 border-b border-zinc-100 px-3 py-2.5 text-sm dark:border-zinc-800"
        >
            <h3 v-if="showTitle ?? true" class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                <slot name="title">{{ title }}</slot>
            </h3>
            <span
                v-if="(showBadge ?? true) && badge"
                class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
            >
        {{ badge }}
      </span>
        </header>

        <!-- body -->
        <div class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300" :class="{ 'flex-1 min-h-0': fill }">
            <slot />
        </div>

        <!-- footer -->
        <footer v-if="$slots.footer" class="border-t border-zinc-100 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-800">
            <slot name="footer" />
        </footer>

        <!-- clickable overlay if href -->
        <a v-if="href" :href="href" class="absolute inset-0" aria-label="Open"></a>
    </component>
</template>
