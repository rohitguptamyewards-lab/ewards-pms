<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    narrative:         { type: String, default: '' },
    roadmapCompletion: { type: Number, default: 0 },
    moduleMetrics:     { type: Array, default: () => [] },
    teamHealth:        { type: Object, default: () => ({}) },
    risks:             { type: Object, default: () => ({}) },
});

function currency(val) {
    return '₹' + Number(val || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
}
</script>

<template>
    <Head title="CEO Dashboard" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">CEO Business Dashboard</h1>
                <p class="mt-0.5 text-sm text-gray-500">Board-ready overview — aggregate metrics only</p>
            </div>
        </div>

        <!-- Narrative Summary -->
        <div class="rounded-xl border border-[#5e16bd]/20 bg-[#5e16bd]/5 p-5 mb-6">
            <h2 class="text-sm font-semibold text-[#5e16bd] mb-2">Weekly Summary</h2>
            <p class="text-sm text-gray-700">{{ narrative }}</p>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Roadmap Completion</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ roadmapCompletion }}%</p>
                <div class="mt-2 h-2 rounded-full bg-gray-100">
                    <div class="h-2 rounded-full bg-[#5e16bd]" :style="{ width: roadmapCompletion + '%' }"></div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Sprint Delivery Rate</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ teamHealth.sprint_delivery_rate || 0 }}%</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Team Utilisation</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ teamHealth.utilisation || 0 }}%</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Active Blockers</p>
                <p class="text-2xl font-bold" :class="risks.active_blockers > 0 ? 'text-red-500' : 'text-gray-900'">
                    {{ risks.active_blockers || 0 }}
                </p>
            </div>
        </div>

        <!-- Risk Indicators -->
        <div v-if="risks.overdue_features > 0 || risks.active_blockers > 0"
             class="rounded-xl border border-red-200 bg-red-50 p-4 mb-6">
            <h3 class="text-sm font-semibold text-red-700 mb-2">Risk Indicators</h3>
            <ul class="text-sm text-red-600 space-y-1">
                <li v-if="risks.overdue_features > 0">{{ risks.overdue_features }} overdue feature(s)</li>
                <li v-if="risks.active_blockers > 0">{{ risks.active_blockers }} active blocker(s)</li>
            </ul>
        </div>

        <!-- Investment vs Impact Matrix -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Investment vs Impact by Module</h2>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white mb-8">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Module</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Total Investment</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Adoption</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Revenue</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">ROI Ratio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="m in moduleMetrics" :key="m.module_id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ m.module_name }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(m.total_cost) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ m.adoption }} merchants</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(m.revenue) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span :class="m.roi_ratio >= 1 ? 'text-emerald-600' : 'text-red-500'" class="font-semibold">
                                {{ m.roi_ratio }}x
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
