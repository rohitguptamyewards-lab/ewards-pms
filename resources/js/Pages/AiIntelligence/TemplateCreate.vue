<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    tools: { type: Array, default: () => [] },
});

const form = useForm({
    ai_tool_id: '',
    capability: '',
    title: '',
    content: '',
    tags: [],
});

const tagInput = ref('');

function addTag() {
    const tag = tagInput.value.trim();
    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag);
    }
    tagInput.value = '';
}

function removeTag(tag) {
    form.tags = form.tags.filter(t => t !== tag);
}

function submit() {
    form.post('/prompt-templates', { preserveScroll: true });
}

const capabilities = [
    { value: 'code', label: 'Code' },
    { value: 'debug', label: 'Debug' },
    { value: 'architecture', label: 'Architecture' },
    { value: 'content', label: 'Content' },
    { value: 'research', label: 'Research' },
];
</script>

<template>
    <Head title="New Prompt Template" />

    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">New Prompt Template</h1>

        <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">AI Tool *</label>
                    <select v-model="form.ai_tool_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="">Select tool</option>
                        <option v-for="t in tools" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                    <p v-if="form.errors.ai_tool_id" class="text-xs text-red-500 mt-1">{{ form.errors.ai_tool_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Capability *</label>
                    <select v-model="form.capability" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="">Select capability</option>
                        <option v-for="c in capabilities" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input v-model="form.title" type="text"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="e.g. Code Review Prompt" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                <textarea v-model="form.content" rows="10"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm font-mono" placeholder="Write your prompt template..." />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                <div class="flex gap-2 mb-2">
                    <input v-model="tagInput" type="text" @keydown.enter.prevent="addTag"
                           class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Add tag..." />
                    <button type="button" @click="addTag"
                            class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200">Add</button>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <span v-for="tag in form.tags" :key="tag"
                          class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                        {{ tag }}
                        <button type="button" @click="removeTag(tag)" class="hover:text-red-500">&times;</button>
                    </span>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-3">
                <a href="/prompt-templates" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#4e1a77] px-5 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    Create Template
                </button>
            </div>
        </form>
    </div>
</template>
