<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    nextNumber: { type: Number, default: 1 },
    features:   { type: Array, default: () => [] },
});

const form = useForm({
    goal: '',
    start_date: '',
    end_date: '',
    total_capacity_hours: null,
    capacity_override_reason: '',
    feature_ids: [],
    feature_hours: {},
});

const selectedFeatures = ref([]);

function toggleFeature(featureId) {
    const idx = form.feature_ids.indexOf(featureId);
    if (idx >= 0) {
        form.feature_ids.splice(idx, 1);
        delete form.feature_hours[featureId];
    } else {
        form.feature_ids.push(featureId);
        const f = props.features.find(f => f.id === featureId);
        form.feature_hours[featureId] = f?.estimated_hours || 0;
    }
}

function isSelected(id) {
    return form.feature_ids.includes(id);
}

function submit() {
    form.post('/sprints');
}
</script>

<template>
    <Head title="Create Sprint" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create Sprint #{{ nextNumber }}</h1>
            <p class="mt-0.5 text-sm text-gray-500">Plan a new sprint cycle</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sprint Goal</label>
                    <textarea v-model="form.goal" rows="2"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                              placeholder="What should this sprint achieve?" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                    <input type="date" v-model="form.start_date"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                    <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-500">{{ form.errors.start_date }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                    <input type="date" v-model="form.end_date"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                    <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-500">{{ form.errors.end_date }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Capacity (hours)</label>
                    <input type="number" v-model="form.total_capacity_hours" min="0"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Capacity Override Reason</label>
                    <input v-model="form.capacity_override_reason"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                           placeholder="e.g. holidays, leaves" />
                </div>
            </div>

            <!-- Feature selection -->
            <div class="border-t border-gray-100 pt-4">
                <h3 class="text-sm font-bold text-gray-900 mb-3">Commit Features</h3>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    <div v-for="f in features" :key="f.id"
                         :class="isSelected(f.id) ? 'border-[#5e16bd]/30 bg-purple-50' : 'border-gray-100'"
                         class="flex items-center gap-3 rounded-lg border p-3 cursor-pointer transition-colors"
                         @click="toggleFeature(f.id)">
                        <input type="checkbox" :checked="isSelected(f.id)"
                               class="h-4 w-4 rounded border-gray-300 text-[#5e16bd] focus:ring-[#5e16bd]" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ f.title }}</p>
                            <p class="text-xs text-gray-400">{{ f.module_name || 'No module' }} · {{ f.assignee_name || 'Unassigned' }}</p>
                        </div>
                        <div v-if="isSelected(f.id)" class="flex items-center gap-1">
                            <input type="number" v-model="form.feature_hours[f.id]" min="0"
                                   class="w-16 rounded border border-gray-200 px-2 py-1 text-xs text-center"
                                   @click.stop />
                            <span class="text-xs text-gray-400">hrs</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">{{ f.priority }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="/sprints" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#5e16bd] px-6 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    {{ form.processing ? 'Creating...' : 'Create Sprint' }}
                </button>
            </div>
        </form>
    </div>
</template>
