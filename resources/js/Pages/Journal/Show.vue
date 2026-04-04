<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    entry: { type: Object, required: true },
});

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}

const moodConfig = {
    great:      { emoji: '🟢', label: 'Great',      bg: 'bg-emerald-50 border-emerald-200' },
    good:       { emoji: '🔵', label: 'Good',       bg: 'bg-blue-50 border-blue-200' },
    neutral:    { emoji: '🟡', label: 'Neutral',    bg: 'bg-yellow-50 border-yellow-200' },
    struggling: { emoji: '🟠', label: 'Struggling', bg: 'bg-orange-50 border-orange-200' },
    blocked:    { emoji: '🔴', label: 'Blocked',    bg: 'bg-red-50 border-red-200' },
};
</script>

<template>
    <Head :title="`Journal — ${formatDate(entry.entry_date)}`" />

    <div>
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/journal" class="hover:text-[#4e1a77]">Journal</Link>
            <span>/</span>
            <span class="text-gray-800 font-medium">{{ formatDate(entry.entry_date) }}</span>
        </div>

        <div class="mx-auto max-w-2xl">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">{{ formatDate(entry.entry_date) }}</h1>
                        <p v-if="entry.member_name" class="mt-0.5 text-sm text-gray-500">{{ entry.member_name }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="entry.is_private" class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Private</span>
                        <span v-if="entry.mood" :class="moodConfig[entry.mood]?.bg || 'bg-gray-50 border-gray-200'"
                              class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-sm font-medium">
                            {{ moodConfig[entry.mood]?.emoji }} {{ moodConfig[entry.mood]?.label }}
                        </span>
                    </div>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <h3 class="text-xs font-bold uppercase text-gray-400 mb-1">Accomplishments</h3>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ entry.accomplishments }}</p>
                    </div>

                    <div v-if="entry.blockers">
                        <h3 class="text-xs font-bold uppercase text-red-400 mb-1">Blockers</h3>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ entry.blockers }}</p>
                    </div>

                    <div v-if="entry.plan_for_tomorrow">
                        <h3 class="text-xs font-bold uppercase text-blue-400 mb-1">Plan for Tomorrow</h3>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ entry.plan_for_tomorrow }}</p>
                    </div>

                    <div v-if="entry.reflections">
                        <h3 class="text-xs font-bold uppercase text-purple-400 mb-1">Reflections</h3>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ entry.reflections }}</p>
                    </div>

                    <div v-if="entry.tags" class="pt-2 border-t border-gray-100">
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="tag in (typeof entry.tags === 'string' ? JSON.parse(entry.tags) : entry.tags)" :key="tag"
                                  class="rounded-full bg-[#e8ddf0] px-2.5 py-0.5 text-xs font-medium text-[#4e1a77]">
                                {{ tag }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
