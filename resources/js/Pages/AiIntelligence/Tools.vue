<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    tools:     { type: Object, default: () => ({}) },
    isManager: { type: Boolean, default: false },
    filters:   { type: Object, default: () => ({}) },
});

const activeFilter = ref(props.filters.is_active || '');

function applyFilter() {
    router.get('/ai-tools', { is_active: activeFilter.value }, { preserveState: true, replace: true });
}

function currency(val) {
    return '$' + Number(val || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });
}
</script>

<template>
    <Head title="AI Tools" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">AI Tool Registry</h1>
                <p class="mt-0.5 text-sm text-gray-500">Track AI tools, licenses, and spend</p>
            </div>
            <a v-if="isManager" href="/ai-tools/create"
               class="rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + Add Tool
            </a>
        </div>

        <div class="mb-4">
            <select v-model="activeFilter" @change="applyFilter"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <a v-for="t in (tools.data || [])" :key="t.id" :href="`/ai-tools/${t.id}`"
               class="rounded-xl border border-gray-200 bg-white p-5 hover:border-[#4e1a77]/30 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-900">{{ t.name }}</h3>
                    <span :class="t.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                          class="rounded-full px-2 py-0.5 text-xs font-semibold">
                        {{ t.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 mb-2">{{ t.provider }}</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div><span class="text-gray-400">Cost/Seat:</span> {{ currency(t.cost_per_seat_monthly) }}/mo</div>
                    <div><span class="text-gray-400">Seats:</span> {{ t.seats_purchased }}</div>
                    <div class="col-span-2">
                        <span class="text-gray-400">Monthly Spend:</span>
                        <span class="font-semibold text-gray-700">{{ currency(t.cost_per_seat_monthly * t.seats_purchased) }}</span>
                    </div>
                </div>
            </a>
        </div>

        <div v-if="!(tools.data || []).length"
             class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No AI tools registered yet.</p>
        </div>
    </div>
</template>
