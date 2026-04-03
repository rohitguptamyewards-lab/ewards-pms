<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    documentableType: { type: String, required: true },
    documentableId: { type: Number, required: true },
    documents: { type: Array, default: () => [] },
});

const localDocs = ref([...props.documents]);
const uploading = ref(false);
const error = ref('');
const dragOver = ref(false);

const MAX_SIZE = 10 * 1024 * 1024; // 10MB

function handleDrop(e) {
    dragOver.value = false;
    const files = e.dataTransfer.files;
    if (files.length) uploadFiles(files);
}

function handleFileSelect(e) {
    const files = e.target.files;
    if (files.length) uploadFiles(files);
    e.target.value = '';
}

async function uploadFiles(files) {
    for (const file of files) {
        if (file.size > MAX_SIZE) {
            error.value = `"${file.name}" exceeds 10MB limit.`;
            return;
        }
    }
    uploading.value = true;
    error.value = '';
    try {
        for (const file of files) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('documentable_type', props.documentableType);
            formData.append('documentable_id', props.documentableId);
            const { data } = await axios.post('/api/v1/documents', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            localDocs.value.push(data.data || data);
        }
    } catch (e) {
        error.value = e.response?.data?.message || 'Upload failed.';
    } finally {
        uploading.value = false;
    }
}

async function deleteDoc(doc, index) {
    if (!confirm('Delete this document?')) return;
    try {
        await axios.delete(`/api/v1/documents/${doc.id}`);
        localDocs.value.splice(index, 1);
    } catch (e) {
        error.value = 'Failed to delete document.';
    }
}
</script>

<template>
    <div class="mt-6">
        <h3 class="text-lg font-semibold text-gray-900">Documents</h3>

        <ul v-if="localDocs.length" class="mt-3 divide-y divide-gray-200 rounded-lg border border-gray-200">
            <li
                v-for="(doc, index) in localDocs"
                :key="doc.id"
                class="flex items-center justify-between px-4 py-3"
            >
                <a
                    :href="doc.url || `/api/v1/documents/${doc.id}/download`"
                    target="_blank"
                    class="text-sm font-medium text-blue-600 hover:underline"
                >
                    {{ doc.original_name || doc.name }}
                </a>
                <button
                    @click="deleteDoc(doc, index)"
                    class="text-sm text-red-500 hover:text-red-700"
                >
                    Delete
                </button>
            </li>
        </ul>

        <div
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="handleDrop"
            :class="dragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300'"
            class="relative mt-3 flex cursor-pointer items-center justify-center rounded-lg border-2 border-dashed p-8 transition-colors"
        >
            <input
                type="file"
                multiple
                @change="handleFileSelect"
                class="absolute inset-0 cursor-pointer opacity-0"
            />
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    {{ uploading ? 'Uploading...' : 'Click or drag files here to upload' }}
                </p>
                <p class="mt-1 text-xs text-gray-400">Max 10MB per file</p>
            </div>
        </div>

        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
