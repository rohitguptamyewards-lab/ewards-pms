<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    entries: { type: Array, default: () => [] },
    missing: { type: Array, default: () => [] },
    date:    { type: String, required: true },
});

const selectedDate = ref(props.date);

function changeDate() {
    router.get('/journal/team', { date: selectedDate.value }, { preserveState: true });
}

const moodConfig = {
    great:      { emoji: '🟢' },
    good:       { emoji: '🔵' },
    neutral:    { emoji: '🟡' },
    struggling: { emoji: '🟠' },
    blocked:    { emoji: '🔴' },
};

function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
}
</script>

<template>
    <Head title="Team Journal" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Team Journal</h1>
                <p class="mt-0.5 text-sm text-gray-500">Daily entries from all team members</p>
            </div>
            <div class="flex items-center gap-3">
                <input v-model="selectedDate" @change="changeDate" type="date"
                       class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
            </div>
        </div>

        <!-- Submitted entries -->
        <div v-if="entries.length" class="space-y-3 mb-6">
            <div v-for="entry in entries" :key="entry.id"
                 class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8ddf0] text-xs font-bold text-[#4e1a77]">
                        {{ initials(entry.member_name) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-bold text-gray-900">{{ entry.member_name }}</span>
                            <span v-if="entry.mood" class="text-sm">{{ moodConfig[entry.mood]?.emoji || '' }}</span>
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-3 whitespace-pre-line">{{ entry.accomplishments }}</p>
                        <div v-if="entry.blockers" class="mt-2 rounded-lg bg-red-50 px-3 py-2">
                            <p class="text-xs font-semibold text-red-500 mb-0.5">BLOCKERS</p>
                            <p class="text-xs text-red-700">{{ entry.blockers }}</p>
                        </div>
                    </div>
                    <Link :href="`/journal/${entry.id}`" class="shrink-0 text-xs text-[#4e1a77] hover:underline">View</Link>
                </div>
            </div>
        </div>

        <!-- Missing submissions -->
        <div v-if="missing.length" class="rounded-xl border border-orange-200 bg-orange-50 p-5">
            <h3 class="text-sm font-bold text-orange-700 mb-3">Not yet submitted ({{ missing.length }})</h3>
            <div class="flex flex-wrap gap-2">
                <span v-for="m in missing" :key="m.id"
                      class="inline-flex items-center gap-1.5 rounded-full bg-white border border-orange-200 px-3 py-1 text-xs font-medium text-orange-700">
                    {{ m.name }}
                    <span class="text-orange-400 capitalize">{{ (m.role || '').replace('_', ' ') }}</span>
                </span>
            </div>
        </div>

        <div v-if="!entries.length && !missing.length" class="py-16 text-center">
            <p class="text-sm text-gray-500">No data for this date.</p>
        </div>
    </div>
</template>
