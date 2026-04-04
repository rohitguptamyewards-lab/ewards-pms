<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    teamMembers: { type: Array, default: () => [] },
});

const form = useForm({
    team_member_id: '',
    leave_date: '',
    leave_type: 'planned',
    half_day: false,
    source: 'manual',
    hrms_reference: '',
});

function submit() {
    form.post('/leave-entries', { preserveScroll: true });
}
</script>

<template>
    <Head title="Record Leave" />

    <div class="max-w-lg">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Record Leave</h1>

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
                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                <input v-model="form.leave_date" type="date"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                <select v-model="form.leave_type"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="planned">Planned</option>
                    <option value="sick">Sick</option>
                    <option value="holiday">Holiday</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input v-model="form.half_day" type="checkbox" id="half_day" class="rounded border-gray-300 text-[#4e1a77]" />
                <label for="half_day" class="text-sm text-gray-700">Half day only</label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">HRMS Reference</label>
                <input v-model="form.hrms_reference" type="text"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Optional HRMS ID" />
            </div>

            <div class="flex justify-end gap-3 pt-3">
                <a href="/leave-entries" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#4e1a77] px-5 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    Record Leave
                </button>
            </div>
        </form>
    </div>
</template>
