<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    member:         { type: Object, required: true },
    recentTasks:    { type: Array, default: () => [] },
    hoursThisMonth: { type: [Number, String], default: 0 },
    featuresCount:  { type: Number, default: 0 },
});

function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
}
</script>

<template>
    <Head :title="member.name" />

    <div>
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/team-members" class="hover:text-[#5e16bd]">Team</Link>
            <span>/</span>
            <span class="text-gray-800 font-medium">{{ member.name }}</span>
        </div>

        <div class="mx-auto max-w-2xl">
            <!-- Profile card -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-[#1e0a45] to-[#5e16bd] px-6 py-8 text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/20 text-2xl font-bold text-white">
                        {{ initials(member.name) }}
                    </div>
                    <h1 class="mt-3 text-xl font-bold text-white">{{ member.name }}</h1>
                    <p class="mt-1 text-sm text-white/70 capitalize">{{ (member.role || '').replace('_', ' ') }}</p>
                    <p v-if="member.email" class="mt-0.5 text-xs text-white/50">{{ member.email }}</p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100">
                    <div class="py-4 text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ featuresCount }}</p>
                        <p class="text-xs text-gray-400">Features</p>
                    </div>
                    <div class="py-4 text-center">
                        <p class="text-2xl font-bold text-[#5e16bd]">{{ Number(hoursThisMonth).toFixed(1) }}h</p>
                        <p class="text-xs text-gray-400">Hours This Month</p>
                    </div>
                    <div class="py-4 text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ recentTasks.length }}</p>
                        <p class="text-xs text-gray-400">Active Tasks</p>
                    </div>
                </div>

                <!-- Recent tasks -->
                <div v-if="recentTasks.length" class="p-5">
                    <h3 class="text-xs font-bold uppercase text-gray-400 mb-3">Recent Tasks</h3>
                    <div class="space-y-2">
                        <div v-for="task in recentTasks" :key="task.id" class="flex items-center justify-between">
                            <p class="text-sm text-gray-700">{{ task.title }}</p>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 capitalize">{{ (task.status || '').replace('_', ' ') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
