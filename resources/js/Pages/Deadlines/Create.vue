<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    features:    { type: Array, default: () => [] },
    initiatives: { type: Array, default: () => [] },
});

const form = useForm({
    deadlineable_type: 'feature',
    deadlineable_id: '',
    type: 'target',
    due_date: '',
});

const entities = computed(() => {
    return form.deadlineable_type === 'feature' ? props.features : props.initiatives;
});

function submit() {
    form.post('/deadlines');
}
</script>

<template>
    <Head title="Create Deadline" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create Deadline</h1>
            <p class="mt-0.5 text-sm text-gray-500">Set a new deadline for a feature or initiative</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 max-w-xl">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Entity Type *</label>
                <select v-model="form.deadlineable_type" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="feature">Feature</option>
                    <option value="initiative">Initiative</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ form.deadlineable_type === 'feature' ? 'Feature' : 'Initiative' }} *</label>
                <select v-model="form.deadlineable_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="">Select...</option>
                    <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.title }}</option>
                </select>
                <p v-if="form.errors.deadlineable_id" class="mt-1 text-xs text-red-500">{{ form.errors.deadlineable_id }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deadline Type *</label>
                <select v-model="form.type" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="hard">Hard — immovable, contractual</option>
                    <option value="target">Target — aim date</option>
                    <option value="soft">Soft — flexible</option>
                    <option value="recurring">Recurring</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                <input type="date" v-model="form.due_date" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                <p v-if="form.errors.due_date" class="mt-1 text-xs text-red-500">{{ form.errors.due_date }}</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="/deadlines" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#4e1a77] px-6 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    {{ form.processing ? 'Creating...' : 'Create Deadline' }}
                </button>
            </div>
        </form>
    </div>
</template>
