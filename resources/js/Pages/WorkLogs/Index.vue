<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    workLogs:          { type: Object, default: () => ({ data: [], links: [] }) },
    filters:           { type: Object, default: () => ({}) },
    projects:          { type: Array,  default: () => [] },
    projectsWithTasks: { type: Array,  default: () => [] },
    teamMembers:       { type: Array,  default: () => [] },
    isManager:         { type: Boolean, default: false },
    lastEndTime:       { type: String,  default: null },
    weekTotal:         { type: Number,  default: 0 },
});

/* ── Filters ── */
const showFilters = ref(false);
const localFilters = ref({
    project_id: props.filters.project_id || '',
    user_id:    props.filters.user_id    || '',
    date_from:  props.filters.date_from  || '',
    date_to:    props.filters.date_to    || '',
});

function applyFilters() {
    router.get('/work-logs', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    localFilters.value = { project_id: '', user_id: '', date_from: '', date_to: '' };
    router.get('/work-logs', {}, { preserveState: true, preserveScroll: true });
}

/* ── Inline Add Form ── */
const today = new Date().toISOString().slice(0, 10);

function defaultStartTime() {
    if (props.lastEndTime) return props.lastEndTime.slice(0, 5);
    const now = new Date();
    return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
}

const form = useForm({
    project_id: '',
    task_id:    '',
    log_date:   today,
    start_time: defaultStartTime(),
    end_time:   '',
    status:     'done',
    note:       '',
    blocker:    '',
});

const filteredTasks = computed(() => {
    if (!form.project_id) return [];
    const project = props.projectsWithTasks.find(p => p.id === Number(form.project_id));
    return project?.tasks || [];
});

const calcDuration = computed(() => {
    if (!form.start_time || !form.end_time) return null;
    const [sh, sm] = form.start_time.split(':').map(Number);
    const [eh, em] = form.end_time.split(':').map(Number);
    const diffMin = (eh * 60 + em) - (sh * 60 + sm);
    if (diffMin <= 0) return null;
    const h = Math.floor(diffMin / 60);
    const m = diffMin % 60;
    return { hours: h, mins: m, total: diffMin, display: `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}` };
});

watch(() => form.project_id, () => { form.task_id = ''; });

function submitEntry() {
    form.post('/work-logs', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.log_date = today;
            form.start_time = defaultStartTime();
            form.status = 'done';
        },
    });
}

/* ── Grouped Entries ── */
const groupedLogs = computed(() => {
    const data = props.workLogs.data || [];
    const groups = {};
    data.forEach(log => {
        const date = log.log_date || 'Unknown';
        if (!groups[date]) groups[date] = { date, logs: [], totalMins: 0 };
        groups[date].logs.push(log);
        groups[date].totalMins += Math.round(parseFloat(log.hours_spent || 0) * 60);
    });
    return Object.values(groups).sort((a, b) => b.date.localeCompare(a.date));
});

function formatDateHeader(dateStr) {
    if (!dateStr || dateStr === 'Unknown') return 'Unknown Date';
    const d = new Date(dateStr + 'T00:00:00');
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${days[d.getDay()]}, ${months[d.getMonth()]} ${d.getDate()}`;
}

function formatMinsToHM(mins) {
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

function formatWeekTotal(hours) {
    const totalMins = Math.round(hours * 60);
    const h = Math.floor(totalMins / 60);
    const m = totalMins % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:00`;
}

function formatTimeRange(log) {
    if (!log.start_time || !log.end_time) return '';
    return `${log.start_time?.slice(0,5)} - ${log.end_time?.slice(0,5)}`;
}

function formatHoursToHM(hours) {
    const totalMins = Math.round(parseFloat(hours) * 60);
    const h = Math.floor(totalMins / 60);
    const m = totalMins % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

/* ── Edit/Delete ── */
const editingId = ref(null);
const editForm = ref({});

function startEdit(log) {
    editingId.value = log.id;
    editForm.value = {
        note: log.note || '',
        start_time: log.start_time?.slice(0,5) || '',
        end_time: log.end_time?.slice(0,5) || '',
        status: log.status || 'done',
    };
}

function cancelEdit() {
    editingId.value = null;
    editForm.value = {};
}

function saveEdit(logId) {
    router.put(`/api/v1/work-logs/${logId}`, editForm.value, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
        onError: () => {},
    });
}

function deleteLog(logId) {
    if (!confirm('Delete this work log entry?')) return;
    router.delete(`/api/v1/work-logs/${logId}`, {
        preserveScroll: true,
    });
}

