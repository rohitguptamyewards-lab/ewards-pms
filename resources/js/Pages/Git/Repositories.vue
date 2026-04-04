<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    repositories: { type: Object, default: () => ({}) },
    providers:    { type: Array, default: () => [] },
    isManager:    { type: Boolean, default: false },
});
</script>

<template>
    <Head title="Git Repositories" />

    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Git Repositories</h1>
                <p class="mt-0.5 text-sm text-gray-500">Manage connected repositories</p>
            </div>
            <a v-if="isManager" href="/git-repositories/create"
               class="rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0]">
                + Add Repository
            </a>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Repository</th>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3">Default Branch</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="repo in (repositories.data || [])" :key="repo.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ repo.name }}</p>
                            <p class="text-xs text-gray-400">{{ repo.url }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ repo.provider_name }}</td>
                        <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ repo.default_branch }}</td>
                        <td class="px-4 py-3">
                            <span :class="repo.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                  class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                {{ repo.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!(repositories.data || []).length">
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">No repositories connected yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
