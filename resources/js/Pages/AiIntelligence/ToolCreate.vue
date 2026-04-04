<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const form = useForm({
    name: '',
    provider: '',
    capabilities: [],
    cost_per_seat_monthly: '',
    seats_purchased: 1,
    is_active: true,
});

const capabilityOptions = ['code', 'debug', 'architecture', 'content', 'research'];
const newCapability = ref('');

function addCapability() {
    if (newCapability.value && !form.capabilities.includes(newCapability.value)) {
        form.capabilities.push(newCapability.value);
    }
    newCapability.value = '';
}

function removeCapability(cap) {
    form.capabilities = form.capabilities.filter(c => c !== cap);
}

function submit() {
    form.post('/ai-tools', { preserveScroll: true });
}
</script>

<template>
    <Head title="Add AI Tool" />

    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Add AI Tool</h1>

        <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tool Name *</label>
                    <input v-model="form.name" type="text"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="e.g. GitHub Copilot" />
                    <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Provider *</label>
                    <input v-model="form.provider" type="text"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="e.g. GitHub / OpenAI" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capabilities</label>
                <div class="flex gap-2 mb-2">
                    <select v-model="newCapability" class="rounded-lg border border-gray-200 px-3 py-2 text-sm flex-1">
                        <option value="">Select capability</option>
                        <option v-for="c in capabilityOptions" :key="c" :value="c">{{ c }}</option>
                    </select>
                    <button type="button" @click="addCapability"
                            class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200">Add</button>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <span v-for="cap in form.capabilities" :key="cap"
                          class="inline-flex items-center gap-1 rounded-full bg-[#4e1a77]/10 px-2.5 py-1 text-xs font-medium text-[#4e1a77]">
                        {{ cap }}
                        <button type="button" @click="removeCapability(cap)" class="hover:text-red-500">&times;</button>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost/Seat/Month ($) *</label>
                    <input v-model="form.cost_per_seat_monthly" type="number" step="0.01" min="0"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Seats Purchased *</label>
                    <input v-model="form.seats_purchased" type="number" min="0"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-gray-300 text-[#4e1a77]" />
                <label for="is_active" class="text-sm text-gray-700">Active</label>
            </div>

            <div class="flex justify-end gap-3 pt-3">
                <a href="/ai-tools" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#4e1a77] px-5 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    Add Tool
                </button>
            </div>
        </form>
    </div>
</template>
