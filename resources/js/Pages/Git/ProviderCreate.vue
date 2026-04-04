<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const form = useForm({
    name: '',
    provider_type: 'github',
    base_url: '',
    credentials: '',
    is_active: true,
});

function submit() {
    form.post('/git-providers');
}
</script>

<template>
    <Head title="Add Git Provider" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Add Git Provider</h1>
            <p class="mt-0.5 text-sm text-gray-500">Configure a new Git integration</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 max-w-xl">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input v-model="form.name" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="e.g. eWards GitHub" />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Provider Type *</label>
                <select v-model="form.provider_type" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="github">GitHub</option>
                    <option value="codecommit">CodeCommit</option>
                    <option value="gitlab">GitLab</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Base URL</label>
                <input v-model="form.base_url" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="https://github.com" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Credentials / Token</label>
                <input type="password" v-model="form.credentials" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Personal access token" />
                <p class="mt-1 text-xs text-gray-400">Encrypted at rest</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="/git-providers" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#5e16bd] px-6 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    {{ form.processing ? 'Saving...' : 'Add Provider' }}
                </button>
            </div>
        </form>
    </div>
</template>
