<script setup>
const props = defineProps({
    status: { type: String, required: true },
    type: { type: String, default: 'task', validator: v => ['request', 'project', 'task', 'feature'].includes(v) },
});

const colorMap = {
    task: {
        open: 'bg-gray-100 text-gray-700',
        in_progress: 'bg-blue-100 text-blue-700',
        blocked: 'bg-red-100 text-red-700',
        done: 'bg-green-100 text-green-700',
    },
    request: {
        received: 'bg-gray-100 text-gray-700',
        under_review: 'bg-yellow-100 text-yellow-700',
        accepted: 'bg-green-100 text-green-700',
        deferred: 'bg-orange-100 text-orange-700',
        rejected: 'bg-red-100 text-red-700',
        completed: 'bg-emerald-100 text-emerald-700',
    },
    project: {
        active: 'bg-green-100 text-green-700',
        on_hold: 'bg-yellow-100 text-yellow-700',
        completed: 'bg-blue-100 text-blue-700',
    },
    feature: {
        proposed: 'bg-gray-100 text-gray-700',
        in_progress: 'bg-blue-100 text-blue-700',
        completed: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
    },
};

const label = props.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
const classes = colorMap[props.type]?.[props.status] || 'bg-gray-100 text-gray-700';
</script>

<template>
    <span
        :class="classes"
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
    >
        {{ label }}
    </span>
</template>
