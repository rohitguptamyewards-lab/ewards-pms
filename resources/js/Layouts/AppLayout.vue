<script setup>
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page  = usePage();
const user  = computed(() => page.props.auth?.user);
const role  = computed(() => user.value?.role || '');
const url   = computed(() => page.url);

function isActive(href) {
    if (!href) return false;
    if (href === '/') return url.value === '/';
    return url.value.startsWith(href);
}

const MANAGER  = ['cto', 'ceo', 'manager'];
const MC_UP    = [...MANAGER, 'mc_team'];
const REPORTS  = MC_UP;
const ALL      = [...MC_UP, 'developer', 'tester', 'analyst', 'sales'];
const DEV_TEST = [...MANAGER, 'developer', 'tester'];
const CTO_ONLY = ['cto'];
/* MC & Sales can only see Requests + their linked project status */
const MC_SALES = ['mc_team', 'sales'];
const NOT_MC_SALES = ['cto', 'ceo', 'manager', 'developer', 'tester', 'analyst'];

const navItems = computed(() => {
    const chevIcon = `<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />`;

    /* If MC or Sales, show a very limited sidebar */
    if (MC_SALES.includes(role.value)) {
        return [
            {
                label: 'Dashboard', href: '/', roles: null,
                icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />`,
            },
            {
                label: 'Requests', href: '/requests', roles: null,
                icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />`,
            },
            {
                label: 'My Projects', href: '/projects', roles: null,
                icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />`,
            },
            { label: 'PERSONAL', href: null, roles: null, header: true },
            {
                label: 'My Dashboard', href: '/personal/history', roles: null, indent: true,
                icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />`,
            },
            {
                label: 'Team', href: '/team-members', roles: null,
                icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />`,
            },
        ];
    }

    const items = [
        {
            label: 'Dashboard', href: '/', roles: null,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />`,
        },
        {
            label: 'Work Logs', href: '/work-logs', roles: NOT_MC_SALES,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />`,
        },
        {
            label: 'Journal', href: '/journal', roles: NOT_MC_SALES,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />`,
        },
        {
            label: 'Activity Logs', href: '/activity-logs', roles: NOT_MC_SALES,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />`,
        },
        {
            label: 'Projects', href: '/projects', roles: ALL,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />`,
        },
        {
            label: 'Tasks', href: '/tasks', roles: [...MC_UP, 'developer', 'tester', 'analyst'],
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />`,
        },
        {
            label: 'Requests', href: '/requests', roles: [...MC_UP, 'sales'],
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />`,
        },
        {
            label: 'Sprints', href: '/sprints', roles: DEV_TEST,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />`,
        },

        { label: 'PRODUCT', href: null, roles: MC_UP, header: true },
        {
            label: 'Features', href: '/features', roles: MC_UP, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />`,
        },
        {
            label: 'Board', href: '/features/kanban', roles: MC_UP, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z" />`,
        },
        {
            label: 'Initiatives', href: '/initiatives', roles: MC_UP, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />`,
        },
        {
            label: 'Ideas', href: '/ideas', roles: NOT_MC_SALES, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />`,
        },
        {
            label: 'Decisions', href: '/decisions', roles: MC_UP, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z" />`,
        },
        {
            label: 'Releases', href: '/releases', roles: MC_UP, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />`,
        },
        {
            label: 'Changelogs', href: '/changelogs', roles: MC_UP, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />`,
        },
        {
            label: 'Deadlines', href: '/deadlines', roles: MC_UP, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />`,
        },
        {
            label: 'Bug SLA', href: '/bug-sla', roles: DEV_TEST, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0112 12.75zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 01-1.152 6.06M12 12.75c-2.883 0-5.647.508-8.208 1.44a23.916 23.916 0 001.152 6.06M12 12.75a2.25 2.25 0 002.248-2.354M12 12.75a2.25 2.25 0 01-2.248-2.354M12 8.25c.995 0 1.971-.08 2.922-.236.403-.066.74-.358.795-.762a3.778 3.778 0 00-.399-2.25M12 8.25c-.995 0-1.97-.08-2.922-.236-.402-.066-.74-.358-.795-.762a3.734 3.734 0 01.4-2.253M12 8.25a2.25 2.25 0 00-2.248 2.146M12 8.25a2.25 2.25 0 012.248 2.146M8.683 5a6.032 6.032 0 01-1.155-1.002c.07-.63.27-1.222.574-1.747m.581 2.749A3.75 3.75 0 0115.318 5m0 0c.427-.283.815-.62 1.155-.999a4.471 4.471 0 00-.575-1.752M4.921 6a24.048 24.048 0 00-.392 3.314c1.668.546 3.416.914 5.223 1.082M19.08 6c.205 1.08.337 2.187.392 3.314a23.882 23.882 0 01-5.223 1.082" />`,
        },
        {
            label: 'Merchant Comms', href: '/merchant-communications', roles: MC_UP, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />`,
        },

        { label: 'PERSONAL', href: null, roles: null, header: true },
        {
            label: 'My Dashboard', href: '/personal/history', roles: NOT_MC_SALES, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />`,
        },
        {
            label: 'Review Prep', href: '/personal/review-prep', roles: NOT_MC_SALES, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />`,
        },

        {
            label: 'Team', href: '/team-members', roles: ALL,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />`,
        },

        { label: 'MANAGEMENT', href: null, roles: MANAGER, header: true },
        {
            label: 'Team Journal', href: '/journal/team', roles: MANAGER, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />`,
        },
        {
            label: 'Onboarding', href: '/onboarding', roles: MANAGER, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />`,
        },
        {
            label: 'Offboarding', href: '/offboarding', roles: MANAGER, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />`,
        },
        {
            label: 'Bus Factor', href: '/bus-factor', roles: MANAGER, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />`,
        },
        {
            label: 'Capacity', href: '/capacity', roles: MANAGER, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />`,
        },
        {
            label: 'Leave Entries', href: '/leave-entries', roles: MANAGER, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />`,
        },

        { label: 'BUSINESS', href: null, roles: ['cto', 'ceo'], header: true },
        {
            label: 'CEO Dashboard', href: '/dashboard/ceo', roles: ['cto', 'ceo'], indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />`,
        },

        { label: 'INTELLIGENCE', href: null, roles: CTO_ONLY, header: true },
        {
            label: 'Cost Dashboard', href: '/dashboard/cost', roles: CTO_ONLY, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />`,
        },
        {
            label: 'Cost vs Impact', href: '/cost-vs-impact', roles: CTO_ONLY, indent: true,
            icon: chevIcon,
        },
        {
            label: 'Cost Rates', href: '/cost-rates', roles: CTO_ONLY, indent: true,
            icon: chevIcon,
        },
        {
            label: 'AI Dashboard', href: '/dashboard/ai', roles: CTO_ONLY, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />`,
        },
        {
            label: 'AI Tools', href: '/ai-tools', roles: CTO_ONLY, indent: true,
            icon: chevIcon,
        },
        {
            label: 'AI Templates', href: '/prompt-templates', roles: CTO_ONLY, indent: true,
            icon: chevIcon,
        },
        {
            label: 'AI Knowledge Base', href: '/ai-knowledge-base', roles: CTO_ONLY, indent: true,
            icon: chevIcon,
        },

        { label: 'GIT', href: null, roles: MANAGER, header: true },
        {
            label: 'Git Providers', href: '/git-providers', roles: MANAGER, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75L16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />`,
        },
        {
            label: 'Repositories', href: '/git-repositories', roles: MANAGER, indent: true,
            icon: chevIcon,
        },

        { label: 'REPORTS', href: null, roles: REPORTS, header: true },
        {
            label: 'Work Log Report', href: '/reports/work-logs', roles: REPORTS, indent: true,
            icon: chevIcon,
        },
        {
            label: 'Project Report', href: '/reports/projects', roles: REPORTS, indent: true,
            icon: chevIcon,
        },
        {
            label: 'Individual Report', href: '/reports/individual', roles: REPORTS, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />`,
        },
    ];
    return items.filter(item => item.roles === null || item.roles.includes(role.value));
});

const avatarInitial = computed(() => (user.value?.name || '?').charAt(0).toUpperCase());
const roleLabel = computed(() => (role.value || '').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()));

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-[#f8f4fa]">
        <!-- Sidebar -->
        <aside class="flex w-64 flex-shrink-0 flex-col border-r border-gray-200 bg-white">
            <!-- Logo -->
            <div class="flex h-16 items-center gap-2 px-5">
                <!-- eWards logo: E in dark, W in orange, ARDS in dark -->
                <svg class="h-7" viewBox="0 0 140 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <text x="0" y="24" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="700" letter-spacing="-0.5">
                        <tspan fill="#2c0f47">E</tspan><tspan fill="#f97316">W</tspan><tspan fill="#2c0f47">ARDS</tspan>
                    </text>
                </svg>
            </div>

            <!-- Nav -->
            <nav class="mt-1 flex-1 overflow-y-auto px-2 pb-3">
                <template v-for="item in navItems" :key="item.href || item.label">
                    <!-- Section header -->
                    <p v-if="item.header" class="mb-1 mt-5 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                        {{ item.label }}
                    </p>
                    <!-- Nav link -->
                    <Link
                        v-else
                        :href="item.href"
                        :class="[
                            isActive(item.href)
                                ? 'text-[#4e1a77] font-semibold border-l-[3px] border-[#4e1a77] bg-[#f8f4fa]'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-[#4e1a77] border-l-[3px] border-transparent',
                            item.indent ? 'pl-8 text-xs py-2' : 'text-sm py-2.5',
                        ]"
                        class="flex items-center gap-3 px-4 transition-all duration-150"
                    >
                        <svg
                            v-if="item.icon"
                            :class="[
                                isActive(item.href) ? 'text-[#4e1a77]' : 'text-gray-400',
                                item.indent ? 'h-3.5 w-3.5' : 'h-[18px] w-[18px]',
                            ]"
                            class="flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                            v-html="item.icon"
                        />
                        {{ item.label }}
                    </Link>
                </template>
            </nav>

            <!-- User footer -->
            <div class="border-t border-gray-200 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#e8ddf0] text-sm font-bold text-[#4e1a77]">
                        {{ avatarInitial }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800">{{ user?.name }}</p>
                        <p class="truncate text-xs text-gray-400">{{ roleLabel }}</p>
                    </div>
                    <button @click="logout" class="shrink-0 rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-[#4e1a77] transition-colors" title="Sign out">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Topbar -->
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6">
                <div class="flex items-center gap-3">
                    <!-- Hamburger icon -->
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-medium text-gray-800">{{ user?.name }}</p>
                        <p class="text-xs text-gray-400">{{ user?.email }}</p>
                    </div>
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
