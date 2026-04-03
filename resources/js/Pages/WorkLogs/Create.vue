<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    projects: { type: Array, default: () => [] },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    project_id: '',
    task_id: '',
    log_date: today,
    hours_spent: 1,
    note: '',
    blocker: '',
});

const filteredTasks = computed(() => {
    if (!form.project_id) return [];
    const project = props.projects.find(p => p.id === Number(form.project_id));
    return project?.tasks || [];
});

watch(() => form.project_id, () => {
    form.task_id = '';
});

function submit() {
    form.post('/work-logs');
}
</script>

<template>
    <Head title="Log Work" />

    <div class="mx-auto max-w-2xl">
        <h1 class="mb-6 text-2xl font-bold text-gray-900">Log Work</h1>

        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Project -->
                <div>
                    <label for="project_id" class="mb-1 block text-sm font-medium text-gray-700">Project</label>
                    <select
                        id="project_id"
                        v-model="form.project_id"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.project_id }"
                    >
                        <option value="">Select a project</option>
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.project_id" class="mt-1 text-sm text-red-600">{{ form.errors.project_id }}</p>
                </div>

                <!-- Task -->
                <div>
                    <label for="task_id" class="mb-1 block text-sm font-medium text-gray-700">Task</label>
                    <select
                        id="task_id"
                        v-model="form.task_id"
                        required
                        :disabled="!form.project_id"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400"
                        :class="{ 'border-red-500': form.errors.task_id }"
                    >
                        <option value="">{{ form.project_id ? 'Select a task' : 'Select a project first' }}</option>
                        <option v-for="task in filteredTasks" :key="task.id" :value="task.id">
                            {{ task.title }}
                        </option>
                    </select>
                    <p v-if="form.errors.task_id" class="mt-1 text-sm text-red-600">{{ form.errors.task_id }}</p>
                </div>

                <!-- Date -->
                <div>
                    <label for="log_date" class="mb-1 block text-sm font-medium text-gray-700">Date</label>
                    <input
                        id="log_date"
                        v-model="form.log_date"
                        type="date"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.log_date }"
                    />
                    <p v-if="form.errors.log_date" class="mt-1 text-sm text-red-600">{{ form.errors.log_date }}</p>
                </div>

                <!-- Hours -->
                <div>
                    <label for="hours_spent" class="mb-1 block text-sm font-medium text-gray-700">Hours Spent</label>
                    <input
                        id="hours_spent"
                        v-model.number="form.hours_spent"
                        type="number"
                        step="0.25"
                        min="0.25"
                        max="24"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.hours_spent }"
                    />
                    <p v-if="form.errors.hours_spent" class="mt-1 text-sm text-red-600">{{ form.errors.hours_spent }}</p>
                </div>

                <!-- Note -->
                <div>
                    <label for="note" class="mb-1 block text-sm font-medium text-gray-700">Note</label>
                    <textarea
                        id="note"
                        v-model="form.note"
                        rows="3"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.note }"
                    />
                    <p v-if="form.errors.note" class="mt-1 text-sm text-red-600">{{ form.errors.note }}</p>
                </div>

                <!-- Blocker -->
                <div>
                    <label for="blocker" class="mb-1 block text-sm font-medium text-gray-700">Blocker / Issue</label>
                    <textarea
                        id="blocker"
                        v-model="form.blocker"
                        rows="2"
                        placeholder="Optional - describe any blockers or issues"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.blocker }"
                    />
                    <p v-if="form.errors.blocker" class="mt-1 text-sm text-red-600">{{ form.errors.blocker }}</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Saving...' : 'Log Work' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
