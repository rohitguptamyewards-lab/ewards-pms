<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    templates: { type: Object, default: () => ({}) },
    tools:     { type: Array, default: () => [] },
    filters:   { type: Object, default: () => ({}) },
});

const capFilter  = ref(props.filters.capability || '');
const toolFilter = ref(props.filters.ai_tool_id || '');

function applyFilter() {
    router.get('/prompt-templates', { capability: capFilter.value, ai_tool_id: toolFilter.value }, { preserveState: true, replace: true });
}

const capLabels = { code: 'Code', debug: 'Debug', architecture: 'Architecture', content: 'Content', research: 'Research' };
</script>

<template>
    <Head title="Prompt Templates" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Prompt Templates</h1>
                <p class="mt-0.5 text-sm text-gray-500">Shared AI prompts organized by capability and tool</p>
            </div>
            <a href="/prompt-templates/create"
               class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + New Template
            </a>
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
            <div v-for="t in (templates.data || [])" :key="t.id"
                 class="rounded-xl border border-gray-200 bg-white p-5 hover:border-[#5e16bd]/30 transition-colors">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">{{ t.title }}</h3>
                        <p class="text-xs text-gray-400">{{ t.tool_name }} · {{ capLabels[t.capability] || t.capability }} · Used {{ t.usage_count }}x</p>
                    </div>
                    <span class="rounded-full bg-[#5e16bd]/10 px-2.5 py-0.5 text-xs font-medium text-[#5e16bd]">
                        {{ capLabels[t.capability] || t.capability }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 line-clamp-3">{{ t.content }}</p>
                <div v-if="t.tags" class="flex flex-wrap gap-1 mt-2">
                    <span v-for="tag in (JSON.parse(t.tags || '[]'))" :key="tag"
                          class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500">{{ tag }}</span>
                </div>
            </div>
        </div>

        <div v-if="!(templates.data || []).length"
             class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No prompt templates found.</p>
        </div>
    </div>
</template>
