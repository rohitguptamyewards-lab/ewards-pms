<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    sprints:   { type: Object, default: () => ({}) },
    isManager: { type: Boolean, default: false },
    filters:   { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters.status || '');

function applyFilter() {
    router.get('/sprints', { status: statusFilter.value }, { preserveState: true, replace: true });
}

const statusConfig = {
    planning:  { label: 'Planning', bg: 'bg-yellow-100 text-yellow-700' },
    active:    { label: 'Active', bg: 'bg-blue-100 text-blue-700' },
    completed: { label: 'Completed', bg: 'bg-emerald-100 text-emerald-700' },
    archived:  { label: 'Archived', bg: 'bg-gray-100 text-gray-600' },
};
</script>

<template>
    <Head title="Sprints" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Sprints</h1>
                <p class="mt-0.5 text-sm text-gray-500">Sprint cycle planning and tracking</p>
            </div>
            <a v-if="isManager" href="/sprints/create"
               class="rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + New Sprint
            </a>
        </div>

        <div class="mb-4">
            <select v-model="statusFilter" @change="applyFilter"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="planning">Planning</option>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="archived">Archived</option>
            </select>
        </div>

        <div class="space-y-3">
            <a v-for="sprint in (sprints.data || [])" :key="sprint.id"
               :href="`/sprints/${sprint.id}`"
               class="block rounded-xl border border-gray-200 bg-white p-5 hover:border-[#4e1a77]/30 transition-colors">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Sprint #{{ sprint.sprint_number }}</h3>
                        <p class="text-xs text-gray-500">{{ sprint.start_date }} → {{ sprint.end_date }}</p>
                    </div>
                    <span :class="statusConfig[sprint.status]?.bg || 'bg-gray-100 text-gray-600'"
                          class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                        {{ statusConfig[sprint.status]?.label || sprint.status }}
                    </span>
                </div>
                <p v-if="sprint.goal" class="text-sm text-gray-600 mb-2">{{ sprint.goal }}</p>
                <div class="flex gap-4 text-xs text-gray-400">
                    <span>Capacity: {{ sprint.total_capacity_hours || 0 }}h</span>
                    <span>Committed: {{ sprint.committed_hours || 0 }}h</span>
                </div>
            </a>
        </div>

        <div v-if="!(sprints.data || []).length" class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No sprints found.</p>
        </div>

        <div v-if="sprints.links" class="mt-4 flex gap-1">
            <template v-for="link in sprints.links" :key="link.label">
                <a v-if="link.url" :href="link.url"
                   :class="link.active ? 'bg-[#4e1a77] text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                   class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium"
                   v-html="link.label" />
            </template>
        </div>
    </div>
</template>
