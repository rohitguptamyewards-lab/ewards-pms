<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    teamMembers: { type: Array, default: () => [] },
});

const form = useForm({
    name:        '',
    description: '',
    status:      'active',
    priority:    'medium',
    owner_id:    '',
    member_ids:  [],
    start_date:  '',
    end_date:    '',
});

function toggleMember(id) {
    const idx = form.member_ids.indexOf(id);
    if (idx === -1) {
        form.member_ids.push(id);
    } else {
        form.member_ids.splice(idx, 1);
    }
}

function submit() {
    form.post('/projects');
}
</script>

<template>
    <Head title="New Project" />

    <div class="mx-auto max-w-2xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/projects" class="hover:text-gray-700">Projects</Link>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-900">New Project</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-4">
                <h1 class="text-lg font-bold text-gray-900">New Project</h1>
                <p class="mt-0.5 text-sm text-gray-500">Fill in the details to create a new project</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 p-6">
                <!-- Name -->
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Project name"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        :class="{ 'border-red-400 bg-red-50': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="mb-1.5 block text-sm font-semibold text-gray-700">Description</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        placeholder="What is this project about?"
                        class="block w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        :class="{ 'border-red-400': form.errors.description }"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                </div>

                <!-- Status + Priority -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="mb-1.5 block text-sm font-semibold text-gray-700">Status</label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400': form.errors.status }"
                        >
                            <option value="active">Active</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                        </select>
                        <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                    </div>
                    <div>
                        <label for="priority" class="mb-1.5 block text-sm font-semibold text-gray-700">Priority</label>
                        <select
                            id="priority"
                            v-model="form.priority"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400': form.errors.priority }"
                        >
                            <option value="critical">P0 · Critical</option>
                            <option value="high">P1 · High</option>
                            <option value="medium">P2 · Medium</option>
                            <option value="low">P3 · Low</option>
                        </select>
                        <p v-if="form.errors.priority" class="mt-1 text-xs text-red-600">{{ form.errors.priority }}</p>
                    </div>
                </div>

                <!-- Owner -->
                <div>
                    <label for="owner_id" class="mb-1.5 block text-sm font-semibold text-gray-700">Owner</label>
                    <select
                        id="owner_id"
                        v-model="form.owner_id"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        :class="{ 'border-red-400': form.errors.owner_id }"
                    >
                        <option value="">Select an owner</option>
                        <option v-for="member in teamMembers" :key="member.id" :value="member.id">
                            {{ member.name }} ({{ member.role }})
                        </option>
                    </select>
                    <p v-if="form.errors.owner_id" class="mt-1 text-xs text-red-600">{{ form.errors.owner_id }}</p>
                </div>

                <!-- Members (multi-select checkboxes) -->
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Members
                        <span class="ml-1 text-xs font-normal text-gray-400">({{ form.member_ids.length }} selected)</span>
                    </label>
                    <div
                        class="max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-3 space-y-1"
                        :class="{ 'border-red-400': form.errors.member_ids }"
                    >
                        <label
                            v-for="member in teamMembers"
                            :key="member.id"
                            class="flex items-center gap-3 rounded-lg px-2 py-1.5 cursor-pointer hover:bg-white transition-colors"
                        >
                            <input
                                type="checkbox"
                                :checked="form.member_ids.includes(member.id)"
                                @change="toggleMember(member.id)"
                                class="h-4 w-4 rounded border-gray-300 text-[#4e1a77] focus:ring-[#4e1a77]"
                            />
                            <span class="text-sm text-gray-800">{{ member.name }}</span>
                            <span class="ml-auto text-xs text-gray-400 capitalize">{{ member.role }}</span>
                        </label>
                        <p v-if="!teamMembers.length" class="px-2 py-1 text-sm text-gray-400">No team members available.</p>
                    </div>
                    <p v-if="form.errors.member_ids" class="mt-1 text-xs text-red-600">{{ form.errors.member_ids }}</p>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="mb-1.5 block text-sm font-semibold text-gray-700">Start Date</label>
                        <input
                            id="start_date"
                            v-model="form.start_date"
                            type="date"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400': form.errors.start_date }"
                        />
                        <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
                    </div>
                    <div>
                        <label for="end_date" class="mb-1.5 block text-sm font-semibold text-gray-700">End Date (Deadline)</label>
                        <input
                            id="end_date"
                            v-model="form.end_date"
                            type="date"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400': form.errors.end_date }"
                        />
                        <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                    <Link
                        href="/projects"
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
                        {{ form.processing ? 'Creating...' : 'Create Project' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
