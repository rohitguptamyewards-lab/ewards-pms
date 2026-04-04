<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    capacity: { type: Array, default: () => [] },
    velocity: { type: Object, default: () => ({}) },
    filters:  { type: Object, default: () => ({}) },
});

const startDate = ref(props.filters.startDate || '');
const endDate   = ref(props.filters.endDate || '');

function applyFilter() {
    router.get('/capacity', { start_date: startDate.value, end_date: endDate.value }, { preserveState: true, replace: true });
}

function utilColor(pct) {
    if (pct > 100) return 'text-red-600';
    if (pct > 85) return 'text-yellow-600';
    return 'text-emerald-600';
}
</script>

<template>
    <Head title="Capacity Planning" />

    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Capacity Planning</h1>
        <p class="text-sm text-gray-500 mb-6">Team workload overview with overload detection</p>

        <!-- Velocity card -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase">Avg Sprint Velocity</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ velocity.avg_velocity || 0 }}h</p>
                    <p class="text-xs text-gray-400">Based on {{ velocity.sprints_analysed || 0 }} completed sprints</p>
                </div>
            </div>
        </div>

        <!-- Date range filter -->
        <div class="flex gap-3 mb-4">
            <input v-model="startDate" type="date" class="rounded-lg border border-gray-200 px-3 py-2 text-sm" />
            <input v-model="endDate" type="date" class="rounded-lg border border-gray-200 px-3 py-2 text-sm" />
            <button @click="applyFilter"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200">Apply</button>
        </div>

        <!-- Overload alert -->
        <div v-if="capacity.some(c => c.is_overloaded)"
             class="rounded-xl border border-red-200 bg-red-50 p-4 mb-4">
            <p class="text-sm font-semibold text-red-700">Overload Detected</p>
            <ul class="text-sm text-red-600 mt-1">
                <li v-for="c in capacity.filter(c => c.is_overloaded)" :key="c.member_id">
                    {{ c.member_name }} — {{ c.allocated_hours }}h allocated vs {{ c.adjusted_capacity }}h capacity
                </li>
            </ul>
        </div>

        <!-- Capacity table -->
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Member</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Role</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Weekly Cap</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Leave Hrs</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Adj. Capacity</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Allocated</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Available</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Utilisation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="c in capacity" :key="c.member_id"
                        :class="c.is_overloaded ? 'bg-red-50' : ''" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ c.member_name }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ c.role }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ c.weekly_capacity }}h</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ c.leave_hours }}h</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ c.adjusted_capacity }}h</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">{{ c.allocated_hours }}h</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ c.available_hours }}h</td>
                        <td class="px-4 py-3 text-right font-semibold" :class="utilColor(c.utilisation_percentage)">
                            {{ c.utilisation_percentage }}%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
