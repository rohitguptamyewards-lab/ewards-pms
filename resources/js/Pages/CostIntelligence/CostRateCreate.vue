<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    teamMembers: { type: Array, default: () => [] },
});

const form = useForm({
    team_member_id: '',
    monthly_ctc: '',
    working_hours_per_month: 168,
    overhead_multiplier: 1.3,
    effective_from: new Date().toISOString().slice(0, 10),
    effective_to: '',
});

function submit() {
    form.post('/cost-rates', { preserveScroll: true });
}
</script>

<template>
    <Head title="New Cost Rate" />

    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">New Cost Rate</h1>
        <p class="text-sm text-gray-500 mb-6">Append-only — previous active rate will be closed automatically.</p>

        <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Team Member *</label>
                <select v-model="form.team_member_id"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="">Select member</option>
                    <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
                <p v-if="form.errors.team_member_id" class="text-xs text-red-500 mt-1">{{ form.errors.team_member_id }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Monthly CTC (INR) *</label>
                <input v-model="form.monthly_ctc" type="number" step="0.01" min="0"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                <p v-if="form.errors.monthly_ctc" class="text-xs text-red-500 mt-1">{{ form.errors.monthly_ctc }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Working Hrs/Month</label>
                    <input v-model="form.working_hours_per_month" type="number" min="1" max="300"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Overhead Multiplier</label>
                    <input v-model="form.overhead_multiplier" type="number" step="0.01" min="0.5" max="3"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                    <p class="text-xs text-gray-400 mt-1">Contractors auto-set to 1.0</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Effective From *</label>
                    <input v-model="form.effective_from" type="date"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Effective To</label>
                    <input v-model="form.effective_to" type="date"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                    <p class="text-xs text-gray-400 mt-1">Leave blank for open-ended</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-3">
                <a href="/cost-rates" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#5e16bd] px-5 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    Create Rate
                </button>
            </div>
        </form>
    </div>
</template>
