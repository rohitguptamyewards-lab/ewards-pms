<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    decisions: { type: Object, default: () => ({ data: [], links: [] }) },
    filters:   { type: Object, default: () => ({}) },
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
const canCreate = computed(() => ['cto', 'ceo', 'manager'].includes(role.value));

const localFilters = ref({ status: props.filters.status || '' });

function applyFilters() {
    const params = {};
    if (localFilters.value.status) params.status = localFilters.value.status;
    router.get('/decisions', params, { preserveState: true });
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

const statusConfig = {
    proposed:   { label: 'Proposed',   bg: 'bg-blue-100 text-blue-700' },
    open:       { label: 'Open',       bg: 'bg-yellow-100 text-yellow-700' },
    decided:    { label: 'Decided',    bg: 'bg-emerald-100 text-emerald-700' },
    superseded: { label: 'Superseded', bg: 'bg-gray-100 text-gray-500' },
};
</script>

<template>
    <Head title="Decision Log" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Decision Log</h1>
                <p class="mt-0.5 text-sm text-gray-500">Architectural and product decisions — append-only record</p>
            </div>
            <Link v-if="canCreate" href="/decisions/create"
                  class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#3d1560] transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Record Decision
            </Link>
        </div>

        <!-- Filter -->
        <div class="mb-5 flex gap-3">
            <select v-model="localFilters.status" @change="applyFilters" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-100 bg-gray-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Decision</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Made By</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Linked To</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="d in decisions.data" :key="d.id" class="group hover:bg-[#f5f0ff]/30 transition-colors">
                        <td class="px-5 py-3.5">
                            <Link :href="`/decisions/${d.id}`" class="font-medium text-gray-900 hover:text-[#4e1a77]">
                                {{ d.title }}
                            </Link>
                            <p v-if="d.superseded_by_title" class="mt-0.5 text-xs text-orange-500">Superseded by: {{ d.superseded_by_title }}</p>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600">{{ d.decision_maker_name || '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span :class="statusConfig[d.status]?.bg || 'bg-gray-100 text-gray-600'"
                                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                {{ statusConfig[d.status]?.label || d.status }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-gray-500">{{ formatDate(d.decision_date) }}</td>
                        <td class="px-5 py-3.5 text-xs text-gray-500 capitalize">{{ d.linked_to_type || '—' }}</td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!decisions.data?.length" class="py-16 text-center">
                <p class="text-sm text-gray-500">No decisions recorded yet.</p>
            </div>

            <div v-if="decisions.links?.length > 3" class="flex items-center justify-center gap-1 border-t border-gray-100 px-5 py-3">
                <Link v-for="link in decisions.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="[link.active ? 'bg-[#4e1a77] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'pointer-events-none opacity-40' : '']"
                      class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                      v-html="link.label" preserve-scroll />
            </div>
        </div>
    </div>
</template>
