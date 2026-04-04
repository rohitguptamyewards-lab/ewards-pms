<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import StatsCard from '@/Components/StatsCard.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    member:     { type: Object, required: true },
    projects:   { type: Array, default: () => [] },
    tasks:      { type: Array, default: () => [] },
    recentLogs: { type: Array, default: () => [] },
    stats:      { type: Object, default: () => ({}) },
});

const page = usePage();
const authRole = computed(() => page.props.auth?.user?.role);
const canResend = computed(() => ['cto', 'ceo', 'manager'].includes(authRole.value));
const resending = ref(false);

function resendWelcome() {
    if (!confirm(`This will reset ${props.member.name}'s password and send a new welcome email. Continue?`)) return;
    resending.value = true;
    router.post(`/team-members/${props.member.id}/resend-welcome`, {}, {
        preserveScroll: true,
        onFinish: () => resending.value = false,
    });
}

const activeTab = ref('overview');
const tabs = [
    { key: 'overview',  label: 'Overview' },
    { key: 'tasks',     label: 'Tasks' },
    { key: 'worklogs',  label: 'Work Logs' },
    { key: 'projects',  label: 'Projects' },
];

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// BUG FIX: added manager and mc_team
const roleConfig = {
    cto:       { classes: 'bg-purple-100 text-purple-700', label: 'CTO' },
    ceo:       { classes: 'bg-[#e8ddf0] text-[#4e1a77]',     label: 'CEO' },
    manager:   { classes: 'bg-[#e8ddf0] text-[#4e1a77]',   label: 'Manager' },
    mc_team:   { classes: 'bg-violet-100 text-violet-700', label: 'MC Team' },
    developer: { classes: 'bg-green-100 text-green-700',   label: 'Developer' },
    tester:    { classes: 'bg-yellow-100 text-yellow-700', label: 'Tester' },
    analyst:   { classes: 'bg-orange-100 text-orange-700', label: 'Analyst' },
    sales:     { classes: 'bg-pink-100 text-pink-700',     label: 'Sales' },
};

function getRoleConfig(role) {
    return roleConfig[role] || { classes: 'bg-gray-100 text-gray-600', label: role };
}

function avatarColor(name) {
    const colors = ['bg-[#4e1a77]','bg-purple-500','bg-green-500','bg-indigo-500','bg-pink-500','bg-orange-500','bg-teal-500'];
    return colors[(name || '').charCodeAt(0) % colors.length];
}

function statusColor(status) {
    return { done: 'text-emerald-600', in_progress: 'text-[#4e1a77]', blocked: 'text-red-600' }[status] || 'text-gray-500';
}
</script>

