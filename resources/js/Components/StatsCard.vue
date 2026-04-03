<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    color: { type: String, default: 'blue' },
});

const bgMap = {
    blue: 'bg-blue-50 border-blue-200',
    green: 'bg-green-50 border-green-200',
    red: 'bg-red-50 border-red-200',
    yellow: 'bg-yellow-50 border-yellow-200',
    orange: 'bg-orange-50 border-orange-200',
    gray: 'bg-gray-50 border-gray-200',
    emerald: 'bg-emerald-50 border-emerald-200',
    indigo: 'bg-indigo-50 border-indigo-200',
};

const textMap = {
    blue: 'text-blue-700',
    green: 'text-green-700',
    red: 'text-red-700',
    yellow: 'text-yellow-700',
    orange: 'text-orange-700',
    gray: 'text-gray-700',
    emerald: 'text-emerald-700',
    indigo: 'text-indigo-700',
};
</script>

<template>
    <div :class="bgMap[color] || bgMap.blue" class="rounded-lg border p-5">
        <p class="text-sm font-medium text-gray-500">{{ label }}</p>
        <p :class="textMap[color] || textMap.blue" class="mt-1 text-3xl font-bold">
            {{ value }}
        </p>
    </div>
</template>
