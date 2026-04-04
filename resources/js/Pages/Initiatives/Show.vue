<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    initiative: { type: Object, required: true },
    isManager:  { type: Boolean, default: false },
});

const page = usePage();
const flash = computed(() => page.props.flash);

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

const statusConfig = {
    planning:  { label: 'Planning',  bg: 'bg-gray-100 text-gray-700' },
    active:    { label: 'Active',    bg: 'bg-emerald-100 text-emerald-700' },
    at_risk:   { label: 'At Risk',   bg: 'bg-orange-100 text-orange-700' },
    completed: { label: 'Completed', bg: 'bg-teal-100 text-teal-700' },
    archived:  { label: 'Archived',  bg: 'bg-gray-100 text-gray-500' },
};

const featureStatusConfig = {
    backlog:            { label: 'Backlog',            bg: 'bg-gray-100 text-gray-600' },
    in_progress:        { label: 'In Progress',        bg: 'bg-blue-100 text-blue-700' },
    in_review:          { label: 'In Review',          bg: 'bg-purple-100 text-purple-700' },
    ready_for_qa:       { label: 'Ready for QA',       bg: 'bg-indigo-100 text-indigo-700' },
    in_qa:              { label: 'In QA',              bg: 'bg-yellow-100 text-yellow-700' },
    ready_for_release:  { label: 'Ready for Release',  bg: 'bg-teal-100 text-teal-700' },
    released:           { label: 'Released',           bg: 'bg-emerald-100 text-emerald-700' },
    deferred:           { label: 'Deferred',           bg: 'bg-orange-100 text-orange-700' },
    rejected:           { label: 'Rejected',           bg: 'bg-red-100 text-red-700' },
};

const priorityConfig = {
    p0: { label: 'P0', color: 'text-red-600 bg-red-50 ring-red-200' },
    p1: { label: 'P1', color: 'text-orange-600 bg-orange-50 ring-orange-200' },
    p2: { label: 'P2', color: 'text-yellow-600 bg-yellow-50 ring-yellow-200' },
    p3: { label: 'P3', color: 'text-gray-500 bg-gray-50 ring-gray-200' },
};

const ini = computed(() => props.initiative);
</script>

<template>
    <Head :title="ini.title" />

    <div>
        <!-- Breadcrumb -->
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/initiatives" class="hover:text-[#4e1a77]">Initiatives</Link>
            <span>/</span>
            <span class="text-gray-800 font-medium">{{ ini.title }}</span>
        </div>

        <!-- Flash -->
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ flash.success }}
        </div>

        <!-- Header -->
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ ini.title }}</h1>
                <p class="mt-1 text-sm text-gray-500">Owned by {{ ini.owner_name || '—' }}</p>
            </div>
            <Link v-if="isManager" :href="`/initiatives/${ini.id}`" method="get"
                  class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                Edit
            </Link>
        </div>

        <!-- Meta grid -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500 uppercase">Status</p>
                <span :class="statusConfig[ini.status]?.bg || 'bg-gray-100 text-gray-600'"
                      class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold">
                    {{ statusConfig[ini.status]?.label || ini.status }}
                </span>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500 uppercase">Progress</p>
                <p class="mt-1 text-xl font-bold text-gray-900">{{ ini.progress ?? 0 }}%</p>
                <div class="mt-1 h-1.5 w-full rounded-full bg-gray-100">
                    <div class="h-1.5 rounded-full bg-[#4e1a77]" :style="{ width: (ini.progress ?? 0) + '%' }" />
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500 uppercase">Features</p>
                <p class="mt-1 text-xl font-bold text-gray-900">{{ ini.completed_feature_count || 0 }} <span class="text-sm font-normal text-gray-400">/ {{ ini.feature_count || 0 }}</span></p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500 uppercase">Deadline</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatDate(ini.deadline) }}</p>
            </div>
        </div>

        <!-- Details -->
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500 mb-3">Description</h2>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ ini.description }}</p>

                <h2 class="mt-6 text-sm font-bold uppercase tracking-wider text-gray-500 mb-3">Business Case</h2>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ ini.business_case }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500 mb-4">Details</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-gray-400">Origin</dt><dd class="font-medium text-gray-800">{{ ini.origin_type?.replace('_', ' ') }}</dd></div>
                    <div><dt class="text-gray-400">Expected Impact</dt><dd class="font-medium text-gray-800">{{ ini.expected_impact?.replace('_', ' ') }}</dd></div>
                    <div><dt class="text-gray-400">Module</dt><dd class="font-medium text-gray-800">{{ ini.module_name || '—' }}</dd></div>
                    <div><dt class="text-gray-400">Est. Features</dt><dd class="font-medium text-gray-800">{{ ini.estimated_features ?? '—' }}</dd></div>
                    <div><dt class="text-gray-400">Total Est. Hours</dt><dd class="font-medium text-gray-800">{{ ini.total_estimated_hours ?? 0 }}h</dd></div>
                </dl>
            </div>
        </div>

        <!-- Features table -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3.5">
                <h2 class="text-sm font-bold text-gray-900">Features ({{ ini.features?.length || 0 }})</h2>
            </div>
            <table v-if="ini.features?.length" class="w-full text-sm">
                <thead class="border-b border-gray-100 bg-gray-50/80">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">Title</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">Assignee</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">Priority</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">Deadline</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="f in ini.features" :key="f.id" class="hover:bg-[#f5f0ff]/30 transition-colors">
                        <td class="px-5 py-3">
                            <Link :href="`/features/${f.id}`" class="font-medium text-gray-900 hover:text-[#4e1a77]">{{ f.title }}</Link>
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ f.assignee_name || '—' }}</td>
                        <td class="px-5 py-3">
                            <span v-if="f.priority" :class="priorityConfig[f.priority]?.color || ''"
                                  class="inline-flex rounded-full px-2 py-0.5 text-xs font-bold ring-1">
                                {{ priorityConfig[f.priority]?.label || f.priority }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span :class="featureStatusConfig[f.status]?.bg || 'bg-gray-100 text-gray-600'"
                                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                {{ featureStatusConfig[f.status]?.label || f.status }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-500">{{ formatDate(f.deadline) }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="px-5 py-10 text-center text-sm text-gray-400">
                No features linked to this initiative yet.
            </div>
        </div>
    </div>
</template>
