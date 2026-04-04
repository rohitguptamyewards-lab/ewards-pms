<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    records:   { type: Object, default: () => ({}) },
    filters:   { type: Object, default: () => ({}) },
    features:  { type: Array, default: () => [] },
    isManager: { type: Boolean, default: false },
});

const localFilters = ref({ ...props.filters });

function applyFilters() {
    router.get('/bug-sla', localFilters.value, { preserveState: true, replace: true });
}

const severityConfig = {
    p0: { label: 'P0 — Critical', bg: 'bg-red-100 text-red-700', sla: '4h' },
    p1: { label: 'P1 — High', bg: 'bg-orange-100 text-orange-700', sla: '24h' },
    p2: { label: 'P2 — Medium', bg: 'bg-yellow-100 text-yellow-700', sla: '72h' },
    p3: { label: 'P3 — Low', bg: 'bg-gray-100 text-gray-600', sla: 'Next Sprint' },
};

function formatDateTime(dt) {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('en-IN', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head title="Bug SLA Records" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Bug SLA Records</h1>
                <p class="mt-0.5 text-sm text-gray-500">Track bug severity SLA compliance</p>
            </div>
            <a href="/bug-sla/create"
               class="rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + Report Bug
            </a>
        </div>

        <div class="mb-4 flex flex-wrap gap-3">
            <select v-model="localFilters.severity" @change="applyFilters"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Severities</option>
                <option value="p0">P0 — Critical</option>
                <option value="p1">P1 — High</option>
                <option value="p2">P2 — Medium</option>
                <option value="p3">P3 — Low</option>
            </select>
            <select v-model="localFilters.breached" @change="applyFilters"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All</option>
                <option :value="true">Breached</option>
                <option :value="false">Not Breached</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Feature</th>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3">SLA Deadline</th>
                        <th class="px-4 py-3">Breached</th>
                        <th class="px-4 py-3">Reopens</th>
                        <th class="px-4 py-3">Origin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="r in (records.data || [])" :key="r.id"
                        :class="r.breached_at ? 'bg-red-50/50' : ''" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ r.feature_title }}</td>
                        <td class="px-4 py-3">
                            <span :class="severityConfig[r.severity]?.bg || 'bg-gray-100'"
                                  class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                {{ severityConfig[r.severity]?.label || r.severity }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ formatDateTime(r.sla_deadline) }}</td>
                        <td class="px-4 py-3">
                            <span v-if="r.breached_at" class="text-xs font-bold text-red-600">BREACHED</span>
                            <span v-else class="text-xs text-emerald-600">OK</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ r.reopen_count || 0 }}</td>
                        <td class="px-4 py-3 text-gray-600 capitalize">{{ (r.origin || '—').replace(/_/g, ' ') }}</td>
                    </tr>
                    <tr v-if="!(records.data || []).length">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">No bug SLA records found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="records.links" class="mt-4 flex gap-1">
            <template v-for="link in records.links" :key="link.label">
                <a v-if="link.url" :href="link.url"
                   :class="link.active ? 'bg-[#4e1a77] text-white' : 'bg-white text-gray-600'"
                   class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium"
                   v-html="link.label" />
            </template>
        </div>
    </div>
</template>
