<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    period:           { type: String, default: '30' },
    role:             { type: String, default: '' },
    // Developer fields
    tasksCompleted:   { type: Number, default: 0 },
    hoursLogged:      { type: [Number, String], default: 0 },
    featuresWorked:   { type: Number, default: 0 },
    statusBreakdown:  { type: Array, default: () => [] },
    recentTasks:      { type: Array, default: () => [] },
    // Tester fields
    bugsReported:     { type: Number, default: 0 },
    featuresQAd:      { type: Number, default: 0 },
    recentBugs:       { type: Array, default: () => [] },
    // Analyst fields
    requestsCreated:  { type: Number, default: 0 },
    requestsLinked:   { type: Number, default: 0 },
    recentRequests:   { type: Array, default: () => [] },
    // Manager fields
    teamSize:         { type: Number, default: 0 },
    tasksCompletedTeam: { type: Number, default: 0 },
    totalHours:       { type: [Number, String], default: 0 },
    topContributors:  { type: Array, default: () => [] },
});

const selectedPeriod = ref(props.period);

function changePeriod() {
    router.get('/personal/history', { period: selectedPeriod.value }, { preserveState: true });
}

const roleTitle = {
    developer: 'Developer Dashboard',
    tester:    'Tester Dashboard',
    analyst:   'Analyst Dashboard',
};

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
}
</script>

<template>
    <Head :title="roleTitle[role] || 'My Dashboard'" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ roleTitle[role] || 'My Dashboard' }}</h1>
                <p class="mt-0.5 text-sm text-gray-500">Your activity over the last {{ period }} days</p>
            </div>
            <select v-model="selectedPeriod" @change="changePeriod"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]">
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
            </select>
        </div>

        <!-- Developer view -->
        <template v-if="role === 'developer'">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Tasks Completed</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ tasksCompleted }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Hours Logged</p>
                    <p class="mt-1 text-3xl font-bold text-[#4e1a77]">{{ Number(hoursLogged).toFixed(1) }}h</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Features Worked</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ featuresWorked }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3.5">
                    <h2 class="text-sm font-bold text-gray-900">Recent Tasks</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="task in recentTasks" :key="task.id" class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ task.title }}</p>
                            <p v-if="task.feature_title" class="text-xs text-gray-400">{{ task.feature_title }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 capitalize">{{ (task.status || '').replace('_', ' ') }}</span>
                    </div>
                </div>
            </div>
        </template>

        <!-- Tester view -->
        <template v-else-if="role === 'tester'">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Bugs Reported</p>
                    <p class="mt-1 text-3xl font-bold text-red-600">{{ bugsReported }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Features QA'd</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ featuresQAd }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Hours Logged</p>
                    <p class="mt-1 text-3xl font-bold text-[#4e1a77]">{{ Number(hoursLogged).toFixed(1) }}h</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3.5">
                    <h2 class="text-sm font-bold text-gray-900">Recent Bugs Reported</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="bug in recentBugs" :key="bug.id" class="flex items-center justify-between px-5 py-3">
                        <p class="text-sm font-medium text-gray-800">{{ bug.title }}</p>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 capitalize">{{ (bug.status || '').replace('_', ' ') }}</span>
                    </div>
                </div>
            </div>
        </template>

        <!-- Analyst view -->
        <template v-else-if="role === 'analyst'">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Requests Created</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ requestsCreated }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Requests Linked</p>
                    <p class="mt-1 text-3xl font-bold text-blue-600">{{ requestsLinked }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Hours Logged</p>
                    <p class="mt-1 text-3xl font-bold text-[#4e1a77]">{{ Number(hoursLogged).toFixed(1) }}h</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3.5">
                    <h2 class="text-sm font-bold text-gray-900">Recent Requests</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="req in recentRequests" :key="req.id" class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ req.title }}</p>
                            <p class="text-xs text-gray-400">{{ req.merchant_name || '' }} &middot; demand: {{ req.demand_count ?? 0 }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 capitalize">{{ (req.status || '').replace('_', ' ') }}</span>
                    </div>
                </div>
            </div>
        </template>

        <!-- Manager overview -->
        <template v-else>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Team Size</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ teamSize }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Tasks Completed</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-600">{{ tasksCompletedTeam }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-gray-400">Total Hours</p>
                    <p class="mt-1 text-3xl font-bold text-[#4e1a77]">{{ Number(totalHours).toFixed(1) }}h</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3.5">
                    <h2 class="text-sm font-bold text-gray-900">Top Contributors</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="(c, i) in topContributors" :key="i" class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#e8ddf0] text-xs font-bold text-[#4e1a77]">{{ i + 1 }}</span>
                            <span class="text-sm font-medium text-gray-800">{{ c.name }}</span>
                        </div>
                        <span class="text-sm font-bold text-gray-600">{{ Number(c.total_hours).toFixed(1) }}h</span>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
