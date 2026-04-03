<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    comments: { type: Array, default: () => [] },
    commentableType: { type: String, required: true },
    commentableId: { type: Number, required: true },
});

const localComments = ref([...props.comments]);
const newComment = ref('');
const submitting = ref(false);
const error = ref('');

async function submitComment() {
    if (!newComment.value.trim()) return;
    submitting.value = true;
    error.value = '';
    try {
        const { data } = await axios.post('/api/v1/comments', {
            commentable_type: props.commentableType,
            commentable_id: props.commentableId,
            body: newComment.value,
        });
        localComments.value.push(data.data || data);
        newComment.value = '';
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to add comment.';
    } finally {
        submitting.value = false;
    }
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}
</script>

<template>
    <div class="mt-6">
        <h3 class="text-lg font-semibold text-gray-900">Comments</h3>

        <div v-if="localComments.length === 0" class="mt-3 text-sm text-gray-500">
            No comments yet.
        </div>

        <div class="mt-3 space-y-4">
            <div
                v-for="comment in localComments"
                :key="comment.id"
                class="rounded-lg border border-gray-200 bg-gray-50 p-4"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900">
                        {{ comment.user?.name || 'Unknown' }}
                    </span>
                    <span class="text-xs text-gray-500">{{ formatDate(comment.created_at) }}</span>
                </div>
                <p class="mt-2 text-sm text-gray-700 whitespace-pre-wrap">{{ comment.body }}</p>
            </div>
        </div>

        <div class="mt-4">
            <textarea
                v-model="newComment"
                rows="3"
                placeholder="Write a comment..."
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
            <button
                @click="submitComment"
                :disabled="submitting || !newComment.trim()"
                class="mt-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
            >
                {{ submitting ? 'Posting...' : 'Add Comment' }}
            </button>
        </div>
    </div>
</template>
