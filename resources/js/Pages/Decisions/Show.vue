<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    decision:            { type: Object, required: true },
    supersededDecisions: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

const statusConfig = {
    proposed:   { label: 'Proposed',   bg: 'bg-blue-100 text-blue-700' },
    open:       { label: 'Open',       bg: 'bg-yellow-100 text-yellow-700' },
    decided:    { label: 'Decided',    bg: 'bg-emerald-100 text-emerald-700' },
    superseded: { label: 'Superseded', bg: 'bg-gray-100 text-gray-500 line-through' },
};

const d = computed(() => props.decision);
</script>

<template>
    <Head :title="d.title" />

    <div>
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/decisions" class="hover:text-[#5e16bd]">Decision Log</Link>
            <span>/</span>
            <span class="text-gray-800 font-medium">{{ d.title }}</span>
        </div>

        <div v-if="flash?.success" class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ flash.success }}
        </div>

        <div class="mx-auto max-w-3xl space-y-6">
            <!-- Main card -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4 flex items-start justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ d.title }}</h1>
                        <p class="mt-1 text-sm text-gray-500">Decided by {{ d.decision_maker_name }} on {{ formatDate(d.decision_date) }}</p>
                    </div>
                    <span :class="statusConfig[d.status]?.bg || 'bg-gray-100 text-gray-600'"
                          class="rounded-full px-3 py-1 text-xs font-semibold">
                        {{ statusConfig[d.status]?.label || d.status }}
                    </span>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <h3 class="text-xs font-bold uppercase text-gray-400 mb-1">Context</h3>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ d.context }}</p>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold uppercase text-gray-400 mb-1">Options Considered</h3>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ d.options_considered }}</p>
                    </div>

                    <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4">
                        <h3 class="text-xs font-bold uppercase text-emerald-600 mb-1">Chosen Option</h3>
                        <p class="text-sm text-gray-800 whitespace-pre-line">{{ d.chosen_option }}</p>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold uppercase text-gray-400 mb-1">Rationale</h3>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ d.rationale }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4">
                        <div v-if="d.linked_to_type">
                            <h3 class="text-xs font-bold uppercase text-gray-400 mb-1">Linked To</h3>
                            <p class="text-sm text-gray-700 capitalize">{{ d.linked_to_type }} #{{ d.linked_to_id }}</p>
                        </div>
                        <div v-if="d.superseded_by_title">
                            <h3 class="text-xs font-bold uppercase text-orange-500 mb-1">Superseded By</h3>
                            <Link :href="`/decisions/${d.superseded_by}`" class="text-sm text-[#5e16bd] hover:underline">
                                {{ d.superseded_by_title }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decisions this one supersedes -->
            <div v-if="supersededDecisions.length" class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-3.5">
                    <h2 class="text-sm font-bold text-gray-900">This decision supersedes</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <Link v-for="sd in supersededDecisions" :key="sd.id" :href="`/decisions/${sd.id}`"
                          class="flex items-center justify-between px-6 py-3 hover:bg-[#f5f0ff]/30 transition-colors">
                        <span class="text-sm font-medium text-gray-700">{{ sd.title }}</span>
                        <span class="text-xs text-gray-400">{{ formatDate(sd.decision_date) }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
