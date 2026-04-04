<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    members: { type: Array, default: () => [] },
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
const canCreate = computed(() => ['cto', 'ceo', 'manager'].includes(role.value));

const roleConfig = {
    cto:       { classes: 'bg-purple-100 text-purple-700', label: 'CTO' },
    ceo:       { classes: 'bg-[#e8ddf0]   text-[#4e1a77]',   label: 'CEO' },
    manager:   { classes: 'bg-[#e8ddf0] text-[#4e1a77]',   label: 'Manager' },
    mc_team:   { classes: 'bg-violet-100 text-violet-700', label: 'MC Team' },
    developer: { classes: 'bg-green-100  text-green-700',  label: 'Developer' },
    tester:    { classes: 'bg-yellow-100 text-yellow-700', label: 'Tester' },
    analyst:   { classes: 'bg-orange-100 text-orange-700', label: 'Analyst' },
    sales:     { classes: 'bg-pink-100   text-pink-700',   label: 'Sales' },
};

function getRoleConfig(role) {
    return roleConfig[role] || { classes: 'bg-gray-100 text-gray-600', label: role };
}

function avatarColor(name) {
    const colors = ['bg-[#4e1a77]', 'bg-purple-500', 'bg-green-500', 'bg-indigo-500', 'bg-pink-500', 'bg-orange-500', 'bg-teal-500'];
    const idx = (name || '').charCodeAt(0) % colors.length;
    return colors[idx];
}
</script>

<template>
    <Head title="Team Members" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Team</h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ members.length }} member{{ members.length !== 1 ? 's' : '' }}</p>
            </div>
            <Link
                v-if="canCreate"
                href="/team-members/create"
                class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Member
            </Link>
        </div>

        <!-- Card grid for team members -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="member in members"
                :key="member.id"
                :href="`/team-members/${member.id}`"
                class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-[#4e1a77]/40 hover:shadow-md transition-all"
            >
                <div class="flex items-start gap-4">
                    <!-- Avatar -->
                    <div :class="avatarColor(member.name)" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-base font-bold text-white">
                        {{ (member.name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 group-hover:text-[#4e1a77] transition-colors truncate">{{ member.name }}</p>
                            <span
                                v-if="!member.is_active"
                                class="shrink-0 rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500"
                            >Inactive</span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-400 truncate">{{ member.email }}</p>
                        <div class="mt-2">
                            <span :class="getRoleConfig(member.role).classes" class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium">
                                {{ getRoleConfig(member.role).label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stats row -->
                <div class="mt-4 grid grid-cols-3 gap-2 border-t border-gray-100 pt-4 text-center">
                    <div>
                        <p class="text-lg font-bold text-gray-800">{{ member.project_count }}</p>
                        <p class="text-xs text-gray-400">Projects</p>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-800">{{ member.task_count }}</p>
                        <p class="text-xs text-gray-400">Tasks</p>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-800">{{ member.total_hours }}h</p>
                        <p class="text-xs text-gray-400">Logged</p>
                    </div>
                </div>
            </Link>

            <div v-if="!members.length" class="col-span-full rounded-xl border border-dashed border-gray-200 py-12 text-center">
                <p class="text-sm text-gray-400">No team members found.</p>
            </div>
        </div>
    </div>
</template>