/* ── Project colors (deterministic from ID) ── */
const projectColors = ['#4e1a77', '#e85d04', '#2563eb', '#059669', '#dc2626', '#7c3aed', '#0891b2', '#ca8a04', '#be185d', '#6366f1'];
function getProjectColor(projectId) {
    if (!projectId) return '#9ca3af';
    return projectColors[projectId % projectColors.length];
}

/* ── Project search for dropdown ── */
const projectSearch = ref('');
const showProjectDropdown = ref(false);
const filteredProjects = computed(() => {
    const q = projectSearch.value.toLowerCase();
    if (!q) return props.projectsWithTasks;
    return props.projectsWithTasks.filter(p => p.name.toLowerCase().includes(q));
});

function selectProject(p) {
    form.project_id = p.id;
    projectSearch.value = p.name;
    showProjectDropdown.value = false;
}

function onProjectFocus() {
    showProjectDropdown.value = true;
    if (form.project_id) {
        const p = props.projectsWithTasks.find(x => x.id === Number(form.project_id));
        if (p) projectSearch.value = p.name;
    }
}

function onProjectBlur() {
    setTimeout(() => { showProjectDropdown.value = false; }, 200);
}

function getProjectName(id) {
    const p = props.projects.find(x => x.id === id) || props.projectsWithTasks.find(x => x.id === id);
    return p?.name || '';
}
</script>

