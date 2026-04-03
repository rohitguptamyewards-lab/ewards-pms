<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CommentSection from '@/Components/CommentSection.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    request: { type: Object, required: true },
    comments: { type: Array, default: () => [] },
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
const isCTO = computed(() => role.value === 'cto');

const showTriageModal = ref(false);
const triageAction = ref('');
const triageReason = ref('');
const processing = ref(false);

const urgencyColors = {
    merchant_blocked: 'bg-red-100 text-red-700',
    merchant_unhappy: 'bg-orange-100 text-orange-700',
    nice_to_have: 'bg-gray-100 text-gray-700',
};

const typeColors = {
    bug: 'bg-red-100 text-red-700',
    new_feature: 'bg-purple-100 text-purple-700',
    improvement: 'bg-blue-100 text-blue-700',
};

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
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head :title="request.title" />

    <div class="mx-auto max-w-3xl">
        <!-- Header -->
        <div class="mb-6">
            <Link href="/requests" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Requests</Link>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ request.title }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <StatusBadge :status="request.status" type="request" />
                <span :class="typeColors[request.type] || 'bg-gray-100 text-gray-700'" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                    {{ (request.type || '').replace(/_/g, ' ') }}
                </span>
                <span :class="urgencyColors[request.urgency] || 'bg-gray-100 text-gray-700'" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                    {{ (request.urgency || '').replace(/_/g, ' ') }}
                </span>
            </div>
        </div>

        <!-- Details Card -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Description</h3>
                    <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ request.description || 'No description provided.' }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Merchant</h3>
                        <p class="mt-1 text-sm text-gray-900">{{ request.merchant?.name || '-' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Requested By</h3>
                        <p class="mt-1 text-sm text-gray-900">{{ request.requested_by?.name || request.user?.name || '-' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Demand Count</h3>
                        <p class="mt-1 text-sm text-gray-900">{{ request.demand_count ?? 0 }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Created</h3>
                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(request.created_at) }}</p>
                    </div>
                </div>

                <!-- Linked Feature -->
                <div v-if="request.feature">
                    <h3 class="text-sm font-medium text-gray-500">Linked Feature</h3>
                    <Link :href="`/features/${request.feature.id}`" class="mt-1 text-sm font-medium text-blue-600 hover:underline">
                        {{ request.feature.title }}
                    </Link>
                </div>
            </div>

            <!-- Triage Buttons (CTO only) -->
            <div v-if="isCTO && request.status === 'received'" class="mt-6 flex items-center gap-3 border-t border-gray-200 pt-6">
                <button
                    @click="openTriage('accept')"
                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors"
                >
                    Accept
                </button>
                <button
                    @click="openTriage('defer')"
                    class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-600 transition-colors"
                >
                    Defer
                </button>
                <button
                    @click="openTriage('reject')"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors"
                >
                    Reject
                </button>
            </div>
        </div>

        <!-- Comments -->
        <CommentSection
            :comments="comments"
            commentable-type="request"
            :commentable-id="request.id"
        />

        <!-- Triage Modal -->
        <div v-if="showTriageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 capitalize">{{ triageAction }} Request</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Are you sure you want to {{ triageAction }} this request?
                </p>
                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Reason {{ triageAction !== 'accept' ? '(required)' : '(optional)' }}
                    </label>
                    <textarea
                        v-model="triageReason"
                        rows="3"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    />
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <button
                        @click="showTriageModal = false"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmTriage"
                        :disabled="processing || (triageAction !== 'accept' && !triageReason.trim())"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ processing ? 'Processing...' : 'Confirm' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
