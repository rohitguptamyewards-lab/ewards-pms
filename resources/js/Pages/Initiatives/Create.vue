<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    modules:     { type: Array, default: () => [] },
    teamMembers: { type: Array, default: () => [] },
});

const form = useForm({
    title: '',
    description: '',
    origin_type: '',
    business_case: '',
    expected_impact: '',
    owner_id: '',
    deadline: '',
    estimated_features: '',
    module_id: '',
});

function submit() {
    form.post('/initiatives');
}

const originTypes = [
    { value: 'strategic',     label: 'Strategic' },
    { value: 'data_insight',  label: 'Data Insight' },
    { value: 'tech_debt',     label: 'Tech Debt' },
    { value: 'client_demand', label: 'Client Demand' },
];

const impactTypes = [
    { value: 'revenue',            label: 'Revenue' },
    { value: 'retention',          label: 'Retention' },
    { value: 'ops_efficiency',     label: 'Ops Efficiency' },
    { value: 'platform_stability', label: 'Platform Stability' },
];
</script>

<template>
    <Head title="New Initiative" />

    <div>
        <!-- Breadcrumb -->
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/initiatives" class="hover:text-[#4e1a77]">Initiatives</Link>
            <span>/</span>
            <span class="text-gray-800 font-medium">New</span>
        </div>

        <div class="mx-auto max-w-3xl">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h1 class="text-lg font-bold text-gray-900">Create Initiative</h1>
                    <p class="mt-0.5 text-sm text-gray-500">Strategic body of work containing multiple features.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5 p-6">
                    <!-- Title -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Title <span class="text-red-500">*</span></label>
                        <input v-model="form.title" type="text" required
                               :class="{ 'border-red-400': form.errors.title }"
                               class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Description <span class="text-red-500">*</span></label>
                        <textarea v-model="form.description" rows="3" required
                                  :class="{ 'border-red-400': form.errors.description }"
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                        <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <!-- Business Case (mandatory per BR-004) -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Business Case <span class="text-red-500">*</span></label>
                        <textarea v-model="form.business_case" rows="3" required placeholder="Why are we building this?"
                                  :class="{ 'border-red-400': form.errors.business_case }"
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                        <p v-if="form.errors.business_case" class="mt-1 text-xs text-red-600">{{ form.errors.business_case }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <!-- Origin Type -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Origin Type <span class="text-red-500">*</span></label>
                            <select v-model="form.origin_type" required
                                    :class="{ 'border-red-400': form.errors.origin_type }"
                                    class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]">
                                <option value="" disabled>Select origin</option>
                                <option v-for="o in originTypes" :key="o.value" :value="o.value">{{ o.label }}</option>
                            </select>
                            <p v-if="form.errors.origin_type" class="mt-1 text-xs text-red-600">{{ form.errors.origin_type }}</p>
                        </div>

                        <!-- Expected Impact -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Expected Impact <span class="text-red-500">*</span></label>
                            <select v-model="form.expected_impact" required
                                    :class="{ 'border-red-400': form.errors.expected_impact }"
                                    class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]">
                                <option value="" disabled>Select impact</option>
                                <option v-for="i in impactTypes" :key="i.value" :value="i.value">{{ i.label }}</option>
                            </select>
                            <p v-if="form.errors.expected_impact" class="mt-1 text-xs text-red-600">{{ form.errors.expected_impact }}</p>
                        </div>

                        <!-- Owner -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Owner <span class="text-red-500">*</span></label>
                            <select v-model="form.owner_id" required
                                    :class="{ 'border-red-400': form.errors.owner_id }"
                                    class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]">
                                <option value="" disabled>Select owner</option>
                                <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                            <p v-if="form.errors.owner_id" class="mt-1 text-xs text-red-600">{{ form.errors.owner_id }}</p>
                        </div>

                        <!-- Module -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Linked Module</label>
                            <select v-model="form.module_id"
                                    class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]">
                                <option value="">None</option>
                                <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>

                        <!-- Deadline -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Deadline</label>
                            <input v-model="form.deadline" type="date"
                                   class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                        </div>

                        <!-- Estimated Features -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Estimated Features</label>
                            <input v-model="form.estimated_features" type="number" min="0"
                                   class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <Link href="/initiatives" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</Link>
                        <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#3d1560] disabled:opacity-50">
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                            {{ form.processing ? 'Creating...' : 'Create Initiative' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
