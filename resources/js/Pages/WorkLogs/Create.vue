<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    projects: { type: Array, default: () => [] },
    lastEndTime: { type: String, default: null },
});

const today = new Date().toISOString().slice(0, 10);

// Default start_time to lastEndTime (HH:MM) or current time
function defaultStartTime() {
    if (props.lastEndTime) {
        return props.lastEndTime.slice(0, 5); // "HH:MM:SS" → "HH:MM"
    }
    const now = new Date();
    return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
}

const form = useForm({
    project_id:  '',
    task_id:     '',
    log_date:    today,
    start_time:  defaultStartTime(),
    end_time:    '',
    status:      'done',
    note:        '',
    blocker:     '',
});

const filteredTasks = computed(() => {
    if (!form.project_id) return [];
    const project = props.projects.find(p => p.id === Number(form.project_id));
    return project?.tasks || [];
});

// Calculate hours from start/end time
const calculatedHours = computed(() => {
    if (!form.start_time || !form.end_time) return null;
    const [sh, sm] = form.start_time.split(':').map(Number);
    const [eh, em] = form.end_time.split(':').map(Number);
    const diffMin = (eh * 60 + em) - (sh * 60 + sm);
    if (diffMin <= 0) return null;
    const hours = diffMin / 60;
    return Math.round(hours * 100) / 100;
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
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/work-logs" class="hover:text-gray-700">Work Logs</Link>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-900">Log Work</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-4">
                <h1 class="text-lg font-bold text-gray-900">Log Work</h1>
                <p class="mt-0.5 text-sm text-gray-500">Record your daily progress</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 p-6">
                <!-- Project -->
                <div>
                    <label for="project_id" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Project <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="project_id"
                        v-model="form.project_id"
                        required
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        :class="{ 'border-red-400 bg-red-50': form.errors.project_id }"
                    >
                        <option value="">Select a project</option>
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.project_id" class="mt-1 text-xs text-red-600">{{ form.errors.project_id }}</p>
                </div>

                <!-- Task (optional) -->
                <div>
                    <label for="task_id" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Task
                        <span class="ml-1 text-xs font-normal text-gray-400">(optional)</span>
                    </label>
                    <select
                        id="task_id"
                        v-model="form.task_id"
                        :disabled="!form.project_id"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="{ 'border-red-400': form.errors.task_id }"
                    >
                        <option value="">{{ form.project_id ? 'No specific task' : 'Select a project first' }}</option>
                        <option v-for="task in filteredTasks" :key="task.id" :value="task.id">
                            {{ task.title }}
                        </option>
                    </select>
                    <p v-if="form.errors.task_id" class="mt-1 text-xs text-red-600">{{ form.errors.task_id }}</p>
                </div>

                <!-- Date -->
                <div>
                    <label for="log_date" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="log_date"
                        v-model="form.log_date"
                        type="date"
                        required
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        :class="{ 'border-red-400': form.errors.log_date }"
                    />
                    <p v-if="form.errors.log_date" class="mt-1 text-xs text-red-600">{{ form.errors.log_date }}</p>
                </div>

                <!-- Start Time + End Time + Calculated Hours -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="start_time" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Start Time <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="start_time"
                            v-model="form.start_time"
                            type="time"
                            required
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400': form.errors.start_time }"
                        />
                        <p v-if="lastEndTime && form.start_time === lastEndTime?.slice(0, 5)" class="mt-1 text-xs text-gray-400">
                            Auto-filled from last log
                        </p>
                        <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-600">{{ form.errors.start_time }}</p>
                    </div>
                    <div>
                        <label for="end_time" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            End Time <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="end_time"
                            v-model="form.end_time"
                            type="time"
                            required
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400': form.errors.end_time }"
                        />
                        <p v-if="form.errors.end_time" class="mt-1 text-xs text-red-600">{{ form.errors.end_time }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Hours</label>
                        <div
                            class="flex h-[42px] items-center rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm font-medium"
                            :class="calculatedHours ? 'text-[#4e1a77]' : 'text-gray-400'"
                        >
                            {{ calculatedHours ? `${calculatedHours} hrs` : '--' }}
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="status"
                        v-model="form.status"
                        required
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        :class="{ 'border-red-400': form.errors.status }"
                    >
                        <option value="done">Done</option>
                        <option value="in_progress">In Progress</option>
                        <option value="blocked">Blocked</option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                </div>

                <!-- Note -->
                <div>
                    <label for="note" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Note
                        <span class="ml-1 text-xs font-normal text-gray-400">(optional)</span>
                    </label>
                    <textarea
                        id="note"
                        v-model="form.note"
                        rows="3"
                        placeholder="What did you work on?"
                        class="block w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        :class="{ 'border-red-400': form.errors.note }"
                    />
                    <p v-if="form.errors.note" class="mt-1 text-xs text-red-600">{{ form.errors.note }}</p>
                </div>

                <!-- Blocker (conditional) -->
                <div v-if="form.status === 'blocked'">
                    <label for="blocker" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Blocker / Issue
                        <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="blocker"
                        v-model="form.blocker"
                        rows="2"
                        placeholder="Describe what is blocking you..."
                        class="block w-full resize-none rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm focus:border-red-400 focus:ring-1 focus:ring-red-400 outline-none transition"
                        :class="{ 'border-red-400': form.errors.blocker }"
                    />
                    <p v-if="form.errors.blocker" class="mt-1 text-xs text-red-600">{{ form.errors.blocker }}</p>
                </div>
                <div v-else>
                    <label for="blocker" class="mb-1.5 block text-sm font-semibold text-gray-700">
                        Blocker / Issue
                        <span class="ml-1 text-xs font-normal text-gray-400">(optional)</span>
                    </label>
                    <textarea
                        id="blocker"
                        v-model="form.blocker"
                        rows="2"
                        placeholder="Any blockers or issues encountered?"
                        class="block w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                    />
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                    <Link
                        href="/work-logs"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || !calculatedHours"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#3d1560] disabled:opacity-50 transition-colors"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ form.processing ? 'Saving...' : 'Log Work' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
