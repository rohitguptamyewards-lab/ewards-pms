<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    leaves:      { type: Object, default: () => ({}) },
    teamMembers: { type: Array, default: () => [] },
    isManager:   { type: Boolean, default: false },
    filters:     { type: Object, default: () => ({}) },
});

const memberFilter = ref(props.filters.team_member_id || '');
const typeFilter   = ref(props.filters.leave_type || '');

function applyFilter() {
    router.get('/leave-entries', {
        team_member_id: memberFilter.value,
        leave_type: typeFilter.value,
    }, { preserveState: true, replace: true });
}

const typeConfig = {
    planned: { label: 'Planned', bg: 'bg-blue-100 text-blue-700' },
    sick:    { label: 'Sick', bg: 'bg-red-100 text-red-700' },
    holiday: { label: 'Holiday', bg: 'bg-emerald-100 text-emerald-700' },
    other:   { label: 'Other', bg: 'bg-gray-100 text-gray-600' },
};

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' });
}
</script>

<template>
    <Head title="Leave Calendar" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Leave Calendar</h1>
                <p class="mt-0.5 text-sm text-gray-500">Track team availability for capacity planning</p>
            </div>
            <a href="/leave-entries/create"
               class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + Record Leave
            </a>
        </div>

        <div class="flex gap-3 mb-4">
            <select v-model="memberFilter" @change="applyFilter"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Members</option>
                <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <select v-model="typeFilter" @change="applyFilter"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Types</option>
                <option value="planned">Planned</option>
                <option value="sick">Sick</option>
                <option value="holiday">Holiday</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Member</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Duration</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="l in (leaves.data || [])" :key="l.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ l.member_name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ formatDate(l.leave_date) }}</td>
                        <td class="px-4 py-3">
                            <span :class="typeConfig[l.leave_type]?.bg || 'bg-gray-100'"
                                  class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                {{ typeConfig[l.leave_type]?.label || l.leave_type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ l.half_day ? 'Half Day' : 'Full Day' }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ l.source || 'manual' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="!(leaves.data || []).length"
             class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No leave entries found.</p>
        </div>
    </div>
</template>
