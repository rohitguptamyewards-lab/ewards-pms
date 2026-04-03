<script setup>
const props = defineProps({
    priority: { type: String, required: true },
});

const config = {
    p0: { classes: 'bg-red-100 text-red-700', label: 'P0 Critical' },
    p1: { classes: 'bg-orange-100 text-orange-700', label: 'P1 High' },
    p2: { classes: 'bg-yellow-100 text-yellow-700', label: 'P2 Medium' },
    p3: { classes: 'bg-gray-100 text-gray-700', label: 'P3 Low' },
};

const badge = config[props.priority] || config.p3;
</script>

<template>
    <span
        :class="badge.classes"
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    >
        {{ badge.label }}
    </span>
</template>
