<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    tool:        { type: Object, default: () => ({}) },
    assignments: { type: Array, default: () => [] },
    teamMembers: { type: Array, default: () => [] },
    isManager:   { type: Boolean, default: false },
});

const selectedMember = ref('');

function assignMember() {
    if (!selectedMember.value) return;
    router.post(`/api/v1/ai-tools/${props.tool.id}/assign`, {
        team_member_id: selectedMember.value,
    }, { preserveState: true });
    selectedMember.value = '';
}

function revokeMember(memberId) {
    router.post(`/api/v1/ai-tools/${props.tool.id}/revoke`, {
        team_member_id: memberId,
    }, { preserveState: true });
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

const capabilities = JSON.parse(props.tool.capabilities || '[]');
</script>

<template>
    <Head :title="tool.name || 'AI Tool'" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ tool.name }}</h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ tool.provider }}</p>
            </div>
            <span :class="tool.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                  class="rounded-full px-3 py-1 text-sm font-semibold">
                {{ tool.is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Cost/Seat</p>
                <p class="text-xl font-bold text-gray-900 mt-1">${{ tool.cost_per_seat_monthly }}/mo</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Seats</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ tool.seats_purchased }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium text-gray-400 uppercase">Monthly Spend</p>
                <p class="text-xl font-bold text-gray-900 mt-1">${{ (tool.cost_per_seat_monthly * tool.seats_purchased).toFixed(2) }}</p>
            </div>
        </div>

        <div v-if="capabilities.length" class="mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Capabilities</h3>
            <div class="flex flex-wrap gap-1.5">
                <span v-for="cap in capabilities" :key="cap"
                      class="rounded-full bg-[#5e16bd]/10 px-2.5 py-1 text-xs font-medium text-[#5e16bd]">
                    {{ cap }}
                </span>
            </div>
        </div>

        <!-- Assignments -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Team Assignments</h2>

        <div v-if="isManager" class="flex gap-2 mb-4">
            <select v-model="selectedMember" class="rounded-lg border border-gray-200 px-3 py-2 text-sm flex-1">
                <option value="">Select member to assign</option>
                <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <button @click="assignMember"
                    class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                Assign
            </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Member</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Assigned</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th v-if="isManager" class="px-4 py-3 text-left font-medium text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="a in assignments" :key="a.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ a.member_name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ formatDate(a.assigned_at) }}</td>
                        <td class="px-4 py-3">
                            <span :class="a.revoked_at ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-700'"
                                  class="rounded-full px-2 py-0.5 text-xs font-semibold">
                                {{ a.revoked_at ? 'Revoked' : 'Active' }}
                            </span>
                        </td>
                        <td v-if="isManager" class="px-4 py-3">
                            <button v-if="!a.revoked_at" @click="revokeMember(a.team_member_id)"
                                    class="text-xs text-red-500 hover:underline">Revoke</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
