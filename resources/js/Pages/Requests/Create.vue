<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    merchants: { type: Array, default: () => [] },
});

const form = useForm({
    title: '',
    description: '',
    merchant_id: '',
    type: '',
    urgency: '',
});

const duplicates = ref([]);
const checkingDuplicates = ref(false);
const merchantSearch = ref('');

let debounceTimer = null;

watch(() => form.title, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    if (val.length >= 3) {
        debounceTimer = setTimeout(async () => {
            checkingDuplicates.value = true;
            try {
                const { data } = await axios.get('/api/v1/requests/check-duplicates', {
                    params: { title: val },
                });
                duplicates.value = data.data || data || [];
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

function mergeTo(requestId) {
    if (confirm('This will merge your request into the existing one. Continue?')) {
        form.post(`/requests/${requestId}/merge`);
    }
}
</script>

<template>
    <Head title="New Request" />

    <div class="mx-auto max-w-2xl">
        <h1 class="mb-6 text-2xl font-bold text-gray-900">New Request</h1>

        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Title -->
                <div>
                    <label for="title" class="mb-1 block text-sm font-medium text-gray-700">Title</label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.title }"
                    />
                    <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>

                    <!-- Duplicate warning -->
                    <div v-if="duplicates.length" class="mt-2 rounded-lg border border-yellow-300 bg-yellow-50 p-3">
                        <p class="text-sm font-medium text-yellow-800">Similar requests found:</p>
                        <ul class="mt-2 space-y-2">
                            <li v-for="dup in duplicates" :key="dup.id" class="flex items-center justify-between text-sm">
                                <span class="text-yellow-700">{{ dup.title }}</span>
                                <button
                                    type="button"
                                    @click="mergeTo(dup.id)"
                                    class="rounded bg-yellow-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-yellow-700"
                                >
                                    Merge
                                </button>
                            </li>
                        </ul>
                    </div>
                    <p v-if="checkingDuplicates" class="mt-1 text-xs text-gray-400">Checking for duplicates...</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.description }"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>

                <!-- Merchant -->
                <div>
                    <label for="merchant" class="mb-1 block text-sm font-medium text-gray-700">Merchant</label>
                    <input
                        v-model="merchantSearch"
                        type="text"
                        placeholder="Search merchants..."
                        class="mb-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    />
                    <select
                        id="merchant"
                        v-model="form.merchant_id"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.merchant_id }"
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
                    <p v-if="form.errors.merchant_id" class="mt-1 text-sm text-red-600">{{ form.errors.merchant_id }}</p>
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                    <select
                        id="type"
                        v-model="form.type"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.type }"
                    >
                        <option value="">Select type</option>
                        <option value="bug">Bug</option>
                        <option value="new_feature">New Feature</option>
                        <option value="improvement">Improvement</option>
                    </select>
                    <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                </div>

                <!-- Urgency -->
                <div>
                    <label for="urgency" class="mb-1 block text-sm font-medium text-gray-700">Urgency</label>
                    <select
                        id="urgency"
                        v-model="form.urgency"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.urgency }"
                    >
                        <option value="">Select urgency</option>
                        <option value="merchant_blocked">Merchant Blocked</option>
                        <option value="merchant_unhappy">Merchant Unhappy</option>
                        <option value="nice_to_have">Nice to Have</option>
                    </select>
                    <p v-if="form.errors.urgency" class="mt-1 text-sm text-red-600">{{ form.errors.urgency }}</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
