<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    changelogs: { type: Object, default: () => ({}) },
    isManager:  { type: Boolean, default: false },
    filters:    { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters.status || '');

function applyFilter() {
    router.get('/changelogs', { status: statusFilter.value }, { preserveState: true, replace: true });
}

const statusConfig = {
    draft:     { label: 'Draft',     bg: 'bg-gray-100 text-gray-600' },
    approved:  { label: 'Approved',  bg: 'bg-blue-100 text-blue-700' },
    published: { label: 'Published', bg: 'bg-emerald-100 text-emerald-700' },
};

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

function approveChangelog(id) {
    router.post(`/api/v1/changelogs/${id}/approve`, {}, { preserveState: true });
}

function publishChangelog(id) {
    router.post(`/api/v1/changelogs/${id}/publish`, {}, { preserveState: true });
}
</script>

<template>
    <Head title="Changelogs" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Changelogs</h1>
                <p class="mt-0.5 text-sm text-gray-500">Merchant-facing release notes with approval workflow</p>
            </div>
            <a v-if="isManager" href="/changelogs/create"
               class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + New Changelog
            </a>
        </div>

        <div class="mb-4">
            <select v-model="statusFilter" @change="applyFilter"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="approved">Approved</option>
                <option value="published">Published</option>
            </select>
        </div>

        <div class="space-y-3">
            <div v-for="c in (changelogs.data || [])" :key="c.id"
                 class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">{{ c.title }}</h3>
                        <p class="text-xs text-gray-400">
                            Drafted by {{ c.drafted_by_name || '—' }}
                            <span v-if="c.published_at"> · Published {{ formatDate(c.published_at) }}</span>
                        </p>
                    </div>
                    <span :class="statusConfig[c.status]?.bg || 'bg-gray-100'"
                          class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                        {{ statusConfig[c.status]?.label || c.status }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 whitespace-pre-line line-clamp-3">{{ c.body }}</p>

                <div v-if="isManager" class="flex gap-2 mt-3">
                    <button v-if="c.status === 'draft'" @click="approveChangelog(c.id)"
                            class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                        Approve
                    </button>
                    <button v-if="c.status === 'approved'" @click="publishChangelog(c.id)"
                            class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100">
                        Publish
                    </button>
                </div>
            </div>
        </div>

        <div v-if="!(changelogs.data || []).length"
             class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No changelogs found.</p>
        </div>
    </div>
</template>
