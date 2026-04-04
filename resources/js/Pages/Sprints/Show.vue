<script setup>
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    sprint:    { type: Object, default: () => ({}) },
    health:    { type: Object, default: () => ({ score: 0, label: 'Unknown' }) },
    velocity:  { type: Number, default: 0 },
    isManager: { type: Boolean, default: false },
});

const statusConfig = {
    backlog:           { label: 'Backlog', bg: 'bg-gray-100 text-gray-600' },
    in_progress:       { label: 'In Progress', bg: 'bg-blue-100 text-blue-700' },
    in_review:         { label: 'In Review', bg: 'bg-indigo-100 text-indigo-700' },
    ready_for_qa:      { label: 'Ready for QA', bg: 'bg-orange-100 text-orange-700' },
    in_qa:             { label: 'In QA', bg: 'bg-yellow-100 text-yellow-700' },
    ready_for_release: { label: 'Ready for Release', bg: 'bg-teal-100 text-teal-700' },
    released:          { label: 'Released', bg: 'bg-emerald-100 text-emerald-700' },
};

const healthColor = {
    Healthy:   'text-emerald-600 bg-emerald-50 border-emerald-200',
    'At Risk': 'text-orange-600 bg-orange-50 border-orange-200',
    Unhealthy: 'text-red-600 bg-red-50 border-red-200',
};

async function activateSprint() {
    await axios.post(`/api/v1/sprints/${props.sprint.id}/activate`);
    window.location.reload();
}

async function completeSprint() {
    await axios.post(`/api/v1/sprints/${props.sprint.id}/complete`);
    window.location.reload();
}
</script>

<template>
    <Head :title="`Sprint #${sprint.sprint_number}`" />

    <div>
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Sprint #{{ sprint.sprint_number }}</h1>
                <p class="text-sm text-gray-500">{{ sprint.start_date }} → {{ sprint.end_date }}</p>
            </div>
            <div class="flex gap-2">
                <button v-if="isManager && sprint.status === 'planning'" @click="activateSprint"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Activate Sprint
                </button>
                <button v-if="isManager && sprint.status === 'active'" @click="completeSprint"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Complete Sprint
                </button>
            </div>
        </div>

        <!-- Goal -->
        <div v-if="sprint.goal" class="mb-6 rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase mb-1">Sprint Goal</p>
            <p class="text-sm text-gray-700">{{ sprint.goal }}</p>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ sprint.committed_hours || 0 }}</p>
                <p class="text-xs text-gray-400">Committed Hours</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center">
                <p class="text-2xl font-bold text-emerald-600">{{ sprint.delivered_count || 0 }}</p>
                <p class="text-xs text-gray-400">Delivered</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center">
                <p class="text-2xl font-bold text-orange-600">{{ sprint.carry_over_count || 0 }}</p>
                <p class="text-xs text-gray-400">Carry Over</p>
            </div>
            <div :class="healthColor[health.label] || 'bg-gray-50'"
                 class="rounded-xl border p-4 text-center">
                <p class="text-2xl font-bold">{{ health.score }}%</p>
                <p class="text-xs font-semibold">{{ health.label }}</p>
            </div>
        </div>

        <!-- Features table -->
        <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="text-sm font-bold text-gray-700">Committed Features</h3>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-semibold uppercase text-gray-500 bg-gray-50/50">
                    <tr>
                        <th class="px-4 py-2.5">Feature</th>
                        <th class="px-4 py-2.5">Module</th>
                        <th class="px-4 py-2.5">Assignee</th>
                        <th class="px-4 py-2.5">Status</th>
                        <th class="px-4 py-2.5">Hours</th>
                        <th class="px-4 py-2.5">Carry Over</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="f in (sprint.features || [])" :key="f.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ f.feature_title }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ f.module_name || '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ f.assignee_name || '—' }}</td>
                        <td class="px-4 py-3">
                            <span :class="statusConfig[f.feature_status]?.bg || 'bg-gray-100'"
                                  class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                {{ statusConfig[f.feature_status]?.label || f.feature_status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ f.committed_hours }}h</td>
                        <td class="px-4 py-3">
                            <span v-if="f.carried_over" class="text-xs font-semibold text-orange-600">Yes</span>
                            <span v-else class="text-xs text-gray-400">—</span>
                        </td>
                    </tr>
                    <tr v-if="!(sprint.features || []).length">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">No features committed to this sprint.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
