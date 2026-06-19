<script setup lang="ts">
import { useTimeMode } from '@/composables/useTimeMode';

const { mode, toggle } = useTimeMode();
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-1 rounded-full border bg-background p-0.5 text-xs font-medium"
        :aria-label="`Showing ${mode === 'venue' ? 'venue' : 'your'} local time`"
        @click="toggle"
    >
        <span
            class="rounded-full px-2.5 py-1 transition-colors"
            :class="
                mode === 'venue'
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground'
            "
        >
            Venue time
        </span>
        <span
            class="rounded-full px-2.5 py-1 transition-colors"
            :class="
                mode === 'local'
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground'
            "
        >
            My time
        </span>
    </button>
</template>