<template>
    <Head :title="member.name" />

    <div class="mx-auto max-w-5xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/team-members" class="hover:text-gray-700">Team</Link>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-900 truncate">{{ member.name }}</span>
        </div>

        <!-- Profile header -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start gap-5">
                <div :class="avatarColor(member.name)" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full text-2xl font-bold text-white">
                    {{ (member.name || '?').charAt(0).toUpperCase() }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900">{{ member.name }}</h1>
                        <span :class="getRoleConfig(member.role).classes" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                            {{ getRoleConfig(member.role).label }}
                        </span>
                        <span :class="member.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                            {{ member.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ member.email }}</p>
                    <div class="mt-2 flex flex-wrap gap-4">
                        <span v-if="member.department" class="flex items-center gap-1 text-xs text-gray-400">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            {{ member.department }}
                        </span>
                        <span v-if="member.joining_date" class="flex items-center gap-1 text-xs text-gray-400">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Joined {{ formatDate(member.joining_date) }}
                        </span>
                        <span v-if="member.weekly_capacity" class="flex items-center gap-1 text-xs text-gray-400">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ member.weekly_capacity }}h/week
                        </span>
                    </div>
                </div>
                <!-- Resend Welcome Email button -->
                <button
                    v-if="canResend"
                    @click="resendWelcome"
                    :disabled="resending"
                    class="shrink-0 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 transition-colors disabled:opacity-50"
                >
                    <svg v-if="resending" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                    {{ resending ? 'Sending...' : 'Resend Welcome Email' }}
                </button>
            </div>
        </div>

        <!-- Flash messages -->
        <div v-if="$page.props.flash?.success" class="mt-3 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ $page.props.flash.error }}
        </div>

        <!-- Stats row -->
        <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-5">
            <StatsCard label="Total Hours"  :value="`${stats.totalHours ?? 0}h`"          color="blue"   />
            <StatsCard label="This Week"    :value="`${stats.thisWeekHours ?? 0}h`"        color="indigo" />
            <StatsCard label="Projects"     :value="stats.projectCount ?? 0"               color="gray"   />
            <StatsCard label="Open Tasks"   :value="stats.openTasksCount ?? 0"             color="yellow" />
            <StatsCard label="Blocked"      :value="stats.blockedTasksCount ?? 0"          color="red"    />
        </div>

        <!-- Tabs -->
        <div class="mt-6 border-b border-gray-200">
            <nav class="-mb-px flex gap-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="activeTab === tab.key
                        ? 'border-[#4e1a77] text-[#4e1a77] bg-[#f5f0ff]/50'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap border-b-2 px-4 pb-3 pt-2 text-sm font-medium transition-colors rounded-t-lg"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="mt-5 space-y-5">
            <!-- Active projects summary -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Active Projects</h3>
                <div v-if="projects.filter(p => p.status === 'active').length" class="space-y-2">
                    <div
                        v-for="p in projects.filter(p => p.status === 'active')"
                        :key="p.id"
                        class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 hover:bg-[#f5f0ff]/40 transition-colors"
                    >
                        <Link :href="`/projects/${p.id}`" class="text-sm font-medium text-[#4e1a77] hover:underline">{{ p.name }}</Link>
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span class="font-medium text-gray-700">{{ p.my_hours ?? 0 }}h logged</span>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ p.progress ?? 0 }}%</span>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-lg border border-dashed border-gray-200 py-8 text-center">
                    <p class="text-sm text-gray-400">No active projects.</p>
                </div>
            </div>

            <!-- Open & blocked tasks -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Open Tasks</h3>
                <div v-if="tasks.length" class="space-y-2">
                    <div
                        v-for="task in tasks.slice(0, 8)"
                        :key="task.id"
                        class="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <PriorityBadge :priority="task.priority" />
                            <Link :href="`/tasks/${task.id}`" class="text-sm text-[#4e1a77] hover:underline truncate">{{ task.title }}</Link>
                            <span class="hidden sm:inline text-xs text-gray-400 truncate">— {{ task.project_name }}</span>
                        </div>
                        <StatusBadge :status="task.status" type="task" />
                    </div>
                    <p v-if="tasks.length > 8" class="pt-1 text-center text-xs text-gray-400">
                        +{{ tasks.length - 8 }} more — see Tasks tab
                    </p>
                </div>
                <div v-else class="rounded-lg border border-dashed border-gray-200 py-8 text-center">
                    <p class="text-sm text-gray-400">No open tasks.</p>
                </div>
            </div>

            <!-- Recent logs -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Recent Work (Last 30 Days)</h3>
                <div v-if="recentLogs.length" class="space-y-1">
                    <div
                        v-for="log in recentLogs.slice(0, 8)"
                        :key="log.id"
                        class="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-gray-50 transition-colors text-sm"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="shrink-0 text-xs text-gray-400 tabular-nums">{{ formatDate(log.log_date) }}</span>
                            <span class="font-medium text-gray-700 truncate">{{ log.project_name }}</span>
                            <span v-if="log.task_title" class="hidden sm:inline text-xs text-gray-400 truncate">— {{ log.task_title }}</span>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span :class="statusColor(log.status)" class="text-xs font-medium capitalize">{{ (log.status || '').replace('_', ' ') }}</span>
                            <span class="font-semibold text-gray-800">{{ log.hours_spent }}h</span>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-lg border border-dashed border-gray-200 py-8 text-center">
                    <p class="text-sm text-gray-400">No work logs in the last 30 days.</p>
                </div>
            </div>
        </div>

        <!-- Tasks Tab -->
        <div v-if="activeTab === 'tasks'" class="mt-5">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Task</th>
                            <th class="px-5 py-3.5">Project</th>
                            <th class="px-5 py-3.5">Priority</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="task in tasks" :key="task.id" class="hover:bg-[#f5f0ff]/20 transition-colors">
                            <td class="px-5 py-3.5">
                                <Link :href="`/tasks/${task.id}`" class="font-semibold text-[#4e1a77] hover:underline">{{ task.title }}</Link>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">
                                <Link :href="`/projects/${task.project_id}`" class="hover:underline">{{ task.project_name }}</Link>
                            </td>
                            <td class="px-5 py-3.5"><PriorityBadge :priority="task.priority" /></td>
                            <td class="px-5 py-3.5"><StatusBadge :status="task.status" type="task" /></td>
                            <td class="px-5 py-3.5 text-gray-400">{{ formatDate(task.deadline) }}</td>
                        </tr>
                        <tr v-if="!tasks.length">
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400">No tasks assigned.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Work Logs Tab -->
        <div v-if="activeTab === 'worklogs'" class="mt-5">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900">Last 30 Days</h3>
                    <span class="text-sm text-gray-400">
                        {{ recentLogs.reduce((s, l) => s + parseFloat(l.hours_spent || 0), 0).toFixed(1) }}h total
                    </span>
                </div>
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Date</th>
                            <th class="px-5 py-3.5">Project</th>
                            <th class="px-5 py-3.5">Task</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Hours</th>
                            <th class="px-5 py-3.5">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-[#f5f0ff]/20 transition-colors">
                            <td class="px-5 py-3.5 text-gray-400 whitespace-nowrap tabular-nums">{{ formatDate(log.log_date) }}</td>
                            <td class="px-5 py-3.5 font-medium text-gray-900">{{ log.project_name }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ log.task_title || '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span :class="statusColor(log.status)" class="text-xs font-medium capitalize">{{ (log.status || '').replace('_', ' ') || '—' }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900">{{ log.hours_spent }}h</td>
                            <td class="px-5 py-3.5 text-gray-500 max-w-xs truncate">{{ log.note || '—' }}</td>
                        </tr>
                        <tr v-if="!recentLogs.length">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">No work logs in the last 30 days.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Projects Tab -->
        <div v-if="activeTab === 'projects'" class="mt-5">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Project</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Owner</th>
                            <th class="px-5 py-3.5 text-right">My Hours</th>
                            <th class="px-5 py-3.5 text-right">Progress</th>
                            <th class="px-5 py-3.5">End Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="project in projects" :key="project.id" class="hover:bg-[#f5f0ff]/20 transition-colors">
                            <td class="px-5 py-3.5">
                                <Link :href="`/projects/${project.id}`" class="font-semibold text-[#4e1a77] hover:underline">{{ project.name }}</Link>
                            </td>
                            <td class="px-5 py-3.5"><StatusBadge :status="project.status" type="project" /></td>
                            <td class="px-5 py-3.5 text-gray-600">{{ project.owner_name }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900">{{ project.my_hours ?? 0 }}h</td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="font-medium text-gray-700">{{ project.progress ?? 0 }}%</span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-400">{{ formatDate(project.end_date) }}</td>
                        </tr>
                        <tr v-if="!projects.length">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">Not assigned to any projects.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
