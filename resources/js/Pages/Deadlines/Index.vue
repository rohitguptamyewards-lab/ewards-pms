<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    deadlines: { type: Object, default: () => ({}) },
    filters:   { type: Object, default: () => ({}) },
    isManager: { type: Boolean, default: false },
});

const localFilters = ref({ ...props.filters });

function applyFilters() {
    router.get('/deadlines', localFilters.value, { preserveState: true, replace: true });
}

const stateConfig = {
    on_track: { label: 'On Track', bg: 'bg-emerald-100 text-emerald-700' },
    at_risk:  { label: 'At Risk', bg: 'bg-orange-100 text-orange-700' },
    overdue:  { label: 'Overdue', bg: 'bg-red-100 text-red-700' },
    met:      { label: 'Met', bg: 'bg-blue-100 text-blue-700' },
    missed:   { label: 'Missed', bg: 'bg-gray-100 text-gray-600' },
};

const typeConfig = {
    hard:      { label: 'Hard', bg: 'bg-red-50 text-red-600' },
    target:    { label: 'Target', bg: 'bg-orange-50 text-orange-600' },
    soft:      { label: 'Soft', bg: 'bg-yellow-50 text-yellow-600' },
    recurring: { label: 'Recurring', bg: 'bg-blue-50 text-blue-600' },
};

function daysUntil(date) {
    const diff = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24));
    return diff;
}
</script>

<template>
    <Head title="Deadlines" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Deadlines</h1>
                <p class="mt-0.5 text-sm text-gray-500">Track and manage project deadlines</p>
            </div>
            <a v-if="isManager" href="/deadlines/create"
               class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + New Deadline
            </a>
        </div>

        <div class="mb-4 flex flex-wrap gap-3">
            <select v-model="localFilters.state" @change="applyFilters"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All States</option>
                <option value="on_track">On Track</option>
                <option value="at_risk">At Risk</option>
                <option value="overdue">Overdue</option>
                <option value="met">Met</option>
                <option value="missed">Missed</option>
            </select>
            <select v-model="localFilters.type" @change="applyFilters"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Types</option>
                <option value="hard">Hard</option>
                <option value="target">Target</option>
                <option value="soft">Soft</option>
                <option value="recurring">Recurring</option>
            </select>
        </div>

        <div class="space-y-3">
            <div v-for="d in (deadlines.data || [])" :key="d.id"
                 :class="d.state === 'overdue' ? 'border-red-200 bg-red-50/50' : 'border-gray-200 bg-white'"
                 class="rounded-xl border p-5">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">{{ d.entity_name }}</h3>
                        <p class="text-xs text-gray-400 capitalize">{{ d.deadlineable_type }}</p>
                    </div>
                    <div class="flex gap-2">
                        <span :class="typeConfig[d.type]?.bg || 'bg-gray-50'" class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                            {{ typeConfig[d.type]?.label || d.type }}
                        </span>
                        <span :class="stateConfig[d.state]?.bg || 'bg-gray-100'" class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                            {{ stateConfig[d.state]?.label || d.state }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span>Due: {{ d.due_date }}</span>
                    <span v-if="daysUntil(d.due_date) >= 0" class="font-semibold"
                          :class="daysUntil(d.due_date) <= 3 ? 'text-red-500' : 'text-gray-600'">
                        {{ daysUntil(d.due_date) }} days left
                    </span>
                    <span v-else class="font-semibold text-red-600">{{ Math.abs(daysUntil(d.due_date)) }} days overdue</span>
                </div>
            </div>
        </div>

        <div v-if="!(deadlines.data || []).length" class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No deadlines found.</p>
        </div>

        <div v-if="deadlines.links" class="mt-4 flex gap-1">
            <template v-for="link in deadlines.links" :key="link.label">
                <a v-if="link.url" :href="link.url"
                   :class="link.active ? 'bg-[#5e16bd] text-white' : 'bg-white text-gray-600'"
                   class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium"
                   v-html="link.label" />
            </template>
        </div>
    </div>
</template>
