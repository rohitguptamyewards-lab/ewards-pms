<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    modules:     { type: Array, default: () => [] },
    teamMembers: { type: Array, default: () => [] },
});

const form = useForm({
    title:           '',
    description:     '',
    type:            '',
    priority:        'p2',
    module_id:       '',
    status:          'backlog',
    deadline:        '',
    estimated_hours: '',
    assigned_to:     '',
});

function submit() {
    form.post('/features');
}
</script>

<template>
    <Head title="New Feature" />

    <div class="mx-auto max-w-2xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/features" class="hover:text-gray-700">Features</Link>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-900">New Feature</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-4">
                <h1 class="text-lg font-bold text-gray-900">New Feature</h1>
                <p class="mt-0.5 text-sm text-gray-500">Add a feature to the product pipeline</p>
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
                        placeholder="Feature title"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none transition"
                        :class="{ 'border-red-400 bg-red-50': form.errors.title }"
                    />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <!-- Type + Priority -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="type" class="mb-1.5 block text-sm font-semibold text-gray-700">Type</label>
                        <select
                            id="type"
                            v-model="form.type"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none transition"
                            :class="{ 'border-red-400': form.errors.type }"
                        >
                            <option value="">Select type</option>
                            <option value="new_feature">New Feature</option>
                            <option value="improvement">Improvement</option>
                            <option value="bug">Bug Fix</option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
                    </div>
                    <div>
                        <label for="priority" class="mb-1.5 block text-sm font-semibold text-gray-700">Priority</label>
                        <select
                            id="priority"
                            v-model="form.priority"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none transition"
                        >
                            <option value="p0">P0 · Critical</option>
                            <option value="p1">P1 · High</option>
                            <option value="p2">P2 · Medium</option>
                            <option value="p3">P3 · Low</option>
                        </select>
                    </div>
                </div>

                <!-- Module + Status -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="module_id" class="mb-1.5 block text-sm font-semibold text-gray-700">Module</label>
                        <select
                            id="module_id"
                            v-model="form.module_id"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none transition"
                        >
                            <option value="">No module</option>
                            <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="mb-1.5 block text-sm font-semibold text-gray-700">Initial Status</label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none transition"
                        >
                            <option value="backlog">Backlog</option>
                            <option value="in_progress">In Progress</option>
                        </select>
                    </div>
                </div>

                <!-- Assigned To + Deadline -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="assigned_to" class="mb-1.5 block text-sm font-semibold text-gray-700">Assign To</label>
                        <select
                            id="assigned_to"
                            v-model="form.assigned_to"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none transition"
                        >
                            <option value="">Unassigned</option>
                            <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="deadline" class="mb-1.5 block text-sm font-semibold text-gray-700">Deadline</label>
                        <input
                            id="deadline"
                            v-model="form.deadline"
                            type="date"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none transition"
                            :class="{ 'border-red-400': form.errors.deadline }"
                        />
                        <p v-if="form.errors.deadline" class="mt-1 text-xs text-red-600">{{ form.errors.deadline }}</p>
                    </div>
                </div>

                <!-- Estimated Hours -->
                <div>
                    <label for="estimated_hours" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Estimated Hours
                        <span class="ml-1 text-xs font-normal text-gray-400">(optional)</span>
                    </label>
                    <input
                        id="estimated_hours"
                        v-model.number="form.estimated_hours"
                        type="number"
                        step="0.5"
                        min="0"
                        placeholder="e.g. 8"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none transition"
                        :class="{ 'border-red-400': form.errors.estimated_hours }"
                    />
                    <p v-if="form.errors.estimated_hours" class="mt-1 text-xs text-red-600">{{ form.errors.estimated_hours }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Description
                        <span class="ml-1 text-xs font-normal text-gray-400">(optional)</span>
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        placeholder="What does this feature do? Why is it needed?"
                        class="block w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none transition"
                        :class="{ 'border-red-400': form.errors.description }"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                    <Link
                        href="/features"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#5e16bd] px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#4c12a1] disabled:opacity-50 transition-colors"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ form.processing ? 'Creating...' : 'Create Feature' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
