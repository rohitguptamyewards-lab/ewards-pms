<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    release:   { type: Object, default: () => ({}) },
    isManager: { type: Boolean, default: false },
});

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
    <Head :title="`Release v${release.version}`" />

    <div>
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Release v{{ release.version }}</h1>
                <p class="text-sm text-gray-500">{{ formatDate(release.release_date) }} · Deployed by {{ release.deployed_by_name }}</p>
            </div>
            <span :class="envConfig[release.environment]?.bg || 'bg-gray-100'"
                  class="rounded-full px-3 py-1 text-xs font-bold">
                {{ envConfig[release.environment]?.label || release.environment }}
            </span>
        </div>

        <!-- Release Notes -->
        <div v-if="release.notes" class="mb-6 rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="text-xs font-semibold text-gray-400 uppercase mb-2">Release Notes</h3>
            <pre class="text-sm text-gray-700 whitespace-pre-wrap font-sans">{{ release.notes }}</pre>
        </div>

        <!-- Included Features -->
        <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="text-sm font-bold text-gray-700">Included Features ({{ (release.features || []).length }})</h3>
            </div>
            <div class="divide-y divide-gray-100">
                <div v-for="f in (release.features || [])" :key="f.id"
                     class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                    <div>
                        <a :href="`/features/${f.id}`" class="text-sm font-medium text-gray-900 hover:text-[#4e1a77]">{{ f.title }}</a>
                        <p class="text-xs text-gray-400">{{ f.module_name || 'No module' }}</p>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                        Released
                    </span>
                </div>
                <div v-if="!(release.features || []).length" class="px-4 py-8 text-center text-gray-400 text-sm">
                    No features in this release.
                </div>
            </div>
        </div>
    </div>
</template>
