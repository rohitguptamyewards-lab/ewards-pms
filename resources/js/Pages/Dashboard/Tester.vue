<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    todaysLogs:   { type: Array,  default: () => [] },
    myTasks:      { type: Array,  default: () => [] },
    myProjects:   { type: Array,  default: () => [] },
    weeklyHours:  { type: Number, default: 0 },
});

const blockedTasks   = computed(() => props.myTasks.filter(t => t.status === 'blocked'));
const openTasks      = computed(() => props.myTasks.filter(t => t.status !== 'done'));
const todayHours     = computed(() => props.todaysLogs.reduce((s, l) => s + parseFloat(l.hours_spent || 0), 0).toFixed(1));

function isOverdue(deadline) {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<template>
    <Head title="QA Dashboard" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">QA Dashboard</h1>
                <p class="mt-0.5 text-sm text-gray-500">Testing queue & blocked tasks</p>
            </div>
            <Link
                href="/work-logs/create"
                class="inline-flex items-center gap-2 rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#4c12a1] transition-colors"
            >
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
            <StatsCard label="Blocked Tasks"   :value="blockedTasks.length"                  color="red"    />
            <StatsCard label="Open Tasks"      :value="openTasks.length"                     color="yellow" />
        </div>

        <!-- Blocked tasks — top priority for QA -->
        <div class="mb-6 overflow-hidden rounded-xl border border-red-100 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-red-100 bg-red-50 px-5 py-4">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <h2 class="font-semibold text-red-800">Blocked Tasks — Needs QA Attention</h2>
                <span class="ml-auto rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">{{ blockedTasks.length }}</span>
            </div>
            <div v-if="blockedTasks.length" class="divide-y divide-gray-50">
                <div v-for="task in blockedTasks" :key="task.id" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition-colors">
                    <div class="min-w-0 flex-1">
                        <Link :href="`/tasks/${task.id}`" class="font-medium text-[#5e16bd] hover:underline truncate block">
                            {{ task.title }}
                        </Link>
                        <p class="text-xs text-gray-400">{{ task.project_name || '—' }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <PriorityBadge :priority="task.priority" />
                        <span :class="isOverdue(task.deadline) ? 'text-red-500 font-semibold' : 'text-gray-400'" class="text-xs">
                            {{ formatDate(task.deadline) }}
                        </span>
                    </div>
                </div>
            </div>
            <div v-else class="px-5 py-10 text-center">
                <svg class="mx-auto h-8 w-8 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-sm text-gray-400">No blocked tasks. All clear!</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- My Open Tasks -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <h2 class="font-semibold text-gray-900">My Open Tasks</h2>
                </div>
                <div v-if="openTasks.length" class="divide-y divide-gray-50">
                    <div v-for="task in openTasks" :key="task.id" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition-colors">
                        <div class="min-w-0 flex-1">
                            <Link :href="`/tasks/${task.id}`" class="font-medium text-[#5e16bd] hover:underline truncate block">{{ task.title }}</Link>
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
                    <Link href="/work-logs/create" class="mt-2 inline-block text-sm text-[#5e16bd] hover:underline">Log your first entry →</Link>
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
                    <Link :href="`/projects/${project.id}`" class="w-40 shrink-0 font-medium text-[#5e16bd] hover:underline truncate">{{ project.name }}</Link>
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
