<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    merchants: { type: Array, default: () => [] },
});

const form = useForm({
    title:       '',
    description: '',
    merchant_id: '',
    type:        '',
    urgency:     '',
});

const duplicates        = ref([]);
const checkingDuplicates = ref(false);
const merchantSearch    = ref('');

let debounceTimer = null;

watch(() => form.title, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    if (val.length >= 3) {
        debounceTimer = setTimeout(async () => {
            checkingDuplicates.value = true;
            try {
                const { data } = await axios.get('/api/v1/requests/check-duplicates', { params: { title: val } });
                duplicates.value = data.duplicates || [];
            } catch {
                duplicates.value = [];
            } finally {
                checkingDuplicates.value = false;
            }
        }, 400);
    } else {
        duplicates.value = [];
    }
});

function submit() {
    form.post('/requests');
}

async function mergeTo(targetId) {
    if (!confirm('This will submit your request and merge it with the selected existing request. Continue?')) return;
    try {
        // Create this request first via API (returns JSON)
        const { data: newReq } = await axios.post('/api/v1/requests', {
            title:       form.title,
            description: form.description,
            merchant_id: form.merchant_id || null,
            type:        form.type,
            urgency:     form.urgency,
        });
        // Merge the newly created request into the existing target
        await axios.post(`/api/v1/requests/${newReq.id}/merge`, { target_id: targetId });
        router.visit('/requests');
    } catch (e) {
        const errors = e.response?.data?.errors;
        if (errors) {
            const msgs = Object.values(errors).flat().join('\n');
            alert('Please fix the following before merging:\n' + msgs);
        } else {
            alert(e.response?.data?.message || 'Failed to merge request.');
        }
    }
}
</script>

<template>
    <Head title="New Request" />

    <div class="mx-auto max-w-2xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/requests" class="hover:text-gray-700">Requests</Link>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-900">New Request</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-4">
                <h1 class="text-lg font-bold text-gray-900">New Request</h1>
                <p class="mt-0.5 text-sm text-gray-500">Submit a merchant feature request or bug report</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 p-6">
                <!-- Title -->
                <div>
                    <label for="title" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Title <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        required
                        placeholder="Brief description of the request"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        :class="{ 'border-red-400 bg-red-50': form.errors.title }"
                    />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>

                    <!-- Duplicate check spinner -->
                    <p v-if="checkingDuplicates" class="mt-1 flex items-center gap-1 text-xs text-gray-400">
                        <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Checking for similar requests...
                    </p>

                    <!-- Duplicate warning -->
                    <div v-if="duplicates.length" class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3">
                        <p class="text-sm font-semibold text-amber-800">⚠ Similar requests found — consider merging:</p>
                        <ul class="mt-2 space-y-2">
                            <li v-for="dup in duplicates" :key="dup.id" class="flex items-center justify-between text-sm">
                                <span class="text-amber-700 truncate mr-3">{{ dup.title }}</span>
                                <button
                                    type="button"
                                    @click="mergeTo(dup.id)"
                                    class="shrink-0 rounded-md bg-amber-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-amber-700 transition-colors"
                                >
                                    Merge into this
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Detailed description of the request..."
                        class="block w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        :class="{ 'border-red-400': form.errors.description }"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                </div>

                <!-- Merchant -->
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">Merchant</label>
                    <input
                        v-model="merchantSearch"
                        type="text"
                        placeholder="Search merchants..."
                        class="mb-1.5 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none"
                    />
                    <select
                        id="merchant"
                        v-model="form.merchant_id"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        :class="{ 'border-red-400': form.errors.merchant_id }"
                    >
                        <option value="">Select a merchant</option>
                        <option
                            v-for="m in merchants.filter(m => !merchantSearch || m.name.toLowerCase().includes(merchantSearch.toLowerCase()))"
                            :key="m.id"
                            :value="m.id"
                        >
                            {{ m.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.merchant_id" class="mt-1 text-xs text-red-600">{{ form.errors.merchant_id }}</p>
                </div>

                <!-- Type + Urgency row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="type" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Type <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="type"
                            v-model="form.type"
                            required
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400': form.errors.type }"
                        >
                            <option value="">Select type</option>
                            <option value="bug">Bug</option>
                            <option value="new_feature">New Feature</option>
                            <option value="improvement">Improvement</option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
                    </div>
                    <div>
                        <label for="urgency" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Urgency <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="urgency"
                            v-model="form.urgency"
                            required
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400': form.errors.urgency }"
                        >
                            <option value="">Select urgency</option>
                            <option value="merchant_blocked">Merchant Blocked</option>
                            <option value="merchant_unhappy">Merchant Unhappy</option>
                            <option value="nice_to_have">Nice to Have</option>
                        </select>
                        <p v-if="form.errors.urgency" class="mt-1 text-xs text-red-600">{{ form.errors.urgency }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                    <Link
                        href="/requests"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#3d1560] disabled:opacity-50 transition-colors"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
