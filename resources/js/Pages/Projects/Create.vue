<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    teamMembers: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    description: '',
    status: 'active',
    owner_id: '',
    member_ids: [],
    deadline: '',
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
        <h1 class="mb-6 text-2xl font-bold text-gray-900">New Project</h1>

        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Name -->
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
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

                <!-- Status -->
                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.status }"
                    >
                        <option value="active">Active</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                </div>

                <!-- Owner -->
                <div>
                    <label for="owner_id" class="mb-1 block text-sm font-medium text-gray-700">Owner</label>
                    <select
                        id="owner_id"
                        v-model="form.owner_id"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.owner_id }"
                    >
                        <option value="">Select an owner</option>
                        <option v-for="member in teamMembers" :key="member.id" :value="member.id">
                            {{ member.name }} ({{ member.role }})
                        </option>
                    </select>
                    <p v-if="form.errors.owner_id" class="mt-1 text-sm text-red-600">{{ form.errors.owner_id }}</p>
                </div>

                <!-- Members (multi-select checkboxes) -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Members</label>
                    <div
                        class="max-h-48 overflow-y-auto rounded-lg border border-gray-300 p-3 space-y-2"
                        :class="{ 'border-red-500': form.errors.member_ids }"
                    >
                        <label
                            v-for="member in teamMembers"
                            :key="member.id"
                            class="flex items-center gap-2 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :checked="form.member_ids.includes(member.id)"
                                @change="toggleMember(member.id)"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="text-sm text-gray-700">{{ member.name }}</span>
                            <span class="text-xs text-gray-400">({{ member.role }})</span>
                        </label>
                        <p v-if="!teamMembers.length" class="text-sm text-gray-400">No team members available.</p>
                    </div>
                    <p v-if="form.errors.member_ids" class="mt-1 text-sm text-red-600">{{ form.errors.member_ids }}</p>
                </div>

                <!-- Deadline -->
                <div>
                    <label for="deadline" class="mb-1 block text-sm font-medium text-gray-700">Deadline</label>
                    <input
                        id="deadline"
                        v-model="form.deadline"
                        type="date"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.deadline }"
                    />
                    <p v-if="form.errors.deadline" class="mt-1 text-sm text-red-600">{{ form.errors.deadline }}</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Creating...' : 'Create Project' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
