<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    providers: { type: Array, default: () => [] },
});

const form = useForm({
    git_provider_id: '',
    name: '',
    url: '',
    default_branch: 'main',
    webhook_secret: '',
    is_active: true,
});

function submit() {
    form.post('/git-repositories');
}
</script>

<template>
    <Head title="Add Repository" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Add Repository</h1>
            <p class="mt-0.5 text-sm text-gray-500">Connect a Git repository for tracking</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 max-w-xl">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Provider *</label>
                <select v-model="form.git_provider_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="">Select provider...</option>
                    <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <p v-if="form.errors.git_provider_id" class="mt-1 text-xs text-red-500">{{ form.errors.git_provider_id }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Repository Name *</label>
                <input v-model="form.name" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="e.g. ewards-backend" />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL *</label>
                <input v-model="form.url" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="https://github.com/org/repo" />
                <p v-if="form.errors.url" class="mt-1 text-xs text-red-500">{{ form.errors.url }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Default Branch</label>
                <input v-model="form.default_branch" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Secret</label>
                <input type="password" v-model="form.webhook_secret" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Used for HMAC verification" />
                <p class="mt-1 text-xs text-gray-400">Encrypted at rest. Configure the same secret in your Git provider webhook settings.</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="/git-repositories" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-[#5e16bd] px-6 py-2 text-sm font-semibold text-white hover:bg-[#4e12a0] disabled:opacity-50">
                    {{ form.processing ? 'Saving...' : 'Add Repository' }}
                </button>
            </div>
        </form>
    </div>
</template>
