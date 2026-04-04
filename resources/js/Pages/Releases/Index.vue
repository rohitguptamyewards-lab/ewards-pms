<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    releases:  { type: Object, default: () => ({}) },
    isManager: { type: Boolean, default: false },
    filters:   { type: Object, default: () => ({}) },
});

const envFilter = ref(props.filters.environment || '');

function applyFilter() {
    router.get('/releases', { environment: envFilter.value }, { preserveState: true, replace: true });
}

const envConfig = {
    staging:    { label: 'Staging', bg: 'bg-yellow-100 text-yellow-700' },
    production: { label: 'Production', bg: 'bg-emerald-100 text-emerald-700' },
};

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head title="Releases" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Releases</h1>
                <p class="mt-0.5 text-sm text-gray-500">Track software releases and deployments</p>
            </div>
            <a v-if="isManager" href="/releases/create"
               class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + New Release
            </a>
        </div>

        <div class="mb-4">
            <select v-model="envFilter" @change="applyFilter"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Environments</option>
                <option value="staging">Staging</option>
                <option value="production">Production</option>
            </select>
        </div>

        <div class="space-y-3">
            <a v-for="r in (releases.data || [])" :key="r.id"
               :href="`/releases/${r.id}`"
               class="block rounded-xl border border-gray-200 bg-white p-5 hover:border-[#5e16bd]/30 transition-colors">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">v{{ r.version }}</h3>
                        <p class="text-xs text-gray-400">{{ formatDate(r.release_date) }} · Deployed by {{ r.deployed_by_name }}</p>
                    </div>
                    <span :class="envConfig[r.environment]?.bg || 'bg-gray-100'"
                          class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                        {{ envConfig[r.environment]?.label || r.environment }}
                    </span>
                </div>
                <p v-if="r.notes" class="text-sm text-gray-600 line-clamp-2">{{ r.notes }}</p>
            </a>
        </div>

        <div v-if="!(releases.data || []).length" class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No releases found.</p>
        </div>

        <div v-if="releases.links" class="mt-4 flex gap-1">
            <template v-for="link in releases.links" :key="link.label">
                <a v-if="link.url" :href="link.url"
                   :class="link.active ? 'bg-[#5e16bd] text-white' : 'bg-white text-gray-600'"
                   class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium"
                   v-html="link.label" />
            </template>
        </div>
    </div>
</template>
