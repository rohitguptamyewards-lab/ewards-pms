<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    type:   { type: String, default: 'task', validator: v => ['request', 'project', 'task', 'feature'].includes(v) },
});

const colorMap = {
    task: {
        open:        'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
        in_progress: 'bg-[#ece1ff]  text-[#5e16bd]  ring-1 ring-blue-200',
        blocked:     'bg-red-100   text-red-700   ring-1 ring-red-200',
        done:        'bg-green-100 text-green-700 ring-1 ring-green-200',
    },
    request: {
        received:     'bg-slate-100  text-slate-700  ring-1 ring-slate-200',
        under_review: 'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-200',
        accepted:     'bg-green-100  text-green-700  ring-1 ring-green-200',
        deferred:     'bg-orange-100 text-orange-700 ring-1 ring-orange-200',
        rejected:     'bg-red-100    text-red-700    ring-1 ring-red-200',
        completed:    'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
    },
    project: {
        active:    'bg-green-100  text-green-700  ring-1 ring-green-200',
        on_hold:   'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-200',
        completed: 'bg-[#ece1ff]   text-[#5e16bd]   ring-1 ring-blue-200',
    },
    feature: {
        backlog:           'bg-slate-100   text-slate-700   ring-1 ring-slate-200',
        in_progress:       'bg-[#ece1ff]    text-[#5e16bd]    ring-1 ring-blue-200',
        in_review:         'bg-purple-100  text-purple-700  ring-1 ring-purple-200',
        ready_for_qa:      'bg-yellow-100  text-yellow-700  ring-1 ring-yellow-200',
        in_qa:             'bg-orange-100  text-orange-700  ring-1 ring-orange-200',
        ready_for_release: 'bg-teal-100    text-teal-700    ring-1 ring-teal-200',
        released:          'bg-green-100   text-green-700   ring-1 ring-green-200',
        deferred:          'bg-orange-100  text-orange-700  ring-1 ring-orange-200',
        rejected:          'bg-red-100     text-red-700     ring-1 ring-red-200',
        proposed:          'bg-slate-100   text-slate-700   ring-1 ring-slate-200',
        completed:         'bg-green-100   text-green-700   ring-1 ring-green-200',
    },
};

const dotMap = {
    open:              'bg-slate-400',
    in_progress:       'bg-[#f5f0ff]0',
    blocked:           'bg-red-500',
    done:              'bg-green-500',
    received:          'bg-slate-400',
    under_review:      'bg-yellow-500',
    accepted:          'bg-green-500',
    deferred:          'bg-orange-500',
    rejected:          'bg-red-500',
    completed:         'bg-emerald-500',
    active:            'bg-green-500',
    on_hold:           'bg-yellow-500',
    backlog:           'bg-slate-400',
    in_review:         'bg-purple-500',
    ready_for_qa:      'bg-yellow-500',
    in_qa:             'bg-orange-500',
    ready_for_release: 'bg-teal-500',
    released:          'bg-green-500',
    proposed:          'bg-slate-400',
};

const label   = computed(() => props.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()));
const classes = computed(() => colorMap[props.type]?.[props.status] || 'bg-gray-100 text-gray-600 ring-1 ring-gray-200');
const dot     = computed(() => dotMap[props.status] || 'bg-gray-400');
</script>

<template>
    <span :class="classes" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium">
        <span :class="dot" class="h-1.5 w-1.5 rounded-full flex-shrink-0" />
        {{ label }}
    </span>
</template>
