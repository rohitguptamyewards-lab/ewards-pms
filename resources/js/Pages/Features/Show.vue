<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    feature:     { type: Object, required: true },
    isManager:   { type: Boolean, default: false },
    modules:     { type: Array,  default: () => [] },
    teamMembers: { type: Array,  default: () => [] },
    initiatives: { type: Array,  default: () => [] },
});

const page = usePage();

// ── Status transitions ──────────────────────────────────────────────────────
const STATUS_FLOW = {
    backlog:            ['in_progress'],
    in_progress:        ['in_review', 'backlog'],
    in_review:          ['in_qa', 'in_progress'],
    in_qa:              ['ready_for_release', 'in_review'],
    ready_for_release:  ['released', 'in_qa'],
    released:           [],
};

const STATUS_CONFIG = {
    backlog:           { label: 'Backlog',            classes: 'bg-gray-100 text-gray-600' },
    in_progress:       { label: 'In Progress',        classes: 'bg-[#ece1ff] text-[#5e16bd]' },
    in_review:         { label: 'In Review',          classes: 'bg-purple-100 text-purple-700' },
    in_qa:             { label: 'In QA',              classes: 'bg-orange-100 text-orange-700' },
    ready_for_release: { label: 'Ready for Release',  classes: 'bg-teal-100 text-teal-700' },
    released:          { label: 'Released',           classes: 'bg-emerald-100 text-emerald-700' },
};

const TRANSITION_BTN = {
    in_progress:       'bg-[#5e16bd] hover:bg-[#4c12a1] text-white',
    in_review:         'bg-purple-600 hover:bg-purple-700 text-white',
    in_qa:             'bg-orange-500 hover:bg-orange-600 text-white',
    ready_for_release: 'bg-teal-600 hover:bg-teal-700 text-white',
    released:          'bg-emerald-600 hover:bg-emerald-700 text-white',
    backlog:           'bg-gray-500 hover:bg-gray-600 text-white',
};

const ROLLOUT_CONFIG = {
    internal:    { label: 'Internal',      classes: 'bg-gray-100 text-gray-600' },
    beta_pilot:  { label: 'Beta / Pilot',  classes: 'bg-yellow-100 text-yellow-700' },
    gradual_ga:  { label: 'Gradual GA',    classes: 'bg-blue-100 text-blue-700' },
    full_ga:     { label: 'Full GA',       classes: 'bg-emerald-100 text-emerald-700' },
    sunset:      { label: 'Sunset',        classes: 'bg-red-100 text-red-600' },
};

const ORIGIN_CONFIG = {
    request:    { label: 'Request',    classes: 'bg-blue-100 text-blue-700' },
    initiative: { label: 'Initiative', classes: 'bg-purple-100 text-purple-700' },
    tech_debt:  { label: 'Tech Debt',  classes: 'bg-orange-100 text-orange-700' },
    bug:        { label: 'Bug',        classes: 'bg-red-100 text-red-600' },
    idea:       { label: 'Idea',       classes: 'bg-teal-100 text-teal-700' },
};

const currentStatus = ref(props.feature.status || 'backlog');
const updatingStatus = ref(false);
const statusError = ref('');

const nextStatuses = computed(() => STATUS_FLOW[currentStatus.value] || []);

async function advanceStatus(newStatus) {
    updatingStatus.value = true;
    statusError.value = '';
    try {
        await axios.put(`/api/v1/features/${props.feature.id}`, { status: newStatus });
        currentStatus.value = newStatus;
    } catch (e) {
        statusError.value = e.response?.data?.message || 'Failed to update status.';
    } finally {
        updatingStatus.value = false;
    }
}

// ── Edit mode ───────────────────────────────────────────────────────────────
const editMode = ref(false);
const form = useForm({
    title:           props.feature.title,
    description:     props.feature.description || '',
    type:            props.feature.type || '',
    priority:        props.feature.priority || 'p2',
    module_id:       props.feature.module_id || '',
    initiative_id:   props.feature.initiative_id || '',
    origin_type:     props.feature.origin_type || '',
    rollout_state:   props.feature.rollout_state || '',
    deadline:        props.feature.deadline ? props.feature.deadline.slice(0, 10) : '',
    estimated_hours: props.feature.estimated_hours || '',
    assigned_to:     props.feature.assigned_to || '',
    qa_owner_id:     props.feature.qa_owner_id || '',
    business_impact: props.feature.business_impact || '',
    status:          props.feature.status || 'backlog',
});

