<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    features:  { type: Array,  default: () => [] },
    isManager: { type: Boolean, default: false },
});

const columns = [
    { key: 'backlog',            label: 'Backlog',            color: 'border-gray-300',    bg: 'bg-gray-50' },
    { key: 'in_progress',        label: 'In Progress',        color: 'border-[#5e16bd]',   bg: 'bg-[#f5f0ff]' },
    { key: 'in_review',          label: 'In Review',          color: 'border-purple-400',  bg: 'bg-purple-50' },
    { key: 'in_qa',              label: 'In QA',              color: 'border-orange-400',  bg: 'bg-orange-50' },
    { key: 'ready_for_release',  label: 'Ready for Release',  color: 'border-teal-400',    bg: 'bg-teal-50' },
    { key: 'released',           label: 'Released',           color: 'border-emerald-400', bg: 'bg-emerald-50' },
];

const allFeatures = ref([...props.features]);
const dragging    = ref(null);
const updating    = ref(false);

function getColumnFeatures(status) {
    return allFeatures.value.filter(f => f.status === status);
}

function getColumnCount(status) {
    return getColumnFeatures(status).length;
}

// Drag and drop handlers
function onDragStart(e, feature) {
    if (!props.isManager) return;
    dragging.value = feature;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', feature.id);
    e.target.classList.add('opacity-50');
}

function onDragEnd(e) {
    dragging.value = null;
    e.target.classList.remove('opacity-50');
}

function onDragOver(e) {
    if (!dragging.value) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function onDragEnter(e) {
    if (!dragging.value) return;
    e.currentTarget.classList.add('ring-2', 'ring-[#5e16bd]', 'ring-opacity-50');
}

function onDragLeave(e) {
    e.currentTarget.classList.remove('ring-2', 'ring-[#5e16bd]', 'ring-opacity-50');
}

async function onDrop(e, newStatus) {
    e.currentTarget.classList.remove('ring-2', 'ring-[#5e16bd]', 'ring-opacity-50');
    if (!dragging.value || dragging.value.status === newStatus) return;

    const feature   = dragging.value;
    const oldStatus = feature.status;
    dragging.value = null;

    // Optimistic update
    feature.status = newStatus;
    updating.value = true;

    try {
        await axios.put(`/api/v1/features/${feature.id}`, { status: newStatus });
    } catch {
        // Revert on failure
        feature.status = oldStatus;
    } finally {
        updating.value = false;
    }
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
}
</script>

<template>
    <Head title="Feature Board" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Feature Board</h1>
                <p class="mt-0.5 text-sm text-gray-500">Drag features between columns to update status</p>
            </div>
            <div class="flex items-center gap-3">
                <Link href="/features" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    List View
                </Link>
                <Link v-if="isManager" href="/features/create"
                      class="inline-flex items-center gap-2 rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#4c12a1] transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Feature
                </Link>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="flex gap-4 overflow-x-auto pb-4" style="min-height: calc(100vh - 220px)">
            <div
                v-for="col in columns"
                :key="col.key"
                @dragover="onDragOver"
                @dragenter="onDragEnter"
                @dragleave="onDragLeave"
                @drop="onDrop($event, col.key)"
                :class="col.bg"
                class="flex w-72 shrink-0 flex-col rounded-xl border-t-4 transition-all"
                :style="{ borderTopColor: '' }"
            >
                <!-- Column header -->
                <div class="flex items-center justify-between px-3 py-3">
                    <div class="flex items-center gap-2">
                        <span :class="col.color" class="inline-block h-2.5 w-2.5 rounded-full border-2"></span>
                        <span class="text-sm font-bold text-gray-800">{{ col.label }}</span>
                    </div>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-gray-500 shadow-sm">
                        {{ getColumnCount(col.key) }}
                    </span>
                </div>

                <!-- Cards -->
                <div class="flex-1 space-y-2 px-2 pb-3 overflow-y-auto" style="max-height: calc(100vh - 300px)">
                    <div
                        v-for="feature in getColumnFeatures(col.key)"
                        :key="feature.id"
                        :draggable="isManager"
                        @dragstart="onDragStart($event, feature)"
                        @dragend="onDragEnd"
                        class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm transition-all hover:shadow-md"
                        :class="{ 'cursor-grab active:cursor-grabbing': isManager }"
                    >
                        <Link :href="`/features/${feature.id}`" class="block">
                            <p class="text-sm font-semibold text-gray-900 leading-snug hover:text-[#5e16bd]">
                                {{ feature.title }}
                            </p>
                        </Link>
                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                            <PriorityBadge :priority="feature.priority" />
                            <span v-if="feature.module_name" class="text-xs text-gray-400">{{ feature.module_name }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div v-if="feature.assignee_name" class="flex items-center gap-1.5">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#ece1ff] text-[10px] font-bold text-[#5e16bd]">
                                    {{ feature.assignee_name.charAt(0).toUpperCase() }}
                                </span>
                                <span class="text-xs text-gray-500">{{ feature.assignee_name }}</span>
                            </div>
                            <span v-else class="text-xs text-gray-300">Unassigned</span>
                            <span v-if="feature.deadline" class="text-xs text-gray-400">{{ formatDate(feature.deadline) }}</span>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="!getColumnFeatures(col.key).length"
                         class="flex items-center justify-center rounded-lg border-2 border-dashed border-gray-200 py-8">
                        <p class="text-xs text-gray-400">No features</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
