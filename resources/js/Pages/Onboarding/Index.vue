<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    activeOnboarding:    { type: Array, default: () => [] },
    completedOnboarding: { type: Array, default: () => [] },
    buddies:             { type: Array, default: () => [] },
});

const updatingId = ref(null);

async function toggleChecklistItem(member, index) {
    const checklist = typeof member.onboarding_checklist === 'string'
        ? JSON.parse(member.onboarding_checklist)
        : member.onboarding_checklist;
    checklist[index].done = !checklist[index].done;
    updatingId.value = member.id;
    try {
        await axios.put(`/api/v1/onboarding/${member.id}/checklist`, { onboarding_checklist: checklist });
        member.onboarding_checklist = JSON.stringify(checklist);
    } catch { /* ignore */ }
    updatingId.value = null;
}

function getChecklist(member) {
    if (!member.onboarding_checklist) return [];
    return typeof member.onboarding_checklist === 'string'
        ? JSON.parse(member.onboarding_checklist)
        : member.onboarding_checklist;
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
</script>

<template>
    <Head title="Onboarding" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Onboarding</h1>
            <p class="mt-0.5 text-sm text-gray-500">Track new team member onboarding progress</p>
        </div>

        <!-- Active onboarding -->
        <div v-if="activeOnboarding.length" class="space-y-4 mb-8">
            <div v-for="member in activeOnboarding" :key="member.id"
                 class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#e8ddf0] text-sm font-bold text-[#4e1a77]">
                            {{ initials(member.name) }}
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">{{ member.name }}</h3>
                            <p class="text-xs text-gray-400 capitalize">{{ (member.role || '').replace('_', ' ') }} &middot; Joined {{ formatDate(member.joining_date) }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-700">{{ getProgress(member) }}%</span>
                        <p v-if="member.onboarding_due_date" class="mt-1 text-xs text-gray-400">Due: {{ formatDate(member.onboarding_due_date) }}</p>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="mb-3 h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full bg-[#4e1a77] transition-all" :style="{ width: getProgress(member) + '%' }"></div>
                </div>

                <!-- Checklist -->
                <div class="space-y-1.5">
                    <label v-for="(item, idx) in getChecklist(member)" :key="idx"
                           class="flex items-center gap-2 cursor-pointer py-1 px-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <input type="checkbox" :checked="item.done"
                               @change="toggleChecklistItem(member, idx)"
                               class="h-4 w-4 rounded border-gray-300 text-[#4e1a77] focus:ring-[#4e1a77]" />
                        <span :class="item.done ? 'line-through text-gray-400' : 'text-gray-700'" class="text-sm">{{ item.task }}</span>
                    </label>
                </div>
            </div>
        </div>

        <div v-if="!activeOnboarding.length" class="mb-8 py-12 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No active onboarding processes.</p>
        </div>

        <!-- Recently completed -->
        <div v-if="completedOnboarding.length">
            <h2 class="text-sm font-bold text-gray-600 mb-3">Recently Completed</h2>
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-50">
                <div v-for="m in completedOnboarding" :key="m.id" class="flex items-center justify-between px-5 py-3">
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                            {{ initials(m.name) }}
                        </div>
                        <span class="text-sm font-medium text-gray-800">{{ m.name }}</span>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Completed</span>
                </div>
            </div>
        </div>
    </div>
</template>
