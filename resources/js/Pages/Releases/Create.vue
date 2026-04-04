<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    features: { type: Array, default: () => [] },
});

const form = useForm({
    version: '',
    release_date: new Date().toISOString().split('T')[0],
    environment: 'staging',
    notes: '',
    feature_ids: [],
});

function toggleFeature(id) {
    const idx = form.feature_ids.indexOf(id);
    if (idx >= 0) {
        form.feature_ids.splice(idx, 1);
    } else {
        form.feature_ids.push(id);
    }
}

function submit() {
    form.post('/releases');
}
</script>

<template>
    <Head title="Create Release" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create Release</h1>
            <p class="mt-0.5 text-sm text-gray-500">Deploy a new version with selected features</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Version *</label>
                    <input v-model="form.version" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                           placeholder="e.g. 2.4.0" />
                    <p v-if="form.errors.version" class="mt-1 text-xs text-red-500">{{ form.errors.version }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Release Date *</label>
                    <input type="date" v-model="form.release_date"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Environment *</label>
                    <select v-model="form.environment" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="staging">Staging</option>
                        <option value="production">Production</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Release Notes</label>
                <textarea v-model="form.notes" rows="3"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                          placeholder="Auto-generated from feature titles if left empty" />
            </div>

            <!-- Feature selection -->
            <div class="border-t border-gray-100 pt-4">
                <h3 class="text-sm font-bold text-gray-900 mb-3">Include Features ({{ form.feature_ids.length }} selected)</h3>
                <p class="text-xs text-gray-400 mb-3">Selected features will be marked as Released.</p>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <label v-for="f in features" :key="f.id"
                           :class="form.feature_ids.includes(f.id) ? 'border-[#4e1a77]/30 bg-purple-50' : 'border-gray-100'"
                           class="flex items-center gap-3 rounded-lg border p-3 cursor-pointer transition-colors">
                        <input type="checkbox" :checked="form.feature_ids.includes(f.id)"
                               @change="toggleFeature(f.id)"
                               class="h-4 w-4 rounded border-gray-300 text-[#4e1a77] focus:ring-[#4e1a77]" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ f.title }}</p>
                            <p class="text-xs text-gray-400">{{ f.module_name || 'No module' }} · {{ f.status }}</p>
                        </div>
                    </label>
                </div>
                <div v-if="!features.length" class="text-sm text-gray-400 py-4 text-center">
                    No features are ready for release.
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="/releases" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#4e1a77] px-6 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    {{ form.processing ? 'Creating...' : 'Create Release' }}
                </button>
            </div>
        </form>
    </div>
</template>
