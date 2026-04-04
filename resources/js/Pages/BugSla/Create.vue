<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    features: { type: Array, default: () => [] },
});

const form = useForm({
    feature_id: '',
    severity: 'p2',
    root_cause: '',
    origin: '',
});

function submit() {
    form.post('/bug-sla');
}

const slaInfo = {
    p0: '4 hours',
    p1: '24 hours',
    p2: '72 hours',
    p3: 'Next sprint',
};
</script>

<template>
    <Head title="Report Bug SLA" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Report Bug</h1>
            <p class="mt-0.5 text-sm text-gray-500">Create a bug SLA tracking record</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 max-w-xl">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Feature *</label>
                <select v-model="form.feature_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="">Select feature...</option>
                    <option v-for="f in features" :key="f.id" :value="f.id">{{ f.title }}</option>
                </select>
                <p v-if="form.errors.feature_id" class="mt-1 text-xs text-red-500">{{ form.errors.feature_id }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Severity *</label>
                <select v-model="form.severity" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="p0">P0 — Critical (SLA: 4h)</option>
                    <option value="p1">P1 — High (SLA: 24h)</option>
                    <option value="p2">P2 — Medium (SLA: 72h)</option>
                    <option value="p3">P3 — Low (SLA: Next Sprint)</option>
                </select>
                <p class="mt-1 text-xs text-gray-400">SLA deadline will be auto-calculated: {{ slaInfo[form.severity] }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Root Cause</label>
                <select v-model="form.root_cause" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="">Unknown / TBD</option>
                    <option value="spec_gap">Spec Gap</option>
                    <option value="code_error">Code Error</option>
                    <option value="environment">Environment</option>
                    <option value="third_party">Third Party</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Origin</label>
                <select v-model="form.origin" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="">Select...</option>
                    <option value="qa_found">QA Found</option>
                    <option value="production_found">Production Found</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="/bug-sla" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#4e1a77] px-6 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    {{ form.processing ? 'Creating...' : 'Report Bug' }}
                </button>
            </div>
        </form>
    </div>
</template>
