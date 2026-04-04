<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    initiatives: { type: Object, default: () => ({ data: [], links: [] }) },
    modules:     { type: Array, default: () => [] },
    teamMembers: { type: Array, default: () => [] },
    filters:     { type: Object, default: () => ({}) },
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
const canCreate = computed(() => ['cto', 'ceo', 'manager'].includes(role.value));

const localFilters = ref({
    status:      props.filters.status || '',
    origin_type: props.filters.origin_type || '',
    owner_id:    props.filters.owner_id || '',
    module_id:   props.filters.module_id || '',
});

function applyFilters() {
    const params = {};
    Object.entries(localFilters.value).forEach(([k, v]) => { if (v) params[k] = v; });
    router.get('/initiatives', params, { preserveState: true });
}

function clearFilters() {
    localFilters.value = { status: '', origin_type: '', owner_id: '', module_id: '' };
    router.get('/initiatives');
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

const statusConfig = {
    planning:  { label: 'Planning',  bg: 'bg-gray-100 text-gray-700' },
    active:    { label: 'Active',    bg: 'bg-emerald-100 text-emerald-700' },
    at_risk:   { label: 'At Risk',   bg: 'bg-orange-100 text-orange-700' },
    completed: { label: 'Completed', bg: 'bg-teal-100 text-teal-700' },
    archived:  { label: 'Archived',  bg: 'bg-gray-100 text-gray-500' },
};

const originConfig = {
    strategic:     { label: 'Strategic' },
    data_insight:  { label: 'Data Insight' },
    tech_debt:     { label: 'Tech Debt' },
    client_demand: { label: 'Client Demand' },
};
</script>

<template>
    <Head title="Initiatives" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Initiatives</h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ initiatives.total ?? initiatives.data?.length ?? 0 }} initiative(s)</p>
            </div>
            <Link v-if="canCreate" href="/initiatives/create"
                  class="inline-flex items-center gap-2 rounded-lg bg-[#5e16bd] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#4c12a1] transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                New Initiative
            </Link>
        </div>

        <!-- Filters -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Status</label>
                    <select v-model="localFilters.status" @change="applyFilters" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Origin</label>
                    <select v-model="localFilters.origin_type" @change="applyFilters" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option v-for="(cfg, key) in originConfig" :key="key" :value="key">{{ cfg.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Owner</label>
                    <select v-model="localFilters.owner_id" @change="applyFilters" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Module</label>
                    <select v-model="localFilters.module_id" @change="applyFilters" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
                <button @click="clearFilters" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-500 hover:bg-gray-50">Clear</button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-100 bg-gray-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Initiative</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Owner</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Impact</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Features</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Deadline</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="item in initiatives.data" :key="item.id" class="group hover:bg-[#f5f0ff]/30 transition-colors">
                        <td class="px-5 py-3.5">
                            <Link :href="`/initiatives/${item.id}`" class="font-medium text-gray-900 hover:text-[#5e16bd]">
                                {{ item.title }}
                            </Link>
                            <p class="mt-0.5 text-xs text-gray-400">{{ originConfig[item.origin_type]?.label || item.origin_type }}</p>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600">{{ item.owner_name || '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span :class="statusConfig[item.status]?.bg || 'bg-gray-100 text-gray-600'"
                                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                {{ statusConfig[item.status]?.label || item.status }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600 text-xs">{{ item.expected_impact?.replace('_', ' ') || '—' }}</td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="text-sm font-semibold text-gray-800">{{ item.completed_feature_count || 0 }}</span>
                            <span class="text-gray-400"> / {{ item.feature_count || 0 }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-500 text-xs">{{ formatDate(item.deadline) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Empty state -->
            <div v-if="!initiatives.data?.length" class="py-16 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                </svg>
                <p class="mt-3 text-sm text-gray-500">No initiatives yet.</p>
                <Link v-if="canCreate" href="/initiatives/create" class="mt-2 inline-block text-sm font-medium text-[#5e16bd] hover:underline">
                    Create the first initiative
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="initiatives.links?.length > 3" class="flex items-center justify-center gap-1 border-t border-gray-100 px-5 py-3">
                <Link v-for="link in initiatives.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="[link.active ? 'bg-[#5e16bd] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'pointer-events-none opacity-40' : '']"
                      class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                      v-html="link.label" preserve-scroll />
            </div>
        </div>
    </div>
</template>
