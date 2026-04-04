<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    featureMetrics: { type: Array, default: () => [] },
    moduleMetrics:  { type: Array, default: () => [] },
});

function currency(val) {
    return '₹' + Number(val || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
}
</script>

<template>
    <Head title="Cost vs Impact" />

    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Cost vs Impact Analysis</h1>
        <p class="text-sm text-gray-500 mb-6">Compare investment against adoption and revenue per feature and module</p>

        <!-- Module Rollup -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Module-level ROI</h2>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white mb-8">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Module</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Investment</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Merchants</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Revenue</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">ROI</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Maintenance/Mo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="m in moduleMetrics" :key="m.module_id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ m.module_name }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(m.lifetime_investment) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ m.merchant_count }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(m.total_revenue) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span :class="m.roi >= 1 ? 'text-emerald-600' : 'text-red-500'" class="font-semibold">{{ m.roi }}x</span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(m.maintenance_cost) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Feature-level -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Feature-level Breakdown</h2>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Feature</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Module</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Cost</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Merchants</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Revenue</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">ROI</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Cost/Merchant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="f in featureMetrics" :key="f.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ f.title }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ f.module_name }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(f.total_cost) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ f.merchants_adopted }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(f.revenue_attributed) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span :class="f.roi_ratio >= 1 ? 'text-emerald-600' : 'text-red-500'" class="font-semibold">{{ f.roi_ratio }}x</span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ currency(f.cost_per_merchant) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="!featureMetrics.length" class="py-12 text-center rounded-xl border border-dashed border-gray-200 bg-white mt-4">
            <p class="text-sm text-gray-400">No cost data available yet. Cost snapshots are generated daily.</p>
        </div>
    </div>
</template>
