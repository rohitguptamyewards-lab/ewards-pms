<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    entries: { type: Object, default: () => ({ data: [], links: [] }) },
    streak:  { type: Number, default: 0 },
    filters: { type: Object, default: () => ({}) },
});

const localFilters = ref({
    mood: props.filters.mood || '',
    from: props.filters.from || '',
    to:   props.filters.to   || '',
});

function applyFilters() {
    router.get('/journal', Object.fromEntries(
        Object.entries(localFilters.value).filter(([, v]) => v)
    ), { preserveState: true });
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}

const moodConfig = {
    great:      { emoji: '🟢', label: 'Great' },
    good:       { emoji: '🔵', label: 'Good' },
    neutral:    { emoji: '🟡', label: 'Neutral' },
    struggling: { emoji: '🟠', label: 'Struggling' },
    blocked:    { emoji: '🔴', label: 'Blocked' },
};
</script>

<template>
    <Head title="Work Journal" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Work Journal</h1>
                <p class="mt-0.5 text-sm text-gray-500">Your daily work diary and reflections</p>
            </div>
            <div class="flex items-center gap-3">
                <div v-if="streak > 0" class="flex items-center gap-1.5 rounded-full bg-orange-100 px-3 py-1.5 text-sm font-semibold text-orange-700">
                    <span>🔥</span>
                    <span>{{ streak }}-day streak</span>
                </div>
                <Link href="/journal/create"
                      class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#3d1560] transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Today's Entry
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-5 flex flex-wrap items-end gap-3">
            <div class="min-w-[130px]">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Mood</label>
                <select v-model="localFilters.mood" @change="applyFilters" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="">All Moods</option>
                    <option v-for="(cfg, key) in moodConfig" :key="key" :value="key">{{ cfg.emoji }} {{ cfg.label }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">From</label>
                <input v-model="localFilters.from" @change="applyFilters" type="date" class="rounded-lg border border-gray-200 px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">To</label>
                <input v-model="localFilters.to" @change="applyFilters" type="date" class="rounded-lg border border-gray-200 px-3 py-2 text-sm" />
            </div>
        </div>

        <!-- Entries -->
        <div class="space-y-3">
            <Link v-for="entry in entries.data" :key="entry.id" :href="`/journal/${entry.id}`"
                  class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-[#4e1a77]/30 hover:shadow-md transition-all">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="text-sm font-bold text-gray-900">{{ formatDate(entry.entry_date) }}</span>
                            <span v-if="entry.mood" class="text-sm">{{ moodConfig[entry.mood]?.emoji || '' }}</span>
                            <span v-if="entry.is_private" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">Private</span>
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-2">{{ entry.accomplishments }}</p>
                        <div v-if="entry.tags" class="mt-2 flex flex-wrap gap-1">
                            <span v-for="tag in (typeof entry.tags === 'string' ? JSON.parse(entry.tags) : entry.tags)" :key="tag"
                                  class="rounded-full bg-[#e8ddf0] px-2 py-0.5 text-xs font-medium text-[#4e1a77]">
                                {{ tag }}
                            </span>
                        </div>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </Link>
        </div>

        <div v-if="!entries.data?.length" class="mt-8 text-center py-16">
            <p class="text-sm text-gray-500">No journal entries yet. Start your first entry today!</p>
        </div>

        <!-- Pagination -->
        <div v-if="entries.links?.length > 3" class="mt-5 flex items-center justify-center gap-1">
            <Link v-for="link in entries.links" :key="link.label"
                  :href="link.url || '#'"
                  :class="[link.active ? 'bg-[#4e1a77] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'pointer-events-none opacity-40' : '']"
                  class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                  v-html="link.label" preserve-scroll />
        </div>
    </div>
</template>
