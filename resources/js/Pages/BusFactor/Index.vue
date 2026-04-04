<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    busFactorData:      { type: Array, default: () => [] },
    singleOwnerModules: { type: Array, default: () => [] },
    availableMembers:   { type: Array, default: () => [] },
});

const riskConfig = {
    critical: { label: 'Critical', bg: 'bg-red-100 text-red-700 border-red-200' },
    high:     { label: 'High',     bg: 'bg-orange-100 text-orange-700 border-orange-200' },
    medium:   { label: 'Medium',   bg: 'bg-yellow-100 text-yellow-700 border-yellow-200' },
    low:      { label: 'Low',      bg: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
};

function ownershipPct(count, total) {
    if (!total) return 0;
    return Math.round((count / total) * 100);
}
</script>

<template>
    <Head title="Bus Factor Analysis" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Bus Factor Analysis</h1>
            <p class="mt-0.5 text-sm text-gray-500">Identify knowledge concentration risks across modules</p>
        </div>

        <!-- Risk summary -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-center">
                <p class="text-2xl font-bold text-red-700">{{ busFactorData.filter(m => m.risk_level === 'critical').length }}</p>
                <p class="text-xs font-semibold text-red-500">Critical Risk</p>
            </div>
            <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 text-center">
                <p class="text-2xl font-bold text-orange-700">{{ busFactorData.filter(m => m.risk_level === 'high').length }}</p>
                <p class="text-xs font-semibold text-orange-500">High Risk</p>
            </div>
            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-center">
                <p class="text-2xl font-bold text-yellow-700">{{ busFactorData.filter(m => m.risk_level === 'medium').length }}</p>
                <p class="text-xs font-semibold text-yellow-500">Medium Risk</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
                <p class="text-2xl font-bold text-emerald-700">{{ busFactorData.filter(m => m.risk_level === 'low').length }}</p>
                <p class="text-xs font-semibold text-emerald-500">Low Risk</p>
            </div>
        </div>

        <!-- Module cards -->
        <div class="space-y-3">
            <div v-for="mod in busFactorData" :key="mod.module_name"
                 :class="riskConfig[mod.risk_level]?.bg || 'bg-gray-50'"
                 class="rounded-xl border p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">{{ mod.module_name }}</h3>
                        <p class="text-xs text-gray-500">{{ mod.total_features }} features &middot; {{ mod.bus_factor }} contributor{{ mod.bus_factor !== 1 ? 's' : '' }}</p>
                    </div>
                    <span :class="riskConfig[mod.risk_level]?.bg || 'bg-gray-100 text-gray-600'"
                          class="rounded-full border px-2.5 py-0.5 text-xs font-semibold">
                        {{ riskConfig[mod.risk_level]?.label || 'Unknown' }}
                    </span>
                </div>

                <!-- Contributors bar -->
                <div class="space-y-1.5">
                    <div v-for="c in mod.contributors" :key="c.name" class="flex items-center gap-2">
                        <span class="text-xs font-medium text-gray-700 w-24 truncate">{{ c.name }}</span>
                        <div class="flex-1 h-4 rounded-full bg-white/50 overflow-hidden">
                            <div class="h-full rounded-full bg-current opacity-60 transition-all"
                                 :style="{ width: ownershipPct(c.feature_count, mod.total_features) + '%' }"
                                 :class="ownershipPct(c.feature_count, mod.total_features) > 60 ? 'text-red-500' : 'text-[#5e16bd]'"></div>
                        </div>
                        <span class="text-xs font-bold text-gray-600 w-8 text-right">{{ c.feature_count }}</span>
                        <span class="text-xs text-gray-400 w-10 text-right">{{ ownershipPct(c.feature_count, mod.total_features) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cross-training recommendations -->
        <div v-if="singleOwnerModules.length" class="mt-8 rounded-xl border border-orange-200 bg-orange-50 p-5">
            <h3 class="text-sm font-bold text-orange-700 mb-3">Cross-Training Recommendations</h3>
            <ul class="space-y-2">
                <li v-for="mod in singleOwnerModules" :key="mod.module_name" class="text-sm text-orange-800">
                    <span class="font-semibold">{{ mod.module_name }}</span> — only
                    <span class="font-semibold">{{ mod.contributors[0]?.name }}</span> works on this module.
                    Consider cross-training another developer.
                </li>
            </ul>
        </div>

        <div v-if="!busFactorData.length" class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No module data available yet. Assign features to modules and team members to see analysis.</p>
        </div>
    </div>
</template>
