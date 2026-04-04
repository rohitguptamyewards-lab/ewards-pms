<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    ideas:   { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const localFilters = ref({
    status: props.filters.status || '',
});

function applyFilters() {
    const params = {};
    if (localFilters.value.status) params.status = localFilters.value.status;
    router.get('/ideas', params, { preserveState: true });
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

function daysSince(d) {
    if (!d) return null;
    return Math.floor((Date.now() - new Date(d).getTime()) / 86400000);
}

const statusConfig = {
    new:          { label: 'New',          bg: 'bg-blue-100 text-blue-700' },
    under_review: { label: 'Under Review', bg: 'bg-yellow-100 text-yellow-700' },
    promoted:     { label: 'Promoted',     bg: 'bg-emerald-100 text-emerald-700' },
    archived:     { label: 'Archived',     bg: 'bg-gray-100 text-gray-500' },
};
</script>

<template>
    <Head title="Ideas" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Idea Parking Lot</h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ ideas.total ?? ideas.data?.length ?? 0 }} idea(s)</p>
            </div>
            <Link href="/ideas/create"
                  class="inline-flex items-center gap-2 rounded-lg bg-[#5e16bd] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#4c12a1] transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Capture Idea
            </Link>
        </div>

        <!-- Flash -->
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ flash.success }}
        </div>

        <!-- Filter -->
        <div class="mb-5 flex gap-3">
            <select v-model="localFilters.status" @change="applyFilters" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>
        </div>

        <!-- Cards grid -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="idea in ideas.data" :key="idea.id" :href="`/ideas/${idea.id}`"
                  class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-[#5e16bd]/30 hover:shadow-md transition-all">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-semibold text-gray-900 line-clamp-2">{{ idea.title }}</h3>
                    <span :class="statusConfig[idea.status]?.bg || 'bg-gray-100 text-gray-600'"
                          class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold">
                        {{ statusConfig[idea.status]?.label || idea.status }}
                    </span>
                </div>
                <p v-if="idea.description" class="mt-2 text-sm text-gray-500 line-clamp-2">{{ idea.description }}</p>
                <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
                    <span>{{ idea.creator_name }}</span>
                    <span :class="{ 'text-orange-500 font-medium': daysSince(idea.last_activity_at) > 30 }">
                        {{ formatDate(idea.created_at) }}
                        <template v-if="daysSince(idea.last_activity_at) > 30"> (stale)</template>
                    </span>
                </div>
            </Link>
        </div>

        <!-- Empty -->
        <div v-if="!ideas.data?.length" class="py-16 text-center">
            <p class="text-sm text-gray-500">No ideas yet. Capture one!</p>
        </div>

        <!-- Pagination -->
        <div v-if="ideas.links?.length > 3" class="mt-6 flex items-center justify-center gap-1">
            <Link v-for="link in ideas.links" :key="link.label"
                  :href="link.url || '#'"
                  :class="[link.active ? 'bg-[#5e16bd] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'pointer-events-none opacity-40' : '']"
                  class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                  v-html="link.label" preserve-scroll />
        </div>
    </div>
</template>
