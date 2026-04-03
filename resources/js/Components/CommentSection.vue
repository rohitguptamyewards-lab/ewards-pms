<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    comments:       { type: Array,  default: () => [] },
    commentableType:{ type: String, required: true },
    commentableId:  { type: Number, required: true },
});

const localComments = ref([...props.comments]);
const newComment    = ref('');
const submitting    = ref(false);
const error         = ref('');

async function submitComment() {
    if (!newComment.value.trim()) return;
    submitting.value = true;
    error.value = '';
    try {
        const { data } = await axios.post('/api/v1/comments', {
            commentable_type: props.commentableType,
            commentable_id:   props.commentableId,
            body:             newComment.value,
        });
        localComments.value.unshift(data.data || data);
        newComment.value = '';
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to add comment.';
    } finally {
        submitting.value = false;
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

function renderBody(text) {
    if (!text) return '';
    const escaped = text
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    return escaped.replace(
        /@([\w.]+)/g,
        '<span class="inline-flex items-center rounded bg-[#ece1ff] px-1 py-0.5 text-xs font-semibold text-[#5e16bd]">@$1</span>'
    );
}

// Get initials from name
function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
}
</script>

<template>
    <div>
        <h2 class="mb-4 text-base font-semibold text-gray-900">
            Comments
            <span v-if="localComments.length" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                {{ localComments.length }}
            </span>
        </h2>

        <!-- New comment form -->
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <textarea
                v-model="newComment"
                rows="3"
                placeholder="Write a comment... Use @name to mention someone"
                class="block w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none transition"
            />
            <div class="mt-3 flex items-center justify-between">
                <p class="text-xs text-gray-400">Tip: Use <span class="font-medium text-gray-500">@name</span> to mention a team member</p>
                <div class="flex items-center gap-2">
                    <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
                    <button
                        @click="submitComment"
                        :disabled="submitting || !newComment.trim()"
                        class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-medium text-white hover:bg-[#4c12a1] disabled:opacity-50 transition-colors"
                    >
                        {{ submitting ? 'Posting...' : 'Post Comment' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Comment list -->
        <div v-if="localComments.length" class="space-y-3">
            <div
                v-for="comment in localComments"
                :key="comment.id"
                class="flex gap-3"
            >
                <!-- Avatar -->
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#ece1ff] text-xs font-bold text-[#5e16bd]">
                    {{ initials(comment.user_name || comment.user?.name) }}
                </div>
                <!-- Bubble -->
                <div class="flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-gray-900">
                            {{ comment.user_name || comment.user?.name || 'Unknown' }}
                        </span>
                        <span class="whitespace-nowrap text-xs text-gray-400">{{ formatDate(comment.created_at) }}</span>
                    </div>
                    <p class="mt-1.5 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed"
                       v-html="renderBody(comment.body)" />

                    <!-- Replies -->
                    <div v-if="comment.replies?.length" class="mt-3 space-y-3 border-t border-gray-100 pt-3">
                        <div v-for="reply in comment.replies" :key="reply.id" class="flex gap-2">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-500">
                                {{ initials(reply.user_name || reply.user?.name) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-gray-800">{{ reply.user_name || reply.user?.name || 'Unknown' }}</span>
                                    <span class="text-xs text-gray-400">{{ formatDate(reply.created_at) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-600 whitespace-pre-wrap" v-html="renderBody(reply.body)" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="rounded-xl border border-dashed border-gray-200 py-10 text-center">
            <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
            </svg>
            <p class="mt-2 text-sm text-gray-400">No comments yet. Be the first to comment.</p>
        </div>
    </div>
</template>
