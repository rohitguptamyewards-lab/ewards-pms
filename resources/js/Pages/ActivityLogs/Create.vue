<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    features: { type: Array, default: () => [] },
    aiTools:  { type: Array, default: () => [] },
    prefill:  { type: Array, default: () => [] },
    userRole: { type: String, default: 'developer' },
});

const activityTypes = computed(() => {
    const role = props.userRole;
    if (['developer', 'cto'].includes(role)) {
        return [
            { value: 'coding', label: 'Coding' },
            { value: 'bug_fixing', label: 'Bug Fixing' },
            { value: 'code_review', label: 'Code Review' },
            { value: 'investigation_debugging', label: 'Investigation / Debugging' },
            { value: 'deployment', label: 'Deployment' },
            { value: 'documentation', label: 'Documentation' },
            { value: 'meeting', label: 'Meeting' },
        ];
    }
    if (role === 'tester') {
        return [
            { value: 'test_case_writing', label: 'Test Case Writing' },
            { value: 'manual_testing', label: 'Manual Testing' },
            { value: 'regression_testing', label: 'Regression Testing' },
            { value: 'bug_reporting', label: 'Bug Reporting' },
            { value: 'test_automation', label: 'Test Automation' },
            { value: 'meeting', label: 'Meeting' },
        ];
    }
    // analyst and others
    return [
        { value: 'requirement_gathering', label: 'Requirement Gathering' },
        { value: 'spec_writing', label: 'Spec Writing' },
        { value: 'merchant_communication', label: 'Merchant Communication' },
        { value: 'data_analysis', label: 'Data Analysis' },
        { value: 'documentation', label: 'Documentation' },
        { value: 'meeting', label: 'Meeting' },
    ];
});

const durations = [
    { value: '30m', label: '30 min' },
    { value: '1h', label: '1 hour' },
    { value: '2h', label: '2 hours' },
    { value: '3h', label: '3 hours' },
    { value: 'half_day', label: 'Half day' },
    { value: 'full_day', label: 'Full day' },
];

const form = useForm({
    activity_type: '',
    feature_id: null,
    duration: '1h',
    effort_confidence: 'high',
    status: 'done',
    blocker_reason: '',
    note: '',
    log_date: new Date().toISOString().split('T')[0],
    ai_used: false,
    ai_tool_id: null,
    ai_capability: '',
    ai_contribution: '',
    ai_outcome: '',
    ai_time_saved: '',
    ai_note: '',
});

function submit() {
    form.post('/activity-logs');
}

function prefillYesterday() {
    window.location.href = '/activity-logs/create?prefill_yesterday=1';
}
</script>

<template>
    <Head title="Log Activity" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Log Activity</h1>
            <p class="mt-0.5 text-sm text-gray-500">Record your daily work activity</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6">
            <!-- Prefill button -->
            <div class="flex justify-end">
                <button type="button" @click="prefillYesterday"
                        class="text-sm text-[#4e1a77] hover:underline">
                    Same as yesterday
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Activity Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Activity Type *</label>
                    <select v-model="form.activity_type"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="">Select activity...</option>
                        <option v-for="t in activityTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <p v-if="form.errors.activity_type" class="mt-1 text-xs text-red-500">{{ form.errors.activity_type }}</p>
                </div>

                <!-- Feature -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Feature</label>
                    <select v-model="form.feature_id"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option :value="null">No feature linked</option>
                        <option v-for="f in features" :key="f.id" :value="f.id">{{ f.title }}</option>
                    </select>
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration *</label>
                    <select v-model="form.duration"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option v-for="d in durations" :key="d.value" :value="d.value">{{ d.label }}</option>
                    </select>
                </div>

                <!-- Effort Confidence -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Effort Confidence</label>
                    <select v-model="form.effort_confidence"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select v-model="form.status"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="done">Done</option>
                        <option value="in_progress">In Progress</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>

                <!-- Log Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                    <input type="date" v-model="form.log_date"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
            </div>

            <!-- Blocker Reason -->
            <div v-if="form.status === 'blocked'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Blocker Reason *</label>
                <textarea v-model="form.blocker_reason" rows="2"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Describe the blocker..." />
                <p v-if="form.errors.blocker_reason" class="mt-1 text-xs text-red-500">{{ form.errors.blocker_reason }}</p>
            </div>

            <!-- Note -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                <textarea v-model="form.note" rows="2"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Optional notes..." />
            </div>

            <!-- AI Section -->
            <div class="border-t border-gray-100 pt-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.ai_used"
                           class="h-4 w-4 rounded border-gray-300 text-[#4e1a77] focus:ring-[#4e1a77]" />
                    <span class="text-sm font-medium text-gray-700">AI tool was used</span>
                </label>

                <div v-if="form.ai_used" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 pl-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">AI Tool</label>
                        <select v-model="form.ai_tool_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            <option :value="null">Select tool...</option>
                            <option v-for="t in aiTools" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time Saved</label>
                        <select v-model="form.ai_time_saved" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            <option value="">Select...</option>
                            <option value="15m">15 min</option>
                            <option value="30m">30 min</option>
                            <option value="1h">1 hour</option>
                            <option value="2h">2 hours</option>
                            <option value="half_day">Half day</option>
                            <option value="full_day">Full day</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">AI Note</label>
                        <textarea v-model="form.ai_note" rows="2"
                                  class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="How AI helped..." />
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3">
                <a href="/activity-logs" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#4e1a77] px-6 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    {{ form.processing ? 'Saving...' : 'Log Activity' }}
                </button>
            </div>
        </form>
    </div>
</template>
