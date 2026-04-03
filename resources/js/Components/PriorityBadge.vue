<script setup>
import { computed } from 'vue';

const props = defineProps({
    priority: { type: String, required: true },
});

const config = {
    p0: { classes: 'bg-red-100    text-red-700    ring-1 ring-red-200',    dot: 'bg-red-500',    label: 'P0 · Critical' },
    p1: { classes: 'bg-orange-100 text-orange-700 ring-1 ring-orange-200', dot: 'bg-orange-500', label: 'P1 · High' },
    p2: { classes: 'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-200', dot: 'bg-yellow-500', label: 'P2 · Medium' },
    p3: { classes: 'bg-slate-100  text-slate-600  ring-1 ring-slate-200',  dot: 'bg-slate-400',  label: 'P3 · Low' },
};

const badge = computed(() => config[props.priority] || config.p3);
</script>

<template>
    <span :class="badge.classes" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium">
        <span :class="badge.dot" class="h-1.5 w-1.5 rounded-full flex-shrink-0" />
        {{ badge.label }}
    </span>
</template>
