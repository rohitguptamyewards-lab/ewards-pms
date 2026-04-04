<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    tools:        { type: Array, default: () => [] },
    totalSpend:   { type: Number, default: 0 },
    matrix:       { type: Array, default: () => [] },
    profiles:     { type: Array, default: () => [] },
    roi:          { type: Object, default: () => ({}) },
    topTemplates: { type: Array, default: () => [] },
});

function currency(val) {
    return '$' + Number(val || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });
}

const outcomeColors = {
    helpful: 'text-emerald-600', partially: 'text-yellow-600',
    misleading: 'text-red-600', not_useful: 'text-gray-500',
};
</script>

<template>
    <Head title="AI Intelligence Dashboard" />

    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">AI Intelligence Dashboard</h1>
        <p class="text-sm text-gray-500 mb-6">CTO-only — tool registry, effectiveness, ROI, team profiles</p>

        <!-- Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Total Monthly Spend</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ currency(totalSpend) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Active Tools</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ tools.filter(t => t.is_active).length }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Time Saved</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ roi.total_time_saved_hours || 0 }}h</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">ROI Ratio</p>
                <p class="text-2xl font-bold" :class="(roi.roi_ratio || 0) >= 1 ? 'text-emerald-600' : 'text-red-500'">
                    {{ roi.roi_ratio || 0 }}x
                </p>
            </div>
        </div>

        <!-- Tool Registry -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Tool Registry</h2>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white mb-8">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Tool</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Provider</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Cost/Seat</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Seats</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Monthly Spend</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="t in tools" :key="t.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ t.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ t.provider }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(t.cost_per_seat_monthly) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ t.seats_purchased }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ currency(t.monthly_spend) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Per-Person Profiles -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Team AI Profiles</h2>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white mb-8">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Member</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Usage Count</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Tools Used</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Capabilities</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="p in profiles" :key="p.member_id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ p.member_name }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ p.usage_count }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ p.tools_used }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ p.capabilities_used }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Top Templates -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Top Prompt Templates</h2>
        <div class="space-y-2">
            <div v-for="t in topTemplates" :key="t.id"
                 class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3">
                <div>
                    <span class="text-sm font-medium text-gray-900">{{ t.title }}</span>
                    <span class="text-xs text-gray-400 ml-2">{{ t.tool_name }}</span>
                </div>
                <span class="text-xs font-semibold text-[#4e1a77]">{{ t.usage_count }} uses</span>
            </div>
        </div>
    </div>
</template>
