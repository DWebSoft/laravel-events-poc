<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import TimeToggle from '@/components/events/TimeToggle.vue';
import { Button } from '@/components/ui/button';
import type { CityOption, EventFilterState } from '@/types';

// Two-way bound shared filter state (writable model, so updating fields here
// stays in sync with the parent without mutating a read-only prop).
const filters = defineModel<EventFilterState>('filters', { required: true });

defineProps<{
    cities: CityOption[];
    types: string[];
    statuses: string[];
}>();

const emit = defineEmits<{ change: []; reset: [] }>();

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring';
</script>

<template>
    <div class="rounded-xl border bg-card p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex min-w-44 flex-1 flex-col gap-1">
                <label
                    class="text-xs font-medium text-muted-foreground"
                    for="city"
                    >Location</label
                >
                <select
                    id="city"
                    v-model="filters.city"
                    :class="selectClass"
                    @change="emit('change')"
                >
                    <option :value="null">All locations</option>
                    <option
                        v-for="city in cities"
                        :key="city.label"
                        :value="city.label"
                    >
                        {{ city.label }}
                    </option>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label
                    class="text-xs font-medium text-muted-foreground"
                    for="from"
                    >From</label
                >
                <input
                    id="from"
                    v-model="filters.from"
                    type="date"
                    :class="selectClass"
                    @change="emit('change')"
                />
            </div>

            <div class="flex flex-col gap-1">
                <label
                    class="text-xs font-medium text-muted-foreground"
                    for="to"
                    >To</label
                >
                <input
                    id="to"
                    v-model="filters.to"
                    type="date"
                    :class="selectClass"
                    @change="emit('change')"
                />
            </div>

            <div class="flex flex-col gap-1">
                <label
                    class="text-xs font-medium text-muted-foreground"
                    for="type"
                    >Category</label
                >
                <select
                    id="type"
                    v-model="filters.type"
                    :class="selectClass"
                    @change="emit('change')"
                >
                    <option :value="null">All categories</option>
                    <option
                        v-for="type in types"
                        :key="type"
                        :value="type"
                        class="capitalize"
                    >
                        {{ type }}
                    </option>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label
                    class="text-xs font-medium text-muted-foreground"
                    for="status"
                    >Status</label
                >
                <select
                    id="status"
                    v-model="filters.status"
                    :class="selectClass"
                    @change="emit('change')"
                >
                    <option value="all">All statuses</option>
                    <option
                        v-for="status in statuses"
                        :key="status"
                        :value="status"
                    >
                        {{ status.replace('_', ' ') }}
                    </option>
                </select>
            </div>

            <Button
                variant="ghost"
                size="sm"
                class="h-9"
                @click="emit('reset')"
            >
                <X class="size-4" /> Reset
            </Button>
        </div>

        <div class="mt-3 flex items-center justify-between border-t pt-3">
            <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <Search class="size-3.5" /> Filter by location and date
            </p>
            <TimeToggle />
        </div>
    </div>
</template>
