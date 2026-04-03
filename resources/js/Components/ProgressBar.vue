<script setup>
import { computed } from 'vue';

const props = defineProps({
    percentage: { type: Number, required: true },
    showLabel: { type: Boolean, default: true },
});

const clamped = computed(() => Math.min(100, Math.max(0, props.percentage)));

const barColor = computed(() => {
    if (clamped.value > 60) return 'bg-green-500';
    if (clamped.value >= 30) return 'bg-yellow-500';
    return 'bg-red-500';
});
</script>

<template>
    <div class="flex items-center gap-2">
        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-gray-200">
            <div
                :class="barColor"
                :style="{ width: clamped + '%' }"
                class="h-full rounded-full transition-all duration-300"
            />
        </div>
        <span v-if="showLabel" class="min-w-[3rem] text-right text-sm font-medium text-gray-600">
            {{ Math.round(clamped) }}%
        </span>
    </div>
</template>
