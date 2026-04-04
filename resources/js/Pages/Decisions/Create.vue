<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    teamMembers: { type: Array, default: () => [] },
    features:    { type: Array, default: () => [] },
    initiatives: { type: Array, default: () => [] },
    modules:     { type: Array, default: () => [] },
});

const form = useForm({
    title: '',
    context: '',
    options_considered: '',
    chosen_option: '',
    rationale: '',
    decision_maker_id: '',
    decision_date: new Date().toISOString().split('T')[0],
    linked_to_type: '',
    linked_to_id: '',
    status: 'proposed',
});

const linkedOptions = ref([]);

function onLinkedTypeChange() {
    form.linked_to_id = '';
    if (form.linked_to_type === 'feature') linkedOptions.value = props.features;
    else if (form.linked_to_type === 'initiative') linkedOptions.value = props.initiatives;
    else if (form.linked_to_type === 'module') linkedOptions.value = props.modules;
    else linkedOptions.value = [];
}

function submit() {
    const data = { ...form.data() };
    if (!data.linked_to_type) {
        data.linked_to_type = null;
        data.linked_to_id = null;
    }
    form.transform(() => data).post('/decisions');
}
</script>

<template>
    <Head title="Record Decision" />

    <div>
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/decisions" class="hover:text-[#4e1a77]">Decision Log</Link>
            <span>/</span>
            <span class="text-gray-800 font-medium">New</span>
        </div>

        <div class="mx-auto max-w-3xl">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h1 class="text-lg font-bold text-gray-900">Record Decision</h1>
                    <p class="mt-0.5 text-sm text-gray-500">Decisions are append-only. They cannot be deleted, only superseded.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5 p-6">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Title <span class="text-red-500">*</span></label>
                        <input v-model="form.title" type="text" required
                               :class="{ 'border-red-400': form.errors.title }"
                               class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Context <span class="text-red-500">*</span></label>
                        <textarea v-model="form.context" rows="3" required placeholder="The problem being solved"
                                  :class="{ 'border-red-400': form.errors.context }"
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                        <p v-if="form.errors.context" class="mt-1 text-xs text-red-600">{{ form.errors.context }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Options Considered <span class="text-red-500">*</span></label>
                        <textarea v-model="form.options_considered" rows="3" required placeholder="All alternatives evaluated"
                                  :class="{ 'border-red-400': form.errors.options_considered }"
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Chosen Option <span class="text-red-500">*</span></label>
                        <textarea v-model="form.chosen_option" rows="2" required
                                  :class="{ 'border-red-400': form.errors.chosen_option }"
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Rationale <span class="text-red-500">*</span></label>
                        <textarea v-model="form.rationale" rows="3" required placeholder="Why this option was chosen"
                                  :class="{ 'border-red-400': form.errors.rationale }"
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Decision Maker <span class="text-red-500">*</span></label>
                            <select v-model="form.decision_maker_id" required
                                    class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]">
                                <option value="" disabled>Select person</option>
                                <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Decision Date <span class="text-red-500">*</span></label>
                            <input v-model="form.decision_date" type="date" required
                                   class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Link To</label>
                            <select v-model="form.linked_to_type" @change="onLinkedTypeChange"
                                    class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]">
                                <option value="">None</option>
                                <option value="feature">Feature</option>
                                <option value="initiative">Initiative</option>
                                <option value="module">Module</option>
                            </select>
                        </div>
                        <div v-if="form.linked_to_type">
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Select {{ form.linked_to_type }}</label>
                            <select v-model="form.linked_to_id"
                                    class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]">
                                <option value="">Select...</option>
                                <option v-for="o in linkedOptions" :key="o.id" :value="o.id">{{ o.title || o.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <Link href="/decisions" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</Link>
                        <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#3d1560] disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : 'Record Decision' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
