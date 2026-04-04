<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    communications:     { type: Object, default: () => ({}) },
    filters:            { type: Object, default: () => ({}) },
    merchants:          { type: Array, default: () => [] },
    features:           { type: Array, default: () => [] },
    teamMembers:        { type: Array, default: () => [] },
    slippedCommitments: { type: Array, default: () => [] },
    isManager:          { type: Boolean, default: false },
});

const localFilters = ref({ ...props.filters });

function applyFilters() {
    router.get('/merchant-communications', localFilters.value, { preserveState: true, replace: true });
}

const channelConfig = {
    call:    { label: 'Call', bg: 'bg-blue-100 text-blue-700' },
    email:   { label: 'Email', bg: 'bg-purple-100 text-purple-700' },
    meeting: { label: 'Meeting', bg: 'bg-emerald-100 text-emerald-700' },
};

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head title="Merchant Communications" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Merchant Communications</h1>
                <p class="mt-0.5 text-sm text-gray-500">Track calls, emails, and meetings with merchants</p>
            </div>
            <a href="/merchant-communications/create"
               class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + Log Communication
            </a>
        </div>

        <!-- Slipped commitments alert -->
        <div v-if="slippedCommitments.length" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4">
            <h3 class="text-sm font-bold text-red-700 mb-2">Slipped Commitments ({{ slippedCommitments.length }})</h3>
            <div v-for="sc in slippedCommitments" :key="sc.id" class="text-sm text-red-600 mb-1">
                <span class="font-semibold">{{ sc.merchant_name }}</span> — {{ sc.feature_title }}
                (committed: {{ formatDate(sc.commitment_date) }}, status: {{ sc.feature_status }})
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-3">
            <select v-model="localFilters.merchant_id" @change="applyFilters"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Merchants</option>
                <option v-for="m in merchants" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <select v-model="localFilters.channel" @change="applyFilters"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Channels</option>
                <option value="call">Call</option>
                <option value="email">Email</option>
                <option value="meeting">Meeting</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Merchant</th>
                        <th class="px-4 py-3">Channel</th>
                        <th class="px-4 py-3">Summary</th>
                        <th class="px-4 py-3">Feature</th>
                        <th class="px-4 py-3">Commitment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="c in (communications.data || [])" :key="c.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ formatDate(c.created_at) }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ c.merchant_name }}</td>
                        <td class="px-4 py-3">
                            <span :class="channelConfig[c.channel]?.bg || 'bg-gray-100'"
                                  class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                {{ channelConfig[c.channel]?.label || c.channel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ c.summary }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ c.feature_title || '—' }}</td>
                        <td class="px-4 py-3">
                            <div v-if="c.commitment_made">
                                <span class="text-xs font-semibold text-orange-600">Yes</span>
                                <span v-if="c.commitment_date" class="text-xs text-gray-400 ml-1">by {{ formatDate(c.commitment_date) }}</span>
                            </div>
                            <span v-else class="text-xs text-gray-400">—</span>
                        </td>
                    </tr>
                    <tr v-if="!(communications.data || []).length">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">No communications logged.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="communications.links" class="mt-4 flex gap-1">
            <template v-for="link in communications.links" :key="link.label">
                <a v-if="link.url" :href="link.url"
                   :class="link.active ? 'bg-[#5e16bd] text-white' : 'bg-white text-gray-600'"
                   class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium"
                   v-html="link.label" />
            </template>
        </div>
    </div>
</template>
