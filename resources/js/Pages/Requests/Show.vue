<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CommentSection from '@/Components/CommentSection.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    request:  { type: Object, required: true },
    comments: { type: Array, default: () => [] },
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
// BUG FIX: was only cto — triage should be available to all manager roles
const canTriage = computed(() => ['cto', 'ceo', 'manager', 'mc_team'].includes(role.value));

const showTriageModal = ref(false);
const triageAction    = ref('');
const triageReason    = ref('');
const processing      = ref(false);

const urgencyConfig = {
    merchant_blocked: { classes: 'bg-red-100 text-red-700 ring-1 ring-red-200',       label: 'Merchant Blocked' },
    merchant_unhappy: { classes: 'bg-orange-100 text-orange-700 ring-1 ring-orange-200', label: 'Merchant Unhappy' },
    nice_to_have:     { classes: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200',     label: 'Nice to Have' },
};

const typeConfig = {
    bug:         { classes: 'bg-red-100 text-red-700 ring-1 ring-red-200',       label: 'Bug' },
    new_feature: { classes: 'bg-purple-100 text-purple-700 ring-1 ring-purple-200', label: 'New Feature' },
    improvement: { classes: 'bg-[#ece1ff] text-[#5e16bd] ring-1 ring-blue-200',    label: 'Improvement' },
};

function getUrgency(u) { return urgencyConfig[u] || { classes: 'bg-gray-100 text-gray-600', label: (u || '').replace(/_/g, ' ') }; }
function getType(t)    { return typeConfig[t]    || { classes: 'bg-gray-100 text-gray-600', label: (t || '').replace(/_/g, ' ') }; }

function openTriage(action) {
    triageAction.value = action;
    triageReason.value = '';
    showTriageModal.value = true;
}

function confirmTriage() {
    processing.value = true;
    router.put(`/requests/${props.request.id}/triage`, {
        action: triageAction.value,
        reason: triageReason.value,
    }, {
        onFinish: () => {
            processing.value = false;
            showTriageModal.value = false;
        },
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const triageActionConfig = {
    accept: { label: 'Accept Request',  btnClasses: 'bg-emerald-600 hover:bg-emerald-700' },
    defer:  { label: 'Defer Request',   btnClasses: 'bg-amber-500 hover:bg-amber-600' },
    reject: { label: 'Reject Request',  btnClasses: 'bg-red-600 hover:bg-red-700' },
};
</script>

<template>
    <Head :title="request.title" />

    <div class="mx-auto max-w-3xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/requests" class="hover:text-gray-700">Requests</Link>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-900 truncate max-w-xs">{{ request.title }}</span>
        </div>

        <!-- Header card -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-bold text-gray-900 leading-tight">{{ request.title }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <StatusBadge :status="request.status" type="request" />
                        <span :class="getType(request.type).classes" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                            {{ getType(request.type).label }}
                        </span>
                        <span :class="getUrgency(request.urgency).classes" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                            {{ getUrgency(request.urgency).label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="mt-5 border-t border-gray-100 pt-5">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Description</p>
                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ request.description || 'No description provided.' }}</p>
            </div>

            <!-- Meta grid -->
            <div class="mt-5 grid grid-cols-2 gap-4 border-t border-gray-100 pt-5 sm:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Merchant</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ request.merchant?.name || request.merchant_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Requested By</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ request.requested_by?.name || request.user?.name || request.user_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Demand Count</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ request.demand_count ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Created</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ formatDate(request.created_at) }}</p>
                </div>
            </div>

            <!-- Linked Feature -->
            <div v-if="request.feature" class="mt-4 border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Linked Feature</p>
                <p class="mt-1 text-sm font-medium text-gray-800">{{ request.feature.title }}</p>
            </div>

            <!-- Triage Buttons (manager roles only, received status) -->
            <div v-if="canTriage && request.status === 'received'" class="mt-5 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
                <p class="w-full text-xs font-semibold uppercase tracking-wide text-gray-400">Triage</p>
                <button
                    @click="openTriage('accept')"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 transition-colors"
                >
                    Accept
                </button>
                <button
                    @click="openTriage('defer')"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-600 transition-colors"
                >
                    Defer
                </button>
                <button
                    @click="openTriage('reject')"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 transition-colors"
                >
                    Reject
                </button>
            </div>
        </div>

        <!-- Comments -->
        <div class="mt-6">
            <CommentSection
                :comments="comments"
                commentable-type="request"
                :commentable-id="request.id"
            />
        </div>

        <!-- Triage Modal -->
        <Teleport to="body">
            <div v-if="showTriageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
                    <h3 class="text-lg font-bold text-gray-900 capitalize">
                        {{ triageActionConfig[triageAction]?.label || triageAction + ' Request' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Are you sure you want to <strong>{{ triageAction }}</strong> this request?
                    </p>
                    <div class="mt-4">
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Reason
                            <span v-if="triageAction !== 'accept'" class="text-red-500">*</span>
                            <span v-else class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea
                            v-model="triageReason"
                            rows="3"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none"
                            placeholder="Provide a reason..."
                        />
                    </div>
                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            @click="showTriageModal = false"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmTriage"
                            :disabled="processing || (triageAction !== 'accept' && !triageReason.trim())"
                            :class="triageActionConfig[triageAction]?.btnClasses || 'bg-[#5e16bd] hover:bg-[#4c12a1]'"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm disabled:opacity-50 transition-colors"
                        >
                            {{ processing ? 'Processing...' : 'Confirm' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
