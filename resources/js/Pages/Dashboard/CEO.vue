<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    featurePipeline: { type: Object, default: () => ({}) },
    requestPipeline: { type: Object, default: () => ({}) },
    activeProjects:  { type: Number, default: 0 },
    teamSize:        { type: Number, default: 0 },
    hoursThisMonth:  { type: Number, default: 0 },
});

const FEATURE_STAGES = [
    { key: 'backlog',            label: 'Backlog',             color: '#94a3b8' },
    { key: 'in_progress',        label: 'In Progress',         color: '#4e1a77' },
    { key: 'in_review',          label: 'In Review',           color: '#8b5cf6' },
    { key: 'in_qa',              label: 'In QA',               color: '#f59e0b' },
    { key: 'ready_for_release',  label: 'Ready for Release',   color: '#10b981' },
    { key: 'released',           label: 'Released',            color: '#16a34a' },
];

const REQUEST_STAGES = [
    { key: 'received',     label: 'Received',     color: '#64748b' },
    { key: 'under_review', label: 'Under Review', color: '#4e1a77' },
    { key: 'accepted',     label: 'Accepted',     color: '#10b981' },
    { key: 'deferred',     label: 'Deferred',     color: '#f59e0b' },
    { key: 'rejected',     label: 'Rejected',     color: '#ef4444' },
    { key: 'completed',    label: 'Completed',    color: '#16a34a' },
];

const totalFeatures = () => Object.values(props.featurePipeline).reduce((a, b) => a + b, 0);
const totalRequests = () => Object.values(props.requestPipeline).reduce((a, b) => a + b, 0);
const featuresInProgress = () => (props.featurePipeline.in_progress ?? 0) + (props.featurePipeline.in_review ?? 0) + (props.featurePipeline.in_qa ?? 0);
const featuresReleased = () => props.featurePipeline.released ?? 0;
</script>

<template>
    <Head title="CEO Dashboard" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Business Overview</h1>
                <p class="mt-0.5 text-sm text-gray-500">Product pipeline & team performance at a glance</p>
            </div>
            <div class="flex gap-3">
                <Link href="/features" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 transition-colors">
                    Feature Pipeline
                </Link>
                <Link href="/requests" class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors">
                    All Requests
                </Link>
            </div>
        </div>

        <!-- Key metrics -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
            <StatsCard label="Active Projects"   :value="activeProjects"          color="blue"    />
            <StatsCard label="Team Members"      :value="teamSize"                color="indigo"  />
            <StatsCard label="Total Features"    :value="totalFeatures()"         color="purple"  />
            <StatsCard label="Actively Building" :value="featuresInProgress()"    color="yellow"  />
            <StatsCard label="Released"          :value="featuresReleased()"      color="emerald" />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Feature Pipeline -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-[#4e1a77]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <h2 class="font-semibold text-gray-900">Feature Pipeline</h2>
                    </div>
                    <span class="rounded-full bg-[#e8ddf0] px-2.5 py-0.5 text-xs font-semibold text-[#4e1a77]">{{ totalFeatures() }} total</span>
                </div>
                <div class="divide-y divide-gray-50 p-2">
                    <div v-for="stage in FEATURE_STAGES" :key="stage.key" class="flex items-center justify-between rounded-lg px-4 py-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-block h-2.5 w-2.5 rounded-full flex-shrink-0" :style="{ background: stage.color }"></span>
                            <span class="text-sm text-gray-700">{{ stage.label }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :style="{
                                        width: totalFeatures() ? ((featurePipeline[stage.key] ?? 0) / totalFeatures() * 100) + '%' : '0%',
                                        background: stage.color
                                    }"
                                ></div>
                            </div>
                            <span class="w-8 text-right text-sm font-semibold text-gray-900">{{ featurePipeline[stage.key] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-3 bg-gray-50">
                    <Link href="/features" class="text-xs font-medium text-[#4e1a77] hover:underline">View full pipeline →</Link>
                </div>
            </div>

            <!-- Request Pipeline -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-[#4e1a77]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                        </svg>
                        <h2 class="font-semibold text-gray-900">Request Pipeline</h2>
                    </div>
                    <span class="rounded-full bg-[#e8ddf0] px-2.5 py-0.5 text-xs font-semibold text-[#4e1a77]">{{ totalRequests() }} total</span>
                </div>
                <div class="divide-y divide-gray-50 p-2">
                    <div v-for="stage in REQUEST_STAGES" :key="stage.key" class="flex items-center justify-between rounded-lg px-4 py-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-block h-2.5 w-2.5 rounded-full flex-shrink-0" :style="{ background: stage.color }"></span>
                            <span class="text-sm text-gray-700">{{ stage.label }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :style="{
                                        width: totalRequests() ? ((requestPipeline[stage.key] ?? 0) / totalRequests() * 100) + '%' : '0%',
                                        background: stage.color
                                    }"
                                ></div>
                            </div>
                            <span class="w-8 text-right text-sm font-semibold text-gray-900">{{ requestPipeline[stage.key] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-3 bg-gray-50">
                    <Link href="/requests" class="text-xs font-medium text-[#4e1a77] hover:underline">View all requests →</Link>
                </div>
            </div>
        </div>

        <!-- Hours this month summary -->
        <div class="mt-6 rounded-xl border border-[#ddd0f7] bg-gradient-to-br from-[#f5f0ff] to-[#e8ddf0] p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#4e1a77]">Team Hours This Month</p>
                    <p class="mt-1 text-4xl font-extrabold text-[#361963]">{{ hoursThisMonth }}h</p>
                    <p class="mt-1 text-sm text-[#4e1a77]/70">Logged across all {{ teamSize }} team members</p>
                </div>
                <Link href="/reports/work-logs" class="rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors">
                    View Full Report
                </Link>
            </div>
        </div>
    </div>
</template>
