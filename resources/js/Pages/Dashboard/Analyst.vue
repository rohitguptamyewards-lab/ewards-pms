<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    todaysLogs:          { type: Array,  default: () => [] },
    myTasks:             { type: Array,  default: () => [] },
    myProjects:          { type: Array,  default: () => [] },
    weeklyHours:         { type: Number, default: 0 },
    specQueue:           { type: Array,  default: () => [] },
    specQualityMetrics:  { type: Object, default: () => ({}) },
    docCoverage:         { type: Object, default: () => ({}) },
    testCoverage:        { type: Object, default: () => ({}) },
});

const openTasks  = computed(() => props.myTasks.filter(t => t.status !== 'done'));
const todayHours = computed(() => props.todaysLogs.reduce((s, l) => s + parseFloat(l.hours_spent || 0), 0).toFixed(1));

function isOverdue(deadline) {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

const REPORTS = [
    { label: 'Work Log Report',    href: '/reports/work-logs',  desc: 'Team time entries',  icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { label: 'Project Report',     href: '/reports/projects',   desc: 'Status & progress',  icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' },
    { label: 'Individual Report',  href: '/reports/individual', desc: 'Personal breakdown', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
];

function priorityBadgeClass(p) {
    return { p0: 'bg-red-100 text-red-700', p1: 'bg-orange-100 text-orange-700', p2: 'bg-yellow-100 text-yellow-700', p3: 'bg-gray-100 text-gray-600' }[p] || 'bg-gray-100 text-gray-600';
}
</script>

<template>
    <Head title="Analyst Dashboard" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Analyst Dashboard</h1>
                <p class="mt-0.5 text-sm text-gray-500">Your work summary, spec queue & quality metrics</p>
            </div>
            <Link href="/work-logs/create" class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Log Work
            </Link>
        </div>

        <!-- Stats -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <StatsCard label="7-Day Hours"     :value="(weeklyHours || 0).toFixed(1) + 'h'" color="blue"   />
            <StatsCard label="Today's Hours"   :value="todayHours + 'h'"                     color="indigo" />
            <StatsCard label="Open Tasks"      :value="openTasks.length"                     color="yellow" />
            <StatsCard label="Active Projects" :value="myProjects.length"                    color="green"  />
        </div>

        <!-- Item 55/56/57: Quality Metrics Row -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- Spec Quality -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Spec Quality</p>
                <div class="mt-2 flex items-end justify-between">
                    <p class="text-3xl font-bold text-[#4e1a77]">{{ specQualityMetrics.freeze_to_dev_ratio ?? 0 }}%</p>
                    <p class="text-xs text-gray-400 text-right">Freeze-to-dev<br>ratio</p>
                </div>
                <p class="mt-1 text-xs text-gray-400">{{ specQualityMetrics.frozen_specs ?? 0 }} / {{ specQualityMetrics.total_specs ?? 0 }} specs frozen</p>
            </div>

            <!-- Item 56: Doc Coverage -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Documentation Coverage</p>
                <div class="mt-2 flex items-end justify-between">
                    <p class="text-3xl font-bold" :class="docCoverage.coverage_pct >= 70 ? 'text-green-600' : docCoverage.coverage_pct >= 40 ? 'text-yellow-600' : 'text-red-600'">
                        {{ docCoverage.coverage_pct ?? 0 }}%
                    </p>
                    <p class="text-xs text-gray-400 text-right">Features<br>documented</p>
                </div>
                <div class="mt-2">
                    <ProgressBar :percentage="docCoverage.coverage_pct || 0" />
                </div>
                <p class="mt-1 text-xs text-gray-400">{{ docCoverage.features_with_docs ?? 0 }} / {{ docCoverage.total_features ?? 0 }} features</p>
            </div>

            <!-- Item 57: Test Scenario Coverage -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Test Scenario Coverage</p>
                <div class="mt-2 flex items-end justify-between">
                    <p class="text-3xl font-bold" :class="testCoverage.coverage_pct >= 70 ? 'text-green-600' : testCoverage.coverage_pct >= 40 ? 'text-yellow-600' : 'text-red-600'">
                        {{ testCoverage.coverage_pct ?? 0 }}%
                    </p>
                    <p class="text-xs text-gray-400 text-right">Features<br>with specs</p>
                </div>
                <div class="mt-2">
                    <ProgressBar :percentage="testCoverage.coverage_pct || 0" />
                </div>
                <p class="mt-1 text-xs text-gray-400">{{ testCoverage.features_with_tests ?? 0 }} / {{ testCoverage.total_features ?? 0 }} features</p>
            </div>
        </div>

        <!-- Quick Report Links -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Link
                v-for="report in REPORTS"
                :key="report.href"
                :href="report.href"
                class="group flex items-center gap-4 rounded-xl border border-[#ddd0f7] bg-[#f5f0ff] p-4 shadow-sm hover:bg-[#e8ddf0] transition-colors"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#4e1a77] text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="report.icon" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-[#361963]">{{ report.label }}</p>
                    <p class="text-xs text-[#4e1a77]/70">{{ report.desc }}</p>
                </div>
                <svg class="ml-auto h-4 w-4 text-[#4e1a77]/40 group-hover:text-[#4e1a77] transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </Link>
        </div>

        <!-- Item 54: Spec Writing Queue -->
        <div v-if="specQueue.length" class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-[#4e1a77]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h2 class="font-semibold text-gray-900">Spec Writing Queue</h2>
                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">{{ specQueue.length }} features need specs</span>
                </div>
                <Link href="/features" class="text-xs font-medium text-[#4e1a77] hover:underline">View all →</Link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-50 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3 text-left">Feature</th>
                            <th class="px-5 py-3 text-left hidden sm:table-cell">Module</th>
                            <th class="px-5 py-3 text-left">Priority</th>
                            <th class="px-5 py-3 text-left hidden md:table-cell">Deadline</th>
                            <th class="px-5 py-3 text-left hidden md:table-cell">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="feat in specQueue" :key="feat.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3">
                                <Link :href="`/features/${feat.id}`" class="font-medium text-[#4e1a77] hover:underline">{{ feat.title }}</Link>
                            </td>
                            <td class="px-5 py-3 hidden sm:table-cell text-gray-500">{{ feat.module_name || '—' }}</td>
                            <td class="px-5 py-3">
                                <span :class="priorityBadgeClass(feat.priority)" class="rounded-full px-2 py-0.5 text-xs font-semibold uppercase">{{ feat.priority || '—' }}</span>
                            </td>
                            <td class="px-5 py-3 hidden md:table-cell text-xs" :class="isOverdue(feat.deadline) ? 'text-red-500 font-semibold' : 'text-gray-400'">
                                {{ formatDate(feat.deadline) }}
                            </td>
                            <td class="px-5 py-3 hidden md:table-cell">
                                <StatusBadge :status="feat.status" type="feature" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

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
                    <span v-if="todaysLogs.length" class="text-sm font-medium text-gray-500">{{ todayHours }}h</span>
                </div>
                <div v-if="todaysLogs.length" class="divide-y divide-gray-50">
                    <div v-for="log in todaysLogs" :key="log.id" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ log.project_name || '—' }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ log.task_title || 'No task' }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-bold text-gray-900">{{ log.hours_spent }}h</p>
                    </div>
                </div>
                <div v-else class="px-5 py-10 text-center">
                    <p class="text-sm text-gray-400">No logs for today.</p>
                    <Link href="/work-logs/create" class="mt-2 inline-block text-sm text-[#4e1a77] hover:underline">Log your first entry →</Link>
                </div>
            </div>

            <!-- My Tasks -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h2 class="font-semibold text-gray-900">My Open Tasks</h2>
                </div>
                <div v-if="openTasks.length" class="divide-y divide-gray-50">
                    <div v-for="task in openTasks" :key="task.id" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50">
                        <div class="min-w-0 flex-1">
                            <Link :href="`/tasks/${task.id}`" class="font-medium text-[#4e1a77] hover:underline truncate block">{{ task.title }}</Link>
                            <p class="text-xs text-gray-400">{{ task.project_name || '—' }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <StatusBadge :status="task.status" type="task" />
                            <span :class="isOverdue(task.deadline) ? 'text-red-500 font-semibold' : 'text-gray-400'" class="text-xs">{{ formatDate(task.deadline) }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-10 text-center">
                    <p class="text-sm text-gray-400">No open tasks.</p>
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
                <div v-for="project in myProjects" :key="project.id" class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50">
                    <Link :href="`/projects/${project.id}`" class="w-40 shrink-0 font-medium text-[#4e1a77] hover:underline truncate">{{ project.name }}</Link>
                    <div class="min-w-0 flex-1"><ProgressBar :percentage="project.progress || 0" /></div>
                    <span class="shrink-0 text-xs font-semibold text-gray-600">{{ project.progress ?? 0 }}%</span>
                </div>
            </div>
            <div v-else class="px-5 py-10 text-center">
                <p class="text-sm text-gray-400">No projects assigned.</p>
            </div>
        </div>
    </div>
</template>
