<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
const canManage = computed(() => ['cto', 'ceo', 'manager'].includes(role.value));

const form = useForm({
    name:             '',
    email:            '',
    password:         '',
    password_confirmation: '',
    role:             '',
    department:       '',
    joining_date:     '',
    weekly_capacity:  40,
    working_hours:    8,
    timezone:         'Asia/Kolkata',
    is_active:        true,
    contractor_flag:  false,
    freelancer_flag:  false,
    git_username:     '',
});

const roles = [
    { value: 'cto',       label: 'CTO' },
    { value: 'ceo',       label: 'CEO' },
    { value: 'manager',   label: 'Manager' },
    { value: 'mc_team',   label: 'MC Team' },
    { value: 'sales',     label: 'Sales' },
    { value: 'developer', label: 'Developer' },
    { value: 'tester',    label: 'Tester' },
    { value: 'analyst',   label: 'Analyst' },
];

const departments = [
    'Engineering',
    'Product',
    'Design',
    'QA',
    'Sales',
    'Marketing',
    'Merchant Care',
    'Operations',
    'Management',
];

const timezones = [
    'Asia/Kolkata',
    'America/New_York',
    'America/Chicago',
    'America/Denver',
    'America/Los_Angeles',
    'Europe/London',
    'Europe/Berlin',
    'Asia/Dubai',
    'Asia/Singapore',
    'Asia/Tokyo',
    'Australia/Sydney',
    'UTC',
];

function submit() {
    form.post('/team-members');
}
</script>

<template>
    <Head title="Add Team Member" />

    <div class="mx-auto max-w-2xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/team-members" class="hover:text-gray-700">Team</Link>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-900">Add Member</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-4">
                <h1 class="text-lg font-bold text-gray-900">Add Team Member</h1>
                <p class="mt-0.5 text-sm text-gray-500">Create a new team member account</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 p-6">
                <!-- Name & Email -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Full name"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400 bg-red-50': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="email@example.com"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400 bg-red-50': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>
                </div>

                <!-- Password -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            placeholder="Min 8 characters"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400 bg-red-50': form.errors.password }"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            placeholder="Repeat password"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        />
                    </div>
                </div>

                <!-- Role & Department -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="role" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="role"
                            v-model="form.role"
                            required
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                            :class="{ 'border-red-400': form.errors.role }"
                        >
                            <option value="">Select role</option>
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                        <p v-if="form.errors.role" class="mt-1 text-xs text-red-600">{{ form.errors.role }}</p>
                    </div>
                    <div>
                        <label for="department" class="mb-1.5 block text-sm font-semibold text-gray-700">Department</label>
                        <select
                            id="department"
                            v-model="form.department"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        >
                            <option value="">Select department</option>
                            <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>
                </div>

                <!-- Joining Date & Timezone -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="joining_date" class="mb-1.5 block text-sm font-semibold text-gray-700">Joining Date</label>
                        <input
                            id="joining_date"
                            v-model="form.joining_date"
                            type="date"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        />
                    </div>
                    <div>
                        <label for="timezone" class="mb-1.5 block text-sm font-semibold text-gray-700">Timezone</label>
                        <select
                            id="timezone"
                            v-model="form.timezone"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        >
                            <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                        </select>
                    </div>
                </div>

                <!-- Weekly Capacity & Working Hours -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="weekly_capacity" class="mb-1.5 block text-sm font-semibold text-gray-700">Weekly Capacity (hrs)</label>
                        <input
                            id="weekly_capacity"
                            v-model.number="form.weekly_capacity"
                            type="number"
                            min="0"
                            max="168"
                            step="0.5"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        />
                    </div>
                    <div>
                        <label for="working_hours" class="mb-1.5 block text-sm font-semibold text-gray-700">Daily Working Hours</label>
                        <input
                            id="working_hours"
                            v-model.number="form.working_hours"
                            type="number"
                            min="0"
                            max="24"
                            step="0.5"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                        />
                    </div>
                </div>

                <!-- Git Username -->
                <div>
                    <label for="git_username" class="mb-1.5 block text-sm font-semibold text-gray-700">Git Username</label>
                    <input
                        id="git_username"
                        v-model="form.git_username"
                        type="text"
                        placeholder="GitHub / GitLab username"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#4e1a77] focus:bg-white focus:ring-1 focus:ring-[#4e1a77] outline-none transition"
                    />
                </div>

                <!-- Flags -->
                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#4e1a77] focus:ring-[#4e1a77]" />
                        Active
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input v-model="form.contractor_flag" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#4e1a77] focus:ring-[#4e1a77]" />
                        Contractor
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input v-model="form.freelancer_flag" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#4e1a77] focus:ring-[#4e1a77]" />
                        Freelancer
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                    <Link
                        href="/team-members"
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
                        {{ form.processing ? 'Creating...' : 'Create Member' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
