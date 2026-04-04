<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    logs:        { type: Object, default: () => ({}) },
    filters:     { type: Object, default: () => ({}) },
    features:    { type: Array, default: () => [] },
    teamMembers: { type: Array, default: () => [] },
    isManager:   { type: Boolean, default: false },
});

const localFilters = ref({ ...props.filters });

function applyFilters() {
    router.get('/activity-logs', localFilters.value, { preserveState: true, replace: true });
}

const statusConfig = {
    done:        { label: 'Done', bg: 'bg-emerald-100 text-emerald-700' },
    in_progress: { label: 'In Progress', bg: 'bg-blue-100 text-blue-700' },
    blocked:     { label: 'Blocked', bg: 'bg-red-100 text-red-700' },
};

const durationLabels = {
    '30m': '30 min', '1h': '1 hour', '2h': '2 hours', '3h': '3 hours', 'half_day': 'Half day', 'full_day': 'Full day',
};
</script>

<template>
    <Head title="Activity Logs" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Activity Logs</h1>
                <p class="mt-0.5 text-sm text-gray-500">Track daily work activities</p>
            </div>
            <a href="/activity-logs/create"
               class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] transition-colors">
                + Log Activity
            </a>
        </div>

        <!-- Filters -->
        <div class="mb-4 flex flex-wrap gap-3">
            <select v-model="localFilters.status" @change="applyFilters"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="done">Done</option>
                <option value="in_progress">In Progress</option>
                <option value="blocked">Blocked</option>
            </select>
            <select v-if="isManager" v-model="localFilters.team_member_id" @change="applyFilters"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Members</option>
                <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <input type="date" v-model="localFilters.date_from" @change="applyFilters"
                   class="rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="From" />
            <input type="date" v-model="localFilters.date_to" @change="applyFilters"
                   class="rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="To" />
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th v-if="isManager" class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Activity</th>
                        <th class="px-4 py-3">Feature</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">AI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="log in (logs.data || [])" :key="log.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600">{{ log.log_date }}</td>
                        <td v-if="isManager" class="px-4 py-3 font-medium text-gray-900">{{ log.team_member_name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ (log.activity_type || '').replace(/_/g, ' ') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ log.feature_title || '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ durationLabels[log.duration] || log.duration }}</td>
                        <td class="px-4 py-3">
                            <span :class="statusConfig[log.status]?.bg || 'bg-gray-100 text-gray-600'"
                                  class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                {{ statusConfig[log.status]?.label || log.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="log.ai_used" class="text-xs font-semibold text-purple-600">Yes</span>
                            <span v-else class="text-xs text-gray-400">—</span>
                        </td>
                    </tr>
                    <tr v-if="!(logs.data || []).length">
                        <td :colspan="isManager ? 7 : 6" class="px-4 py-8 text-center text-gray-400">No activity logs found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="logs.links" class="mt-4 flex gap-1">
            <template v-for="link in logs.links" :key="link.label">
                <a v-if="link.url" :href="link.url"
                   :class="link.active ? 'bg-[#5e16bd] text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                   class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium"
                   v-html="link.label" />
            </template>
        </div>
    </div>
</template>