function saveEdit() {
    form.put(`/features/${props.feature.id}`, {
        onSuccess: () => { editMode.value = false; },
    });
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head :title="feature.title" />

    <div class="mx-auto max-w-3xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/features" class="hover:text-gray-700">Features</Link>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-900 truncate max-w-xs">{{ feature.title }}</span>
        </div>

        <!-- Header card -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-bold text-gray-900 leading-tight">{{ feature.title }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <!-- Status badge -->
                        <span :class="STATUS_CONFIG[currentStatus]?.classes || 'bg-gray-100 text-gray-600'"
                              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                            {{ STATUS_CONFIG[currentStatus]?.label || currentStatus }}
                        </span>
                        <PriorityBadge :priority="feature.priority" />
                        <span v-if="feature.type" class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 capitalize">
                            {{ (feature.type || '').replace('_', ' ') }}
                        </span>
                        <span v-if="feature.origin_type" :class="ORIGIN_CONFIG[feature.origin_type]?.classes || 'bg-gray-100 text-gray-600'"
                              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                            {{ ORIGIN_CONFIG[feature.origin_type]?.label || feature.origin_type }}
                        </span>
                        <span v-if="feature.rollout_state" :class="ROLLOUT_CONFIG[feature.rollout_state]?.classes || 'bg-gray-100 text-gray-600'"
                              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                            {{ ROLLOUT_CONFIG[feature.rollout_state]?.label || feature.rollout_state }}
                        </span>
                    </div>
                </div>
                <button
                    v-if="isManager && !editMode"
                    @click="editMode = true"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </button>
            </div>

            <!-- Meta grid -->
            <div class="mt-5 grid grid-cols-2 gap-4 border-t border-gray-100 pt-5 sm:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Assigned To</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ feature.assignee_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">QA Owner</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ feature.qa_owner_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Module</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ feature.module_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Initiative</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ feature.initiative_title || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Deadline</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ formatDate(feature.deadline) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Est. Hours</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ feature.estimated_hours ? feature.estimated_hours + 'h' : '—' }}</p>
                </div>
            </div>

            <!-- Business Impact -->
            <div v-if="feature.business_impact && !editMode" class="mt-4 border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Business Impact</p>
                <p class="mt-1 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ feature.business_impact }}</p>
            </div>

            <!-- Description -->
            <div v-if="feature.description && !editMode" class="mt-4 border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Description</p>
                <p class="mt-1 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ feature.description }}</p>
            </div>

            <!-- Edit Form -->
            <div v-if="editMode && isManager" class="mt-5 border-t border-gray-100 pt-5">
                <p class="mb-4 text-sm font-semibold text-gray-700">Edit Feature</p>
                <form @submit.prevent="saveEdit" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                        <input v-model="form.title" type="text" required
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none"
                            :class="{ 'border-red-400': form.errors.title }" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                            <select v-model="form.type" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                                <option value="">None</option>
                                <option value="new_feature">New Feature</option>
                                <option value="improvement">Improvement</option>
                                <option value="bug">Bug Fix</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Priority</label>
                            <select v-model="form.priority" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                                <option value="p0">P0 · Critical</option>
                                <option value="p1">P1 · High</option>
                                <option value="p2">P2 · Medium</option>
                                <option value="p3">P3 · Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Module</label>
                            <select v-model="form.module_id" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                                <option value="">No module</option>
                                <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Initiative</label>
                            <select v-model="form.initiative_id" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                                <option value="">No initiative</option>
                                <option v-for="i in initiatives" :key="i.id" :value="i.id">{{ i.title }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Origin</label>
                            <select v-model="form.origin_type" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                                <option value="">Not specified</option>
                                <option value="request">Request</option>
                                <option value="initiative">Initiative</option>
                                <option value="tech_debt">Tech Debt</option>
                                <option value="bug">Bug</option>
                                <option value="idea">Idea</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Rollout State</label>
                            <select v-model="form.rollout_state" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                                <option value="">Not set</option>
                                <option value="internal">Internal</option>
                                <option value="beta_pilot">Beta / Pilot</option>
                                <option value="gradual_ga">Gradual GA</option>
                                <option value="full_ga">Full GA</option>
                                <option value="sunset">Sunset</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Assign To</label>
                            <select v-model="form.assigned_to" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                                <option value="">Unassigned</option>
                                <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">QA Owner</label>
                            <select v-model="form.qa_owner_id" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                                <option value="">Unassigned</option>
                                <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Deadline</label>
                            <input v-model="form.deadline" type="date" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Est. Hours</label>
                            <input v-model.number="form.estimated_hours" type="number" step="0.5" min="0" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Business Impact</label>
                        <textarea v-model="form.business_impact" rows="2" placeholder="Revenue impact, user value, strategic importance..."
                            class="block w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea v-model="form.description" rows="3" class="block w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none" />
                    </div>
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="editMode = false"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-medium text-white hover:bg-[#4c12a1] disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Status Transitions -->
            <div v-if="isManager && !editMode" class="mt-5 border-t border-gray-100 pt-5">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Advance Status</p>
                <div v-if="nextStatuses.length" class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="status in nextStatuses"
                        :key="status"
                        @click="advanceStatus(status)"
                        :disabled="updatingStatus"
                        :class="TRANSITION_BTN[status]"
                        class="rounded-lg px-4 py-2 text-sm font-medium shadow-sm disabled:opacity-50 transition-colors"
                    >
                        <span v-if="updatingStatus" class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Updating...
                        </span>
                        <span v-else>→ {{ STATUS_CONFIG[status]?.label || status }}</span>
                    </button>
                    <p v-if="statusError" class="text-sm text-red-600">{{ statusError }}</p>
                </div>
                <div v-else class="flex items-center gap-2 text-sm text-emerald-600 font-medium">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Feature Released
                </div>
            </div>
        </div>

        <!-- Linked Requests -->
        <div v-if="feature.linked_requests?.length" class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Linked Requests <span class="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">{{ feature.linked_requests.length }}</span></h2>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="req in feature.linked_requests" :key="req.id" class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition-colors">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">{{ req.title }}</p>
                        <p class="text-xs text-gray-400">{{ req.merchant_name || '—' }} · demand: {{ req.demand_count ?? 0 }}</p>
                    </div>
                    <span class="ml-3 shrink-0 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 capitalize">
                        {{ (req.status || '').replace('_', ' ') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
