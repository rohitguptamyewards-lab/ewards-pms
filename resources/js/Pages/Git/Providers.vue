<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    providers: { type: Object, default: () => ({}) },
    isManager: { type: Boolean, default: false },
});

const typeConfig = {
    github:      { label: 'GitHub', bg: 'bg-gray-900 text-white' },
    codecommit:  { label: 'CodeCommit', bg: 'bg-orange-100 text-orange-700' },
    gitlab:      { label: 'GitLab', bg: 'bg-red-100 text-red-700' },
};
</script>

<template>
    <Head title="Git Providers" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Git Providers</h1>
                <p class="mt-0.5 text-sm text-gray-500">Manage Git integration providers</p>
            </div>
            <a v-if="isManager" href="/git-providers/create"
               class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + Add Provider
            </a>
        </div>

        <div class="space-y-3">
            <div v-for="p in (providers.data || [])" :key="p.id"
                 class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-center gap-3">
                    <span :class="typeConfig[p.provider_type]?.bg || 'bg-gray-100 text-gray-600'"
                          class="rounded-lg px-3 py-1.5 text-xs font-bold">
                        {{ typeConfig[p.provider_type]?.label || p.provider_type }}
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">{{ p.name }}</h3>
                        <p v-if="p.base_url" class="text-xs text-gray-400">{{ p.base_url }}</p>
                    </div>
                </div>
                <span :class="p.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                      class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                    {{ p.is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>

        <div v-if="!(providers.data || []).length" class="py-16 text-center rounded-xl border border-dashed border-gray-200 bg-white">
            <p class="text-sm text-gray-400">No git providers configured yet.</p>
        </div>
    </div>
</template>
