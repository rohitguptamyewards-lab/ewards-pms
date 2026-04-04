<script setup>
import { computed } from 'vue';

const props = defineProps({
    percentage: { type: Number, required: true },
    showLabel:  { type: Boolean, default: true },
});

const clamped = computed(() => Math.min(100, Math.max(0, props.percentage)));

const barColor = computed(() => {
    if (clamped.value >= 70) return 'bg-emerald-500';
    if (clamped.value >= 40) return 'bg-[#f5f0ff]0';
    if (clamped.value >= 20) return 'bg-yellow-500';
    return 'bg-red-400';
});

const textColor = computed(() => {
    if (clamped.value >= 70) return 'text-emerald-600';
    if (clamped.value >= 40) return 'text-[#4e1a77]';
    if (clamped.value >= 20) return 'text-yellow-600';
    return 'text-red-500';
});
</script>

<template>
    <div class="flex items-center gap-3">
        <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
            <div
                :class="barColor"
                :style="{ width: clamped + '%' }"
                class="h-full rounded-full transition-all duration-500 ease-out"
            />
        </div>
        <span v-if="showLabel" :class="textColor" class="w-10 shrink-0 text-right text-xs font-semibold">
            {{ Math.round(clamped) }}%
        </span>
    </div>
</template>
