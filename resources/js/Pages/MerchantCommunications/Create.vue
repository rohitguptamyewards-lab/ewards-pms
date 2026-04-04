<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    merchants: { type: Array, default: () => [] },
    features:  { type: Array, default: () => [] },
});

const form = useForm({
    merchant_id: '',
    feature_id: null,
    channel: 'call',
    summary: '',
    commitment_made: false,
    commitment_date: '',
});

function submit() {
    form.post('/merchant-communications');
}
</script>

<template>
    <Head title="Log Communication" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Log Communication</h1>
            <p class="mt-0.5 text-sm text-gray-500">Record a merchant interaction</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 max-w-xl">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Merchant *</label>
                <select v-model="form.merchant_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="">Select merchant...</option>
                    <option v-for="m in merchants" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
                <p v-if="form.errors.merchant_id" class="mt-1 text-xs text-red-500">{{ form.errors.merchant_id }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Channel *</label>
                <select v-model="form.channel" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="call">Call</option>
                    <option value="email">Email</option>
                    <option value="meeting">Meeting</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Linked Feature</label>
                <select v-model="form.feature_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option :value="null">None</option>
                    <option v-for="f in features" :key="f.id" :value="f.id">{{ f.title }}</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Summary *</label>
                <textarea v-model="form.summary" rows="4"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                          placeholder="Key points discussed..." />
                <p v-if="form.errors.summary" class="mt-1 text-xs text-red-500">{{ form.errors.summary }}</p>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.commitment_made"
                           class="h-4 w-4 rounded border-gray-300 text-[#4e1a77] focus:ring-[#4e1a77]" />
                    <span class="text-sm font-medium text-gray-700">Commitment was made</span>
                </label>

                <div v-if="form.commitment_made" class="mt-3 pl-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Commitment Date</label>
                    <input type="date" v-model="form.commitment_date"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                    <p class="mt-1 text-xs text-gray-400">An alert will be triggered if the linked feature slips past this date.</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="/merchant-communications" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#4e1a77] px-6 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    {{ form.processing ? 'Saving...' : 'Log Communication' }}
                </button>
            </div>
        </form>
    </div>
</template>
