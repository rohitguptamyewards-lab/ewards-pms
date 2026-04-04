<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

defineOptions({ layout: AppLayout });

const props = defineProps({
    idea: { type: Object, required: true },
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
const isManager = computed(() => ['cto', 'ceo', 'manager'].includes(role.value));
const updating = ref(false);

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

async function changeStatus(status) {
    updating.value = true;
    try {
        await axios.put(`/api/v1/ideas/${props.idea.id}`, { status });
        window.location.reload();
    } catch (e) {
        alert(e.response?.data?.message || 'Failed');
    } finally {
        updating.value = false;
    }
}

const statusConfig = {
    new:          { label: 'New',          bg: 'bg-blue-100 text-blue-700' },
    under_review: { label: 'Under Review', bg: 'bg-yellow-100 text-yellow-700' },
    promoted:     { label: 'Promoted',     bg: 'bg-emerald-100 text-emerald-700' },
    archived:     { label: 'Archived',     bg: 'bg-gray-100 text-gray-500' },
};
</script>

<template>
    <Head :title="idea.title" />

    <div>
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/ideas" class="hover:text-[#5e16bd]">Ideas</Link>
            <span>/</span>
            <span class="text-gray-800 font-medium">{{ idea.title }}</span>
        </div>

        <div class="mx-auto max-w-3xl">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">{{ idea.title }}</h1>
                            <p class="mt-1 text-sm text-gray-500">Captured by {{ idea.creator_name }} on {{ formatDate(idea.created_at) }}</p>
                        </div>
                        <span :class="statusConfig[idea.status]?.bg || 'bg-gray-100 text-gray-600'"
                              class="rounded-full px-3 py-1 text-xs font-semibold">
                            {{ statusConfig[idea.status]?.label || idea.status }}
                        </span>
                    </div>
                </div>

                <div class="space-y-4 p-6">
                    <div v-if="idea.description">
                        <h3 class="text-xs font-bold uppercase text-gray-400 mb-1">Description</h3>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ idea.description }}</p>
                    </div>

                    <div v-if="idea.source">
                        <h3 class="text-xs font-bold uppercase text-gray-400 mb-1">Source</h3>
                        <p class="text-sm text-gray-700">{{ idea.source }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div>
                            <h3 class="text-xs font-bold uppercase text-gray-400 mb-1">Last Activity</h3>
                            <p class="text-sm text-gray-700">{{ formatDate(idea.last_activity_at) }}</p>
                        </div>
                        <div v-if="idea.promoted_to_type">
                            <h3 class="text-xs font-bold uppercase text-gray-400 mb-1">Promoted To</h3>
                            <p class="text-sm text-gray-700 capitalize">{{ idea.promoted_to_type }} #{{ idea.promoted_to_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div v-if="isManager && idea.status !== 'promoted' && idea.status !== 'archived'"
                     class="flex items-center gap-2 border-t border-gray-100 px-6 py-4">
                    <button v-if="idea.status === 'new'" @click="changeStatus('under_review')" :disabled="updating"
                            class="rounded-lg bg-yellow-100 px-3 py-1.5 text-xs font-semibold text-yellow-700 hover:bg-yellow-200">
                        Mark Under Review
                    </button>
                    <button @click="changeStatus('archived')" :disabled="updating"
                            class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-200">
                        Archive
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
