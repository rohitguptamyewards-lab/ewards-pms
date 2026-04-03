<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    color: { type: String, default: 'blue' },
    icon:  { type: String, default: null },
});

const palette = {
    blue:    { bg: 'bg-[#f5f0ff]',    border: 'border-[#ddd0f7]',  text: 'text-[#5e16bd]',    icon: 'text-[#5e16bd]/60' },
    green:   { bg: 'bg-green-50',   border: 'border-green-100',  text: 'text-green-700',   icon: 'text-green-400'   },
    red:     { bg: 'bg-red-50',     border: 'border-red-100',    text: 'text-red-700',     icon: 'text-red-400'     },
    yellow:  { bg: 'bg-yellow-50',  border: 'border-yellow-100', text: 'text-yellow-700',  icon: 'text-yellow-400'  },
    orange:  { bg: 'bg-orange-50',  border: 'border-orange-100', text: 'text-orange-700',  icon: 'text-orange-400'  },
    gray:    { bg: 'bg-gray-50',    border: 'border-gray-200',   text: 'text-gray-700',    icon: 'text-gray-400'    },
    emerald: { bg: 'bg-emerald-50', border: 'border-emerald-100',text: 'text-emerald-700', icon: 'text-emerald-400' },
    indigo:  { bg: 'bg-[#ece1ff]',  border: 'border-[#ddd0f7]',  text: 'text-[#5e16bd]',   icon: 'text-[#5e16bd]/60' },
    purple:  { bg: 'bg-purple-50',  border: 'border-purple-100', text: 'text-purple-700',  icon: 'text-purple-400'  },
};
</script>

<template>
    <div
        :class="[palette[color]?.bg || palette.blue.bg, palette[color]?.border || palette.blue.border]"
        class="rounded-xl border p-5 shadow-sm"
    >
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ label }}</p>
        <p :class="palette[color]?.text || palette.blue.text" class="mt-2 text-3xl font-bold tracking-tight">
            {{ value }}
        </p>
    </div>
</template>
