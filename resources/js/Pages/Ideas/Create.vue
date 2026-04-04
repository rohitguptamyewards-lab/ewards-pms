<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const form = useForm({
    title: '',
    description: '',
    source: '',
});

function submit() {
    form.post('/ideas');
}
</script>

<template>
    <Head title="Capture Idea" />

    <div>
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/ideas" class="hover:text-[#5e16bd]">Ideas</Link>
            <span>/</span>
            <span class="text-gray-800 font-medium">Capture</span>
        </div>

        <div class="mx-auto max-w-2xl">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h1 class="text-lg font-bold text-gray-900">Capture an Idea</h1>
                    <p class="mt-0.5 text-sm text-gray-500">Quick capture — review later. Ideas auto-archive after 90 days of inactivity.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5 p-6">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Title <span class="text-red-500">*</span></label>
                        <input v-model="form.title" type="text" required
                               :class="{ 'border-red-400': form.errors.title }"
                               class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#5e16bd] focus:ring-[#5e16bd]" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Description</label>
                        <textarea v-model="form.description" rows="3"
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#5e16bd] focus:ring-[#5e16bd]"
                                  placeholder="One-line description or context" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Source</label>
                        <input v-model="form.source" type="text"
                               class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#5e16bd] focus:ring-[#5e16bd]"
                               placeholder="Where did this idea come from?" />
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <Link href="/ideas" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</Link>
                        <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-[#5e16bd] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#4c12a1] disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : 'Capture Idea' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
