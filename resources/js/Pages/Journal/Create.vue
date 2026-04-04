<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    existing: { type: Object, default: null },
    today:    { type: String, required: true },
});

const form = useForm({
    entry_date:        props.existing?.entry_date?.slice(0, 10) || props.today,
    accomplishments:   props.existing?.accomplishments || '',
    blockers:          props.existing?.blockers || '',
    plan_for_tomorrow: props.existing?.plan_for_tomorrow || '',
    reflections:       props.existing?.reflections || '',
    mood:              props.existing?.mood || '',
    tags:              props.existing?.tags ? (typeof props.existing.tags === 'string' ? JSON.parse(props.existing.tags) : props.existing.tags) : [],
    is_private:        props.existing?.is_private || false,
});

const tagInput = ref('');

function addTag() {
    const tag = tagInput.value.trim();
    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag);
    }
    tagInput.value = '';
}

function removeTag(index) {
    form.tags.splice(index, 1);
}

function submit() {
    form.post('/journal');
}

const moods = [
    { value: 'great',      emoji: '🟢', label: 'Great' },
    { value: 'good',       emoji: '🔵', label: 'Good' },
    { value: 'neutral',    emoji: '🟡', label: 'Neutral' },
    { value: 'struggling', emoji: '🟠', label: 'Struggling' },
    { value: 'blocked',    emoji: '🔴', label: 'Blocked' },
];
</script>

<template>
    <Head :title="existing ? 'Edit Journal Entry' : 'New Journal Entry'" />

    <div>
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/journal" class="hover:text-[#4e1a77]">Journal</Link>
            <span>/</span>
            <span class="text-gray-800 font-medium">{{ existing ? 'Edit' : 'New Entry' }}</span>
        </div>

        <div class="mx-auto max-w-2xl">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h1 class="text-lg font-bold text-gray-900">{{ existing ? 'Update' : 'Write' }} Journal Entry</h1>
                    <p class="mt-0.5 text-sm text-gray-500">Reflect on your day — what you achieved, what blocked you, and how you're feeling.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5 p-6">
                    <!-- Date -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Date</label>
                        <input v-model="form.entry_date" type="date"
                               class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                    </div>

                    <!-- Accomplishments -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Accomplishments <span class="text-red-500">*</span></label>
                        <textarea v-model="form.accomplishments" rows="4" required placeholder="What did you accomplish today?"
                                  :class="{ 'border-red-400': form.errors.accomplishments }"
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                        <p v-if="form.errors.accomplishments" class="mt-1 text-xs text-red-600">{{ form.errors.accomplishments }}</p>
                    </div>

                    <!-- Blockers -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Blockers</label>
                        <textarea v-model="form.blockers" rows="2" placeholder="Anything blocking your progress?"
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                    </div>

                    <!-- Plan for tomorrow -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Plan for Tomorrow</label>
                        <textarea v-model="form.plan_for_tomorrow" rows="2" placeholder="What are you planning to work on next?"
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                    </div>

                    <!-- Reflections -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Reflections</label>
                        <textarea v-model="form.reflections" rows="2" placeholder="Lessons learned, insights, or personal notes..."
                                  class="block w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                    </div>

                    <!-- Mood -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700">How are you feeling?</label>
                        <div class="flex gap-2">
                            <button v-for="m in moods" :key="m.value" type="button"
                                    @click="form.mood = form.mood === m.value ? '' : m.value"
                                    :class="form.mood === m.value ? 'ring-2 ring-[#4e1a77] bg-[#f5f0ff]' : 'bg-gray-50 hover:bg-gray-100'"
                                    class="flex flex-col items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium transition-all">
                                <span class="text-lg">{{ m.emoji }}</span>
                                <span class="text-gray-600">{{ m.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Tags</label>
                        <div class="flex items-center gap-2">
                            <input v-model="tagInput" @keydown.enter.prevent="addTag" type="text" placeholder="Add a tag..."
                                   class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-[#4e1a77]" />
                            <button type="button" @click="addTag" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200">Add</button>
                        </div>
                        <div v-if="form.tags.length" class="mt-2 flex flex-wrap gap-1.5">
                            <span v-for="(tag, i) in form.tags" :key="i"
                                  class="inline-flex items-center gap-1 rounded-full bg-[#e8ddf0] px-2.5 py-0.5 text-xs font-medium text-[#4e1a77]">
                                {{ tag }}
                                <button type="button" @click="removeTag(i)" class="ml-0.5 hover:text-red-500">&times;</button>
                            </span>
                        </div>
                    </div>

                    <!-- Private toggle -->
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_private" type="checkbox"
                               class="h-4 w-4 rounded border-gray-300 text-[#4e1a77] focus:ring-[#4e1a77]" />
                        <span class="text-sm text-gray-700">Mark as private (only visible to you)</span>
                    </label>

                    <!-- Actions -->
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <Link href="/journal" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</Link>
                        <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#3d1560] disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : (existing ? 'Update Entry' : 'Save Entry') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
