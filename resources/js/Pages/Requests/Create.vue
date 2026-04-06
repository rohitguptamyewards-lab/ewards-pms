<script setup>
/**
 * Item 74 — Mobile-first "New Request" flow for sales.
 * Redesigned as a step-by-step wizard optimized for mobile touch.
 */
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    merchants: { type: Array, default: () => [] },
});

const step             = ref(1);
const totalSteps       = 3;
const duplicates       = ref([]);
const checkingDups     = ref(false);
const merchantSearch   = ref('');

const form = useForm({
    title:       '',
    description: '',
    merchant_id: '',
    type:        '',
    urgency:     '',
});

let debounceTimer = null;
watch(() => form.title, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    duplicates.value = [];
    if (val.length >= 3) {
        debounceTimer = setTimeout(async () => {
            checkingDups.value = true;
            try {
                const { data } = await axios.get('/api/v1/requests/check-duplicates', { params: { title: val } });
                duplicates.value = data.duplicates || [];
            } catch { duplicates.value = []; }
            finally { checkingDups.value = false; }
        }, 400);
    }
});

// Step validation
const step1Valid = computed(() => form.title.trim().length >= 3);
const step2Valid = computed(() => !!form.type && !!form.urgency);
const step3Valid = computed(() => form.description.trim().length >= 10);

const canProceed = computed(() => {
    if (step.value === 1) return step1Valid.value;
    if (step.value === 2) return step2Valid.value;
    if (step.value === 3) return step3Valid.value;
    return false;
});

function nextStep() {
    if (canProceed.value && step.value < totalSteps) step.value++;
}
function prevStep() {
    if (step.value > 1) step.value--;
}

function submit() {
    form.post('/requests');
}

async function mergeTo(targetId) {
    if (!confirm('This will submit your request and merge it with the selected existing request. Continue?')) return;
    try {
        const { data: newReq } = await axios.post('/api/v1/requests', {
            title: form.title, description: form.description,
            merchant_id: form.merchant_id || null, type: form.type, urgency: form.urgency,
        });
        await axios.post(`/api/v1/requests/${newReq.id}/merge`, { target_id: targetId });
        router.visit('/requests');
    } catch (e) {
        const errors = e.response?.data?.errors;
        if (errors) alert('Please fix:\n' + Object.values(errors).flat().join('\n'));
        else alert(e.response?.data?.message || 'Failed to merge.');
    }
}

const TYPE_OPTIONS = [
    { value: 'bug',         label: 'Bug',         desc: 'Something is broken',     icon: '🐛', color: 'border-red-200 bg-red-50 text-red-800 hover:bg-red-100' },
    { value: 'new_feature', label: 'New Feature', desc: 'Brand new capability',     icon: '✨', color: 'border-purple-200 bg-purple-50 text-purple-800 hover:bg-purple-100' },
    { value: 'improvement', label: 'Improvement', desc: 'Enhance existing feature', icon: '⚡', color: 'border-blue-200 bg-blue-50 text-blue-800 hover:bg-blue-100' },
];

const URGENCY_OPTIONS = [
    { value: 'merchant_blocked', label: 'Merchant Blocked', desc: 'Merchant cannot proceed',    icon: '🚨', color: 'border-red-200 bg-red-50 text-red-800 hover:bg-red-100' },
    { value: 'merchant_unhappy', label: 'Merchant Unhappy', desc: 'Significant dissatisfaction', icon: '😕', color: 'border-orange-200 bg-orange-50 text-orange-800 hover:bg-orange-100' },
    { value: 'nice_to_have',     label: 'Nice to Have',     desc: 'Would improve experience',   icon: '💡', color: 'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100' },
];
</script>

