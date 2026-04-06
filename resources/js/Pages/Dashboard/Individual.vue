<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

defineOptions({ layout: AppLayout });

defineProps({
    todaysLogs:           { type: Array,  default: () => [] },
    myTasks:              { type: Array,  default: () => [] },
    myProjects:           { type: Array,  default: () => [] },
    weeklyHours:          { type: Number, default: 0 },
    contextSwitchWarning: { type: String, default: null },
    featuresThisWeek:     { type: Number, default: 0 },
    estimationAccuracy:   { type: Object, default: null },
    sprintKanban:         { type: Array,  default: () => [] },
    sprintCommitment:     { type: Object, default: null },
    activeSprint:         { type: Object, default: null },
    isNewHire:            { type: Boolean, default: false },
    onboardingStatus:     { type: Object, default: null },
});

function isOverdue(deadline) {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

const KANBAN_COLUMNS = [
    { key: 'backlog',           label: 'Backlog',        color: 'text-gray-500' },
    { key: 'in_progress',       label: 'In Progress',    color: 'text-purple-600' },
    { key: 'in_review',         label: 'In Review',      color: 'text-blue-600' },
    { key: 'in_qa',             label: 'In QA',          color: 'text-yellow-600' },
    { key: 'ready_for_release', label: 'Ready',          color: 'text-green-600' },
];
</script>

<template>
    <Head title="Dashboard" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Dashboard</h1>
                <p class="mt-0.5 text-sm text-gray-500">Your work summary</p>
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

        <!-- Item 49: Onboarding banner for new hires -->
        <div v-if="isNewHire && onboardingStatus" class="mb-5 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="text-sm font-semibold text-blue-800">Welcome! You're in week {{ onboardingStatus.weeks_since_joining + 1 }} of onboarding.</p>
                <p class="mt-0.5 text-xs text-blue-600">Onboarding status: {{ onboardingStatus.status?.replace(/_/g, ' ') }}</p>
                <Link href="/onboarding" class="mt-1 inline-block text-xs font-medium text-blue-700 hover:underline">View onboarding checklist →</Link>
            </div>
        </div>

        <!-- Item 44: Context-switching warning -->
        <div v-if="contextSwitchWarning" class="mb-5 flex items-center gap-3 rounded-xl border border-orange-200 bg-orange-50 px-5 py-4">
            <svg class="h-5 w-5 shrink-0 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <p class="text-sm text-orange-800"><span class="font-semibold">Context switching detected.</span> {{ contextSwitchWarning }}</p>
        </div>

        <!-- Stats row -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <StatsCard label="7-Day Hours"      :value="(weeklyHours || 0).toFixed(1) + 'h'" color="blue"   />
            <StatsCard label="Today's Logs"     :value="todaysLogs.length"                    color="indigo" />
            <StatsCard label="Open Tasks"       :value="myTasks.filter(t => t.status !== 'done').length" color="yellow" />
            <StatsCard label="Active Projects"  :value="myProjects.length"                    color="green"  />
        </div>

        <!-- Item 45: Estimation accuracy + Item 48: Sprint commitment -->
        <div v-if="estimationAccuracy || sprintCommitment" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- Estimation Accuracy -->
            <div v-if="estimationAccuracy" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Estimation Accuracy</p>
                <p class="mt-1 text-3xl font-bold" :class="estimationAccuracy.avg_pct >= 80 ? 'text-green-600' : estimationAccuracy.avg_pct >= 60 ? 'text-yellow-600' : 'text-red-600'">
                    {{ estimationAccuracy.avg_pct }}%
                </p>
                <p class="mt-1 text-xs text-gray-400">Based on {{ estimationAccuracy.sample_count }} completed assignment(s)</p>
            </div>

            <!-- Sprint Commitment -->
            <div v-if="sprintCommitment" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Sprint Commitment</p>
                <p class="mt-1 text-sm font-semibold text-gray-700">{{ sprintCommitment.sprint_name }}</p>
                <div class="mt-2 flex items-center gap-2">
                    <ProgressBar :percentage="sprintCommitment.completion_rate" />
                    <span class="shrink-0 text-xs font-bold text-gray-600">{{ sprintCommitment.completion_rate }}%</span>
                </div>
                <p class="mt-1 text-xs text-gray-400">{{ sprintCommitment.completed }} / {{ sprintCommitment.total_committed }} features done</p>
                <p v-if="sprintCommitment.end_date" class="text-xs text-gray-400">Ends: {{ formatDate(sprintCommitment.end_date) }}</p>
            </div>
        </div>

        <!-- Grid: Logs + Tasks -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Today's Logs -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h2 class="font-semibold text-gray-900">Today's Logs</h2>
                    </div>
                    <span v-if="todaysLogs.length" class="text-sm font-medium text-gray-500">
                        {{ todaysLogs.reduce((s, l) => s + parseFloat(l.hours_spent || l.hours || 0), 0).toFixed(1) }}h
                    </span>
                </div>
                <div v-if="todaysLogs.length" class="divide-y divide-gray-50">
                    <div v-for="log in todaysLogs" :key="log.id" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ log.project_name || log.project?.name || '—' }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ log.task_title || log.task?.title || 'No task' }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-bold text-gray-900">{{ log.hours_spent || log.hours }}h</p>
                            <p v-if="log.note" class="max-w-[120px] truncate text-xs text-gray-400">{{ log.note }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-12 text-center">
                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-gray-400">No logs for today.</p>
                    <Link href="/work-logs/create" class="mt-2 inline-block text-sm text-[#4e1a77] hover:underline">Log your first entry →</Link>
                </div>
            </div>

            <!-- My Open Tasks -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h2 class="font-semibold text-gray-900">My Open Tasks</h2>
                </div>
                <div v-if="myTasks.length" class="divide-y divide-gray-50">
                    <div v-for="task in myTasks" :key="task.id" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition-colors">
                        <div class="min-w-0 flex-1">
                            <Link :href="`/tasks/${task.id}`" class="font-medium text-[#4e1a77] hover:underline truncate block">
                                {{ task.title }}
                            </Link>
                            <p class="text-xs text-gray-400">{{ task.project_name || task.project?.name || '—' }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <StatusBadge :status="task.status" type="task" />
                            <span :class="isOverdue(task.deadline) ? 'text-red-500 font-semibold' : 'text-gray-400'" class="text-xs">
                                {{ formatDate(task.deadline) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-12 text-center">
                    <p class="text-sm text-gray-400">All caught up! No open tasks.</p>
                </div>
            </div>
        </div>

        <!-- Item 47: Personal Kanban (Sprint Features) -->
        <div v-if="activeSprint && sprintKanban.length" class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-[#4e1a77]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg>
                    <h2 class="font-semibold text-gray-900">My Sprint Kanban</h2>
                </div>
                <span class="rounded-full bg-[#e8ddf0] px-2.5 py-0.5 text-xs font-semibold text-[#4e1a77]">
                    {{ activeSprint.name }}
                </span>
            </div>
            <div class="overflow-x-auto p-4">
                <div class="flex gap-4" style="min-width: 640px;">
                    <div v-for="col in KANBAN_COLUMNS" :key="col.key" class="min-w-[160px] flex-1">
                        <p :class="['mb-2 text-xs font-bold uppercase tracking-wide', col.color]">{{ col.label }}</p>
                        <div class="space-y-2">
                            <div
                                v-for="feat in sprintKanban.filter(f => f.status === col.key)"
                                :key="feat.id"
                                class="rounded-lg border border-gray-100 bg-gray-50 p-3 hover:bg-white transition-colors"
                            >
                                <Link :href="`/features/${feat.id}`" class="text-xs font-medium text-[#4e1a77] hover:underline line-clamp-2">
                                    {{ feat.title }}
                                </Link>
                                <p v-if="feat.estimated_hours" class="mt-1 text-xs text-gray-400">{{ feat.estimated_hours }}h est.</p>
                            </div>
                            <p v-if="!sprintKanban.filter(f => f.status === col.key).length" class="text-xs italic text-gray-300">Empty</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Projects -->
        <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                <h2 class="font-semibold text-gray-900">My Projects</h2>
            </div>
            <div v-if="myProjects.length" class="divide-y divide-gray-50">
                <div v-for="project in myProjects" :key="project.id" class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition-colors">
                    <Link :href="`/projects/${project.id}`" class="w-40 shrink-0 font-medium text-[#4e1a77] hover:underline truncate">
                        {{ project.name }}
                    </Link>
                    <span class="shrink-0 text-xs font-medium text-gray-500">{{ project.my_hours ?? 0 }}h</span>
                    <div class="min-w-0 flex-1">
                        <ProgressBar :percentage="project.progress || 0" />
                    </div>
                    <span class="shrink-0 text-xs font-semibold text-gray-600">{{ project.progress ?? 0 }}%</span>
                </div>
            </div>
            <div v-else class="px-5 py-12 text-center">
                <p class="text-sm text-gray-400">No projects assigned.</p>
            </div>
        </div>
    </div>
</template>
