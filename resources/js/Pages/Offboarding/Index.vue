<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    members: { type: Array, default: () => [] },
});

const updatingId = ref(null);

async function toggleChecklistItem(member, index) {
    const checklist = typeof member.offboarding_checklist === 'string'
        ? JSON.parse(member.offboarding_checklist)
        : (member.offboarding_checklist || []);
    checklist[index].done = !checklist[index].done;
    updatingId.value = member.id;
    try {
        await axios.put(`/api/v1/offboarding/${member.id}/checklist`, { offboarding_checklist: checklist });
        member.offboarding_checklist = JSON.stringify(checklist);
    } catch { /* ignore */ }
    updatingId.value = null;
}

function getChecklist(member) {
    if (!member.offboarding_checklist) return [];
    return typeof member.offboarding_checklist === 'string'
        ? JSON.parse(member.offboarding_checklist)
        : member.offboarding_checklist;
}

function getProgress(member) {
    const list = getChecklist(member);
    if (!list.length) return 0;
    return Math.round((list.filter(i => i.done).length / list.length) * 100);
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
}

function daysUntilExit(exitDate) {
    if (!exitDate) return null;
    const diff = Math.ceil((new Date(exitDate) - new Date()) / (1000 * 60 * 60 * 24));
    return diff;
}
</script>

<template>
    <Head title="Offboarding" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Offboarding</h1>
            <p class="mt-0.5 text-sm text-gray-500">Manage knowledge transfer and exit processes</p>
        </div>

        <div v-if="members.length" class="space-y-4">
            <div v-for="member in members" :key="member.id"
                 class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-sm font-bold text-red-700">
                            {{ initials(member.name) }}
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">{{ member.name }}</h3>
                            <p class="text-xs text-gray-400 capitalize">{{ (member.role || '').replace('_', ' ') }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span v-if="member.offboarding_status === 'completed'"
                              class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Completed</span>
                        <span v-else class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-semibold text-orange-700">
                            {{ getProgress(member) }}%
                        </span>
                        <p class="mt-1 text-xs text-gray-400">
                            Exit: {{ formatDate(member.exit_date) }}
                            <span v-if="daysUntilExit(member.exit_date) !== null && daysUntilExit(member.exit_date) >= 0"
                                  class="text-red-500 font-medium">({{ daysUntilExit(member.exit_date) }}d left)</span>
                        </p>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="mb-3 h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full bg-orange-500 transition-all" :style="{ width: getProgress(member) + '%' }"></div>
                </div>

                <!-- Checklist -->
                <div class="space-y-1.5">
                    <label v-for="(item, idx) in getChecklist(member)" :key="idx"
                           class="flex items-center gap-2 cursor-pointer py-1 px-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <input type="checkbox" :checked="item.done"
                               @change="toggleChecklistItem(member, idx)"
                               class="h-4 w-4 rounded border-gray-300 text-orange-500 focus:ring-orange-500" />
                        <span :class="item.done ? 'line-through text-gray-400' : 'text-gray-700'" class="text-sm">{{ item.task }}</span>
                    </label>
                </div>

                <!-- Exit notes -->
                <div v-if="member.exit_notes" class="mt-3 pt-3 border-t border-gray-100">
                    <p class="text-xs font-semibold text-gray-400 mb-1">EXIT NOTES</p>
                    <p class="text-sm text-gray-600">{{ member.exit_notes }}</p>
                </div>
            </div>
        </div>

        <div v-else class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No offboarding processes in progress.</p>
        </div>
    </div>
</template>
