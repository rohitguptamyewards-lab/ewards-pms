<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '' });

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Sign In" />

    <div class="flex min-h-screen">
        <!-- Left branding panel — eWards purple gradient -->
        <div class="hidden lg:flex lg:w-1/2 flex-col items-center justify-center bg-gradient-to-br from-[#2c0f47] via-[#3d1560] to-[#4e1a77] px-12">
            <div class="max-w-md text-center">
                <div class="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
                    <svg class="h-9 w-9 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white">eWards PMS</h1>
                <p class="mt-3 text-lg text-purple-200">Project Management System</p>
                <p class="mt-6 text-sm text-purple-200/80 leading-relaxed">
                    Streamline and empower your business operations. Track projects, manage tasks, log work hours, and keep your entire team aligned.
                </p>
                <div class="mt-10 grid grid-cols-3 gap-6 text-center">
                    <div>
                        <p class="text-2xl font-bold text-white">Projects</p>
                        <p class="mt-1 text-xs text-purple-200/70">Full lifecycle</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">Tasks</p>
                        <p class="mt-1 text-xs text-purple-200/70">With priorities</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">Reports</p>
                        <p class="mt-1 text-xs text-purple-200/70">Real-time data</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right login panel — light purple bg like eWards -->
        <div class="flex w-full items-center justify-center px-6 lg:w-1/2 bg-[#f8f4fa]">
            <div class="w-full max-w-sm">
                <div class="mb-8 lg:hidden text-center">
                    <h1 class="text-2xl font-bold text-[#4e1a77]">eWards PMS</h1>
                    <p class="mt-1 text-sm text-gray-500">Project Management System</p>
                </div>

                <h2 class="text-2xl font-bold text-gray-900">Welcome back</h2>
                <p class="mt-1 text-sm text-gray-500">Sign in to manage everything from one place.</p>

                <form @submit.prevent="submit" class="mt-8 space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email address <span class="text-[#f97316]">*</span></label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            autofocus
                            placeholder="you@example.com"
                            :class="form.errors.email ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-[#d9cce6] focus:border-[#4e1a77] focus:ring-[#4e1a77]'"
                            class="mt-1 block w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-sm focus:ring-1 outline-none transition"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-[#f97316]">*</span></label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            :class="form.errors.password ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-[#d9cce6] focus:border-[#4e1a77] focus:ring-[#4e1a77]'"
                            class="mt-1 block w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm shadow-sm focus:ring-1 outline-none transition"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-[#4e1a77] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#3d1560] focus:outline-none focus:ring-2 focus:ring-[#4e1a77] focus:ring-offset-2 disabled:opacity-60 transition-colors uppercase tracking-wider"
                    >
                        <span v-if="form.processing" class="flex items-center justify-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Signing in...
                        </span>
                        <span v-else>LOG IN</span>
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-gray-400">eWards PMS &copy; {{ new Date().getFullYear() }}</p>
            </div>
        </div>
    </div>
</template>
