<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    items:   { type: Array, default: () => [] },
    tools:   { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const capFilter  = ref(props.filters.capability || '');
const toolFilter = ref(props.filters.ai_tool_id || '');

function applyFilter() {
    router.get('/ai-knowledge-base', { capability: capFilter.value, ai_tool_id: toolFilter.value }, { preserveState: true, replace: true });
}

const outcomeConfig = {
    helpful:    { label: 'Helpful', bg: 'bg-emerald-100 text-emerald-700' },
    partially:  { label: 'Partially Helpful', bg: 'bg-yellow-100 text-yellow-700' },
    misleading: { label: 'Misleading', bg: 'bg-red-100 text-red-700' },
    not_useful: { label: 'Not Useful', bg: 'bg-gray-100 text-gray-600' },
};

const capLabels = { code: 'Code', debug: 'Debug', architecture: 'Architecture', content: 'Content', research: 'Research' };
</script>

<template>
    <Head title="AI Knowledge Base" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">AI Knowledge Base</h1>
            <p class="mt-0.5 text-sm text-gray-500">Aggregated tips and anti-patterns from team AI usage</p>
        </div>

        <div class="flex gap-3 mb-4">
            <select v-model="capFilter" @change="applyFilter"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Capabilities</option>
                <option v-for="(label, val) in capLabels" :key="val" :value="val">{{ label }}</option>
            </select>
            <select v-model="toolFilter" @change="applyFilter"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Tools</option>
                <option v-for="t in tools" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
        </div>

        <div class="space-y-3">
            <div v-for="(item, idx) in items" :key="idx"
                 class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-[#5e16bd]/10 px-2.5 py-0.5 text-xs font-medium text-[#5e16bd]">
                            {{ capLabels[item.capability] || item.capability }}
                        </span>
                        <span :class="outcomeConfig[item.outcome]?.bg || 'bg-gray-100'"
                              class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                            {{ outcomeConfig[item.outcome]?.label || item.outcome }}
                        </span>
                    </div>
                    <span class="text-xs text-gray-400">{{ item.tool_name }}</span>
                </div>
                <p class="text-sm text-gray-700">{{ item.note }}</p>
                <p class="text-xs text-gray-400 mt-2">— {{ item.member_name }}</p>
            </div>
        </div>

        <div v-if="!items.length"
             class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No knowledge base entries yet. They are auto-generated from AI usage notes.</p>
        </div>
    </div>
</template>
