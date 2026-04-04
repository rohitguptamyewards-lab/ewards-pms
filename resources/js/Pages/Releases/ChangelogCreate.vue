<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    releases: { type: Array, default: () => [] },
    modules:  { type: Array, default: () => [] },
});

const form = useForm({
    title: '',
    body: '',
    release_id: '',
    audience_module_ids: [],
});

function submit() {
    form.post('/changelogs', { preserveScroll: true });
}

function toggleModule(id) {
    const idx = form.audience_module_ids.indexOf(id);
    if (idx >= 0) form.audience_module_ids.splice(idx, 1);
    else form.audience_module_ids.push(id);
}
</script>

<template>
    <Head title="New Changelog" />

    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">New Changelog</h1>
        <p class="text-sm text-gray-500 mb-6">Draft a merchant-facing changelog. Select a release to auto-populate.</p>

        <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input v-model="form.title" type="text"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="e.g. March 2026 Release Notes" />
                <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Auto-draft from Release</label>
                <select v-model="form.release_id"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="">Manual entry</option>
                    <option v-for="r in releases" :key="r.id" :value="r.id">
                        v{{ r.version }} — {{ r.release_date }}
                    </option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Body will be auto-filled from release features if left empty</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Body</label>
                <textarea v-model="form.body" rows="8"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Write changelog content..." />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Audience Modules</label>
                <div class="flex flex-wrap gap-2">
                    <label v-for="m in modules" :key="m.id"
                           class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm cursor-pointer hover:bg-gray-50"
                           :class="form.audience_module_ids.includes(m.id) ? 'border-[#4e1a77] bg-[#4e1a77]/5' : ''">
                        <input type="checkbox" :checked="form.audience_module_ids.includes(m.id)"
                               @change="toggleModule(m.id)" class="rounded border-gray-300 text-[#4e1a77]" />
                        {{ m.name }}
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-3">
                <a href="/changelogs" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#4e1a77] px-5 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    Create Draft
                </button>
            </div>
        </form>
    </div>
</template>
