<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    featureCosts:    { type: Array, default: () => [] },
    initiativeCosts: { type: Array, default: () => [] },
    moduleCosts:     { type: Array, default: () => [] },
    meetingCost:     { type: Number, default: 0 },
    debtScores:      { type: Array, default: () => [] },
});

function currency(val) {
    return '₹' + Number(val || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
}

function healthColor(score) {
    if (score < 50) return 'bg-red-100 text-red-700';
    if (score <= 70) return 'bg-yellow-100 text-yellow-700';
    return 'bg-emerald-100 text-emerald-700';
}

function healthLabel(score) {
    if (score < 50) return 'Red';
    if (score <= 70) return 'Yellow';
    return 'Green';
}
</script>

<template>
    <Head title="CTO Cost Dashboard" />

    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Cost Intelligence Dashboard</h1>
        <p class="text-sm text-gray-500 mb-6">CTO-only view — feature costs, initiative rollup, debt scorecard</p>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Total Features Tracked</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ featureCosts.length }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Meeting Hours</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ meetingCost }}h</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Modules</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ moduleCosts.length }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Initiatives</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ initiativeCosts.length }}</p>
            </div>
        </div>

        <!-- Feature Cost Breakdown -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Feature Cost Breakdown</h2>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white mb-8">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Feature</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Module</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Total Cost</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Est. Hours</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Actual Hours</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="f in featureCosts" :key="f.feature_id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ f.feature_title }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ f.module_name }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(f.total_cost) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ f.estimated_hours }}h</td>
                        <td class="px-4 py-3 text-right" :class="f.actual_hours > f.estimated_hours ? 'text-red-500' : 'text-gray-600'">
                            {{ f.actual_hours }}h
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Initiative Cost Rollup -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Initiative Cost Rollup</h2>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white mb-8">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Initiative</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Features</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Total Cost</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Est. Hours</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Actual Hours</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="i in initiativeCosts" :key="i.initiative_id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ i.initiative_title || 'Unlinked' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ i.feature_count }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(i.total_cost) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ i.estimated_hours }}h</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ i.actual_hours }}h</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Tech Debt Scorecard -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Technical Debt Scorecard</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="d in debtScores" :key="d.module_id"
                 class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-900">{{ d.module_name }}</h3>
                    <span :class="healthColor(d.health_score)"
                          class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                        {{ d.health_score }} · {{ healthLabel(d.health_score) }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div><span class="text-gray-400">Backlog:</span> {{ d.debt_backlog_size }} items</div>
                    <div><span class="text-gray-400">Hours:</span> {{ d.debt_backlog_hours }}h</div>
                    <div><span class="text-gray-400">Velocity:</span> {{ d.debt_velocity > 0 ? '+' : '' }}{{ d.debt_velocity }}</div>
                    <div><span class="text-gray-400">Ratio:</span> {{ d.debt_to_feature_ratio }}</div>
                </div>
            </div>
        </div>

        <div v-if="!debtScores.length" class="py-12 text-center rounded-xl border border-dashed border-gray-200 bg-white mt-4">
            <p class="text-sm text-gray-400">No debt scores calculated yet. Scores are generated weekly.</p>
        </div>
    </div>
</template>
