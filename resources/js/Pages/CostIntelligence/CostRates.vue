<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    rates:       { type: Object, default: () => ({}) },
    teamMembers: { type: Array, default: () => [] },
    filters:     { type: Object, default: () => ({}) },
});

const memberFilter = ref(props.filters.team_member_id || '');

function applyFilter() {
    router.get('/cost-rates', { team_member_id: memberFilter.value }, { preserveState: true, replace: true });
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head title="Cost Rates — CTO Only" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Cost Rates</h1>
                <p class="mt-0.5 text-sm text-gray-500">Manage team cost rates (CTO only · append-only)</p>
            </div>
            <a href="/cost-rates/create"
               class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + New Rate
            </a>
        </div>

        <div class="mb-4">
            <select v-model="memberFilter" @change="applyFilter"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Members</option>
                <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Member</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Working Hrs/Mo</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Multiplier</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Effective From</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Effective To</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="r in (rates.data || [])" :key="r.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ r.member_name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ r.working_hours_per_month }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ r.overhead_multiplier }}x</td>
                        <td class="px-4 py-3 text-gray-600">{{ formatDate(r.effective_from) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ r.effective_to ? formatDate(r.effective_to) : 'Current' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="!(rates.data || []).length"
             class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No cost rates configured yet.</p>
        </div>
    </div>
</template>
