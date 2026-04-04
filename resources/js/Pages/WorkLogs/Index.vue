<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    workLogs:    { type: Object, default: () => ({ data: [], links: [] }) },
    filters:     { type: Object, default: () => ({}) },
    projects:    { type: Array,  default: () => [] },
    teamMembers: { type: Array,  default: () => [] },
    isManager:   { type: Boolean, default: false },
});

const localFilters = ref({
    project_id: props.filters.project_id || '',
    user_id:    props.filters.user_id    || '',
    date_from:  props.filters.date_from  || '',
    date_to:    props.filters.date_to    || '',
});

function applyFilters() {
    router.get('/work-logs', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    localFilters.value = { project_id: '', user_id: '', date_from: '', date_to: '' };
    router.get('/work-logs', {}, { preserveState: true, preserveScroll: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Work Logs" />

    <div>
        <div class="mb-4 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Work Logs</h1>
                <p class="mt-0.5 text-sm text-gray-500">Track time and progress</p>
            </div>
            <Link
                href="/work-logs/create"
                class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Log Work
            </Link>
        </div>

        <!-- Filters -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end">
                <!-- Project filter (all users) -->
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Project</label>
                    <select
                        v-model="localFilters.project_id"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none"
                    >
                        <option value="">All Projects</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>

                <!-- User filter (managers only) -->
                <div v-if="isManager">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Team Member</label>
                    <select
                        v-model="localFilters.user_id"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none"
                    >
                        <option value="">All Members</option>
                        <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Date From</label>
                    <input
                        v-model="localFilters.date_from"
                        type="date"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Date To</label>
                    <input
                        v-model="localFilters.date_to"
                        type="date"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none"
                    />
                </div>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                    <button @click="applyFilters" class="flex-1 rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors sm:flex-none">
                        Apply
                    </button>
                    <button @click="clearFilters" class="flex-1 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors sm:flex-none">
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div v-if="workLogs.data?.length" class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5 bg-gray-50">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                    {{ workLogs.data.length }} entries
                </p>
                <p class="text-sm font-semibold text-gray-700">
                    {{ workLogs.data.reduce((s, l) => s + parseFloat(l.hours_spent || 0), 0).toFixed(1) }}h total
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Date</th>
                            <th v-if="isManager" class="px-5 py-3.5">User</th>
                            <th class="px-5 py-3.5">Project</th>
                            <th class="px-5 py-3.5">Task</th>
                            <th class="px-5 py-3.5">Time</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Hours</th>
                            <th class="px-5 py-3.5">Note</th>
                            <th class="px-5 py-3.5">Blocker</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="log in workLogs.data"
                            :key="log.id"
                            class="group hover:bg-[#f5f0ff]/30 transition-colors"
                        >
                            <td class="px-5 py-3.5 text-gray-400 whitespace-nowrap tabular-nums">{{ formatDate(log.log_date) }}</td>
                            <td v-if="isManager" class="px-5 py-3.5 font-medium text-gray-700">{{ log.user_name || '—' }}</td>
                            <td class="px-5 py-3.5 font-medium text-gray-800">{{ log.project_name || '—' }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ log.task_title || '—' }}</td>
                            <td class="px-5 py-3.5 text-gray-500 whitespace-nowrap tabular-nums text-xs">
                                <template v-if="log.start_time && log.end_time">
                                    {{ log.start_time?.slice(0,5) }} – {{ log.end_time?.slice(0,5) }}
                                </template>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span :class="{
                                    'bg-emerald-100 text-emerald-700': log.status === 'done',
                                    'bg-[#e8ddf0] text-[#4e1a77]': log.status === 'in_progress',
                                    'bg-red-100 text-red-700': log.status === 'blocked',
                                }" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize">
                                    {{ (log.status || '').replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900">{{ log.hours_spent }}h</td>
                            <td class="px-5 py-3.5 text-gray-600 max-w-xs truncate">{{ log.note || '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span v-if="log.blocker" class="inline-flex items-center gap-1 text-xs font-medium text-red-600">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                    {{ log.blocker }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                        </tr>
                        <tr v-if="!workLogs.data?.length">
                            <td :colspan="isManager ? 9 : 8" class="px-5 py-12 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-400">No work logs found.</p>
                                <Link href="/work-logs/create" class="mt-2 inline-block text-sm text-[#4e1a77] hover:underline">Log your first entry →</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="workLogs.links?.length > 3" class="flex items-center justify-center gap-1 border-t border-gray-100 bg-gray-50 px-5 py-3">
                <Link
                    v-for="link in workLogs.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        link.active ? 'bg-[#4e1a77] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200',
                        !link.url ? 'pointer-events-none opacity-40' : '',
                    ]"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    v-html="link.label"
                    preserve-scroll
                />
            </div>
        </div>
    </div>
</template>