<template>
    <Head title="Work Logs" />

    <div>
        <!-- ═══ Header ═══ -->
        <div class="mb-4 flex flex-col gap-3 sm:mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Work Logs</h1>
                <p class="mt-0.5 text-sm text-gray-500">Track time and progress</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Week Total -->
                <div class="flex items-center gap-2 rounded-lg border border-[#e8ddf0] bg-[#f8f4fa] px-3 py-1.5">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">This Week</span>
                    <span class="text-lg font-bold tabular-nums text-[#4e1a77]">{{ formatWeekTotal(weekTotal) }}</span>
                </div>
                <!-- Filter toggle -->
                <button
                    @click="showFilters = !showFilters"
                    :class="showFilters ? 'bg-[#4e1a77] text-white' : 'bg-white text-gray-600 border border-gray-200'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium shadow-sm hover:shadow transition-all"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    Filters
                </button>
            </div>
        </div>

        <!-- ═══ Filters (collapsible) ═══ -->
        <div v-if="showFilters" class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm animate-in">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Project</label>
                    <select v-model="localFilters.project_id" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none">
                        <option value="">All Projects</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <div v-if="isManager">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Team Member</label>
                    <select v-model="localFilters.user_id" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none">
                        <option value="">All Members</option>
                        <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Date From</label>
                    <input v-model="localFilters.date_from" type="date" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Date To</label>
                    <input v-model="localFilters.date_to" type="date" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none" />
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilters" class="rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors">Apply</button>
                    <button @click="clearFilters" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Clear</button>
                </div>
            </div>
        </div>

        <!-- ═══ Inline Entry Bar (Clockify-style) ═══ -->
        <form @submit.prevent="submitEntry" class="mb-5 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <!-- Row 1: Note + Project -->
            <div class="flex flex-col lg:flex-row">
                <!-- Description input -->
                <div class="flex-1 border-b lg:border-b-0 lg:border-r border-gray-100">
                    <input
                        v-model="form.note"
                        type="text"
                        placeholder="What have you worked on?"
                        class="w-full px-4 py-3.5 text-sm text-gray-800 placeholder-gray-400 outline-none bg-transparent"
                    />
                </div>

                <!-- Project dropdown (searchable) -->
                <div class="relative w-full lg:w-56 border-b lg:border-b-0 lg:border-r border-gray-100">
                    <div class="flex items-center px-3">
                        <span v-if="form.project_id" class="mr-2 h-2.5 w-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: getProjectColor(form.project_id) }"></span>
                        <svg v-else class="mr-2 h-4 w-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <input
                            v-model="projectSearch"
                            @focus="onProjectFocus"
                            @blur="onProjectBlur"
                            type="text"
                            placeholder="Project"
                            class="w-full py-3.5 text-sm text-gray-700 placeholder-gray-400 outline-none bg-transparent"
                        />
                    </div>
                    <!-- Dropdown -->
                    <div v-if="showProjectDropdown && filteredProjects.length" class="absolute left-0 right-0 top-full z-20 max-h-48 overflow-y-auto rounded-b-lg border border-t-0 border-gray-200 bg-white shadow-lg">
                        <button
                            v-for="p in filteredProjects"
                            :key="p.id"
                            type="button"
                            @mousedown.prevent="selectProject(p)"
                            class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-[#f8f4fa] transition-colors"
                        >
                            <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: getProjectColor(p.id) }"></span>
                            {{ p.name }}
                        </button>
                    </div>
                </div>

                <!-- Task dropdown -->
                <div class="w-full lg:w-44 border-b lg:border-b-0 lg:border-r border-gray-100">
                    <select
                        v-model="form.task_id"
                        :disabled="!form.project_id"
                        class="w-full px-3 py-3.5 text-sm text-gray-700 outline-none bg-transparent disabled:text-gray-400 disabled:cursor-not-allowed"
                    >
                        <option value="">{{ form.project_id ? 'Task (optional)' : 'Select project first' }}</option>
                        <option v-for="t in filteredTasks" :key="t.id" :value="t.id">{{ t.title }}</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Status + Date + Time + Duration + Add button -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center border-t border-gray-100">
                <!-- Status -->
                <div class="border-b sm:border-b-0 sm:border-r border-gray-100 px-1">
                    <select v-model="form.status" class="px-2 py-2.5 text-xs font-medium outline-none bg-transparent" :class="{
                        'text-emerald-600': form.status === 'done',
                        'text-[#4e1a77]': form.status === 'in_progress',
                        'text-red-600': form.status === 'blocked',
                    }">
                        <option value="done">Done</option>
                        <option value="in_progress">In Progress</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>

                <!-- Blocker note (shown only when blocked) -->
                <div v-if="form.status === 'blocked'" class="flex-1 border-b sm:border-b-0 sm:border-r border-gray-100">
                    <input v-model="form.blocker" type="text" placeholder="Describe blocker..." class="w-full px-3 py-2.5 text-sm text-red-600 placeholder-red-300 outline-none bg-red-50/50" />
                </div>

                <!-- Date -->
                <div class="flex items-center gap-1 border-b sm:border-b-0 sm:border-r border-gray-100 px-3">
                    <svg class="h-4 w-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    <input v-model="form.log_date" type="date" class="py-2.5 text-sm text-gray-700 outline-none bg-transparent w-32" />
                </div>

                <!-- Start Time -->
                <div class="flex items-center border-b sm:border-b-0 sm:border-r border-gray-100 px-2">
                    <input v-model="form.start_time" type="time" class="py-2.5 text-sm tabular-nums text-gray-700 outline-none bg-transparent w-[4.5rem]" />
                    <span class="text-gray-400 mx-0.5">-</span>
                    <input v-model="form.end_time" type="time" class="py-2.5 text-sm tabular-nums text-gray-700 outline-none bg-transparent w-[4.5rem]" />
                </div>

                <!-- Duration display -->
                <div class="flex items-center px-3 border-b sm:border-b-0 sm:border-r border-gray-100 min-w-[5rem]">
                    <span class="py-2.5 text-sm tabular-nums font-semibold" :class="calcDuration ? 'text-[#4e1a77]' : 'text-gray-300'">
                        {{ calcDuration ? calcDuration.display : '00:00' }}
                    </span>
                </div>

                <!-- ADD Button -->
                <div class="p-2">
                    <button
                        type="submit"
                        :disabled="form.processing || !calcDuration || !form.project_id"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#059669] px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#047857] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <template v-else>ADD</template>
                    </button>
                </div>
            </div>

            <!-- Errors -->
            <div v-if="Object.keys(form.errors).length" class="border-t border-red-100 bg-red-50 px-4 py-2">
                <p v-for="(err, key) in form.errors" :key="key" class="text-xs text-red-600">{{ err }}</p>
            </div>
        </form>

        <!-- ═══ Entries Grouped by Date ═══ -->
        <div v-if="groupedLogs.length" class="space-y-3">
            <div v-for="group in groupedLogs" :key="group.date" class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <!-- Date header -->
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-3">
                    <h3 class="text-sm font-bold text-gray-700">{{ formatDateHeader(group.date) }}</h3>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ group.logs.length }} {{ group.logs.length === 1 ? 'entry' : 'entries' }}</span>
                        <span class="text-sm font-bold tabular-nums text-[#4e1a77]">{{ formatMinsToHM(group.totalMins) }}</span>
                    </div>
                </div>

                <!-- Entries -->
                <div class="divide-y divide-gray-50">
                    <div
                        v-for="log in group.logs"
                        :key="log.id"
                        class="group flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-0 px-5 py-3 hover:bg-[#f8f4fa]/40 transition-colors"
                    >
                        <!-- Editing mode -->
                        <template v-if="editingId === log.id">
                            <div class="flex-1 flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                                <input v-model="editForm.note" type="text" class="flex-1 rounded border border-gray-200 px-2 py-1 text-sm outline-none focus:border-[#4e1a77]" placeholder="Note" />
                                <input v-model="editForm.start_time" type="time" class="w-24 rounded border border-gray-200 px-2 py-1 text-sm outline-none focus:border-[#4e1a77]" />
                                <span class="text-gray-400 text-xs hidden sm:inline">-</span>
                                <input v-model="editForm.end_time" type="time" class="w-24 rounded border border-gray-200 px-2 py-1 text-sm outline-none focus:border-[#4e1a77]" />
                                <select v-model="editForm.status" class="rounded border border-gray-200 px-2 py-1 text-xs outline-none">
                                    <option value="done">Done</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="blocked">Blocked</option>
                                </select>
                                <button @click="saveEdit(log.id)" class="rounded bg-[#4e1a77] px-3 py-1 text-xs font-medium text-white hover:bg-[#3d1560]">Save</button>
                                <button @click="cancelEdit" class="rounded border border-gray-200 px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                            </div>
                        </template>

                        <!-- Display mode -->
                        <template v-else>
                            <!-- Description -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ log.note || 'No description' }}</p>
                                <p v-if="log.task_title" class="text-xs text-gray-400 truncate mt-0.5">{{ log.task_title }}</p>
                            </div>

                            <!-- User (manager view) -->
                            <div v-if="isManager && log.user_name" class="sm:w-28 flex-shrink-0 sm:px-2">
                                <span class="text-xs font-medium text-gray-500 bg-gray-100 rounded-full px-2 py-0.5">{{ log.user_name }}</span>
                            </div>

                            <!-- Project -->
                            <div class="sm:w-40 flex-shrink-0 flex items-center gap-1.5 sm:px-2">
                                <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: getProjectColor(log.project_id) }"></span>
                                <span class="text-xs font-medium text-gray-600 truncate">{{ log.project_name || 'No project' }}</span>
                            </div>

                            <!-- Status badge -->
                            <div class="sm:w-24 flex-shrink-0 sm:px-2">
                                <span :class="{
                                    'bg-emerald-100 text-emerald-700': log.status === 'done',
                                    'bg-[#e8ddf0] text-[#4e1a77]': log.status === 'in_progress',
                                    'bg-red-100 text-red-700': log.status === 'blocked',
                                }" class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                                    {{ (log.status || '').replace('_', ' ') }}
                                </span>
                            </div>

                            <!-- Blocker indicator -->
                            <div v-if="log.blocker" class="sm:w-5 flex-shrink-0 sm:px-1" :title="log.blocker">
                                <svg class="h-3.5 w-3.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                </svg>
                            </div>

                            <!-- Time range -->
                            <div class="sm:w-28 flex-shrink-0 sm:px-2 text-right">
                                <span class="text-xs tabular-nums text-gray-500">{{ formatTimeRange(log) }}</span>
                            </div>

                            <!-- Duration -->
                            <div class="sm:w-16 flex-shrink-0 sm:px-2 text-right">
                                <span class="text-sm tabular-nums font-bold text-gray-800">{{ formatHoursToHM(log.hours_spent) }}</span>
                            </div>

                            <!-- Actions -->
                            <div class="sm:w-20 flex-shrink-0 flex items-center justify-end gap-1 sm:pl-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <!-- Edit -->
                                <button @click="startEdit(log)" class="rounded p-1 text-gray-400 hover:text-[#4e1a77] hover:bg-[#f8f4fa] transition-colors" title="Edit">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </button>
                                <!-- Delete -->
                                <button @click="deleteLog(log.id)" class="rounded p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                                <!-- Replay (copy as new entry) -->
                                <button @click="form.note = log.note || ''; form.project_id = log.project_id; projectSearch = getProjectName(log.project_id); form.task_id = log.task_id || ''; form.status = log.status || 'done'" class="rounded p-1 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Copy to new entry">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="rounded-xl border border-gray-200 bg-white shadow-sm px-5 py-16 text-center">
            <svg class="mx-auto mb-3 h-12 w-12 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm font-medium text-gray-400">No work logs found.</p>
            <p class="mt-1 text-xs text-gray-400">Use the entry bar above to log your first time entry.</p>
        </div>

        <!-- ═══ Pagination ═══ -->
        <div v-if="workLogs.links?.length > 3" class="mt-4 flex items-center justify-center gap-1">
            <Link
                v-for="link in workLogs.links"
                :key="link.label"
                :href="link.url || '#'"
                :class="[
                    link.active ? 'bg-[#4e1a77] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200',
                    !link.url ? 'pointer-events-none opacity-40' : '',
                ]"
                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors bg-white border border-gray-200"
                v-html="link.label"
                preserve-scroll
            />
        </div>
    </div>
</template>
