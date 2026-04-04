<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    period:            { type: String, default: '90' },
    tasksCompleted:    { type: Number, default: 0 },
    totalHours:        { type: [Number, String], default: 0 },
    featuresDelivered: { type: Array, default: () => [] },
    journalHighlights: { type: Array, default: () => [] },
    moodTrend:         { type: Array, default: () => [] },
});

const selectedPeriod = ref(props.period);

function changePeriod() {
    router.get('/personal/review-prep', { period: selectedPeriod.value }, { preserveState: true });
}

const moodEmoji = { great: '🟢', good: '🔵', neutral: '🟡', struggling: '🟠', blocked: '🔴' };

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
}
</script>

<template>
    <Head title="Review Prep" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Review Prep</h1>
                <p class="mt-0.5 text-sm text-gray-500">Auto-generated summary for your performance review</p>
            </div>
            <select v-model="selectedPeriod" @change="changePeriod"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]">
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
                <option value="180">Last 6 months</option>
            </select>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-400">Tasks Completed</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ tasksCompleted }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-400">Hours Invested</p>
                <p class="mt-1 text-3xl font-bold text-[#4e1a77]">{{ Number(totalHours).toFixed(1) }}h</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-400">Features Delivered</p>
                <p class="mt-1 text-3xl font-bold text-emerald-600">{{ featuresDelivered.length }}</p>
            </div>
        </div>

        <!-- Features delivered -->
        <div v-if="featuresDelivered.length" class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3.5">
                <h2 class="text-sm font-bold text-gray-900">Features Delivered</h2>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="f in featuresDelivered" :key="f.id" class="flex items-center justify-between px-5 py-3">
                    <Link :href="`/features/${f.id}`" class="text-sm font-medium text-[#4e1a77] hover:underline">{{ f.title }}</Link>
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 capitalize">{{ (f.status || '').replace('_', ' ') }}</span>
                </div>
            </div>
        </div>

        <!-- Mood trend -->
        <div v-if="moodTrend.length" class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-3">Mood Distribution</h2>
            <div class="flex gap-4">
                <div v-for="m in moodTrend" :key="m.mood" class="text-center">
                    <p class="text-2xl">{{ moodEmoji[m.mood] || '' }}</p>
                    <p class="text-lg font-bold text-gray-800">{{ m.count }}</p>
                    <p class="text-xs text-gray-400 capitalize">{{ m.mood }}</p>
                </div>
            </div>
        </div>

        <!-- Journal highlights -->
        <div v-if="journalHighlights.length" class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3.5">
                <h2 class="text-sm font-bold text-gray-900">Journal Highlights</h2>
            </div>
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                <div v-for="j in journalHighlights" :key="j.entry_date" class="px-5 py-3">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold text-gray-500">{{ formatDate(j.entry_date) }}</span>
                        <span v-if="j.mood" class="text-xs">{{ moodEmoji[j.mood] || '' }}</span>
                    </div>
                    <p class="text-sm text-gray-600 line-clamp-2">{{ j.accomplishments }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