<template>
    <Head title="New Request" />

    <div class="mx-auto max-w-lg px-1 sm:px-0">
        <!-- Header -->
        <div class="mb-5 flex items-center gap-3">
            <Link href="/requests" class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white shadow-sm hover:bg-gray-50 transition-colors">
                <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
            <div>
                <h1 class="text-lg font-bold text-gray-900">New Request</h1>
                <p class="text-xs text-gray-500">Step {{ step }} of {{ totalSteps }}</p>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="mb-6 flex gap-1.5">
            <div v-for="s in totalSteps" :key="s" class="h-1.5 flex-1 rounded-full transition-all duration-300"
                :class="s <= step ? 'bg-[#4e1a77]' : 'bg-gray-200'" />
        </div>

        <!-- ── Step 1: Title & Merchant ── -->
        <div v-if="step === 1" class="space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Step 1 — What's the request?</p>

                <!-- Title -->
                <div class="mb-4">
                    <label class="mb-2 block text-sm font-semibold text-gray-800">Request Title <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.title"
                        type="text"
                        autofocus
                        autocomplete="off"
                        placeholder="e.g. Add bulk export feature to reports"
                        class="block w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3.5 text-sm placeholder-gray-400 focus:border-[#4e1a77] focus:bg-white outline-none transition"
                        :class="{ 'border-red-400 bg-red-50': form.errors.title }"
                    />
                    <p v-if="form.errors.title" class="mt-1.5 text-xs text-red-600">{{ form.errors.title }}</p>

                    <!-- Duplicate check -->
                    <p v-if="checkingDups" class="mt-2 flex items-center gap-1.5 text-xs text-gray-400">
                        <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Checking for similar requests...
                    </p>

                    <!-- Duplicate warning -->
                    <div v-if="duplicates.length" class="mt-3 rounded-xl border-2 border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-900">Similar requests found:</p>
                        <div class="mt-3 space-y-2">
                            <div v-for="dup in duplicates" :key="dup.id" class="flex items-center justify-between gap-3 rounded-lg bg-white p-3 shadow-sm">
                                <span class="flex-1 truncate text-xs text-amber-800">{{ dup.title }}</span>
                                <button type="button" @click="mergeTo(dup.id)"
                                    class="shrink-0 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600 active:scale-95 transition">
                                    Merge →
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Merchant (optional) -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-800">Merchant <span class="text-xs font-normal text-gray-400">(optional)</span></label>
                    <input
                        v-model="merchantSearch"
                        type="text"
                        placeholder="Search merchant name..."
                        class="mb-2 block w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 text-sm placeholder-gray-400 focus:border-[#4e1a77] focus:bg-white outline-none transition"
                    />
                    <select
                        v-model="form.merchant_id"
                        class="block w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3.5 text-sm focus:border-[#4e1a77] focus:bg-white outline-none transition"
                    >
                        <option value="">No specific merchant</option>
                        <option
                            v-for="m in merchants.filter(m => !merchantSearch || m.name.toLowerCase().includes(merchantSearch.toLowerCase()))"
                            :key="m.id"
                            :value="m.id"
                        >{{ m.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Next -->
            <button
                type="button"
                @click="nextStep"
                :disabled="!step1Valid"
                class="w-full rounded-xl bg-[#4e1a77] py-4 text-sm font-bold text-white shadow-lg disabled:opacity-40 hover:bg-[#3d1560] active:scale-[0.99] transition"
            >
                Continue →
            </button>
        </div>

        <!-- ── Step 2: Type & Urgency ── -->
        <div v-if="step === 2" class="space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Step 2 — Request type & urgency</p>

                <!-- Type -->
                <div class="mb-5">
                    <label class="mb-3 block text-sm font-semibold text-gray-800">Type <span class="text-red-500">*</span></label>
                    <div class="space-y-2.5">
                        <button
                            v-for="opt in TYPE_OPTIONS"
                            :key="opt.value"
                            type="button"
                            @click="form.type = opt.value"
                            :class="['w-full rounded-xl border-2 p-4 text-left transition active:scale-[0.99]', form.type === opt.value ? opt.color + ' border-current ring-2 ring-offset-1 ring-[#4e1a77]' : opt.color]"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ opt.icon }}</span>
                                <div>
                                    <p class="text-sm font-semibold">{{ opt.label }}</p>
                                    <p class="text-xs opacity-70">{{ opt.desc }}</p>
                                </div>
                                <div class="ml-auto">
                                    <div class="h-5 w-5 rounded-full border-2" :class="form.type === opt.value ? 'border-[#4e1a77] bg-[#4e1a77]' : 'border-gray-300'">
                                        <svg v-if="form.type === opt.value" class="h-full w-full text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Urgency -->
                <div>
                    <label class="mb-3 block text-sm font-semibold text-gray-800">Urgency <span class="text-red-500">*</span></label>
                    <div class="space-y-2.5">
                        <button
                            v-for="opt in URGENCY_OPTIONS"
                            :key="opt.value"
                            type="button"
                            @click="form.urgency = opt.value"
                            :class="['w-full rounded-xl border-2 p-4 text-left transition active:scale-[0.99]', form.urgency === opt.value ? opt.color + ' border-current ring-2 ring-offset-1 ring-[#4e1a77]' : opt.color]"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ opt.icon }}</span>
                                <div>
                                    <p class="text-sm font-semibold">{{ opt.label }}</p>
                                    <p class="text-xs opacity-70">{{ opt.desc }}</p>
                                </div>
                                <div class="ml-auto">
                                    <div class="h-5 w-5 rounded-full border-2" :class="form.urgency === opt.value ? 'border-[#4e1a77] bg-[#4e1a77]' : 'border-gray-300'">
                                        <svg v-if="form.urgency === opt.value" class="h-full w-full text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="prevStep" class="flex-1 rounded-xl border-2 border-gray-200 py-4 text-sm font-semibold text-gray-600 hover:bg-gray-50 active:scale-[0.99] transition">
                    ← Back
                </button>
                <button type="button" @click="nextStep" :disabled="!step2Valid"
                    class="flex-[2] rounded-xl bg-[#4e1a77] py-4 text-sm font-bold text-white shadow-lg disabled:opacity-40 hover:bg-[#3d1560] active:scale-[0.99] transition">
                    Continue →
                </button>
            </div>
        </div>

        <!-- ── Step 3: Description & Submit ── -->
        <div v-if="step === 3" class="space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Step 3 — Details</p>

                <!-- Summary card -->
                <div class="mb-5 rounded-xl bg-[#f5f0ff] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#4e1a77]">Your request summary</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ form.title }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-gray-700 shadow-sm">
                            {{ TYPE_OPTIONS.find(t => t.value === form.type)?.icon }}
                            {{ TYPE_OPTIONS.find(t => t.value === form.type)?.label }}
                        </span>
                        <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-gray-700 shadow-sm">
                            {{ URGENCY_OPTIONS.find(u => u.value === form.urgency)?.icon }}
                            {{ URGENCY_OPTIONS.find(u => u.value === form.urgency)?.label }}
                        </span>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-800">
                        Description <span class="text-red-500">*</span>
                        <span class="ml-1 text-xs font-normal text-gray-400">(min 10 characters)</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="6"
                        placeholder="Describe the problem, expected behavior, and any relevant context...&#10;&#10;The more detail you provide, the faster the team can act on this."
                        class="block w-full resize-none rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3.5 text-sm placeholder-gray-400 focus:border-[#4e1a77] focus:bg-white outline-none transition"
                        :class="{ 'border-red-400 bg-red-50': form.errors.description }"
                    />
                    <div class="mt-1.5 flex items-center justify-between">
                        <p v-if="form.errors.description" class="text-xs text-red-600">{{ form.errors.description }}</p>
                        <p class="ml-auto text-xs" :class="form.description.length >= 10 ? 'text-green-600' : 'text-gray-400'">
                            {{ form.description.length }} chars
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="prevStep" class="flex-1 rounded-xl border-2 border-gray-200 py-4 text-sm font-semibold text-gray-600 hover:bg-gray-50 active:scale-[0.99] transition">
                    ← Back
                </button>
                <button
                    type="button"
                    @click="submit"
                    :disabled="!step3Valid || form.processing"
                    class="flex-[2] inline-flex items-center justify-center gap-2 rounded-xl bg-[#4e1a77] py-4 text-sm font-bold text-white shadow-lg disabled:opacity-40 hover:bg-[#3d1560] active:scale-[0.99] transition"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ form.processing ? 'Submitting...' : 'Submit Request ✓' }}
                </button>
            </div>
        </div>
    </div>
</template>
