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

// Link form state
const showLinkForm = ref(false);
const linkUrl = ref('');
const linkName = ref('');
const linkDescription = ref('');
const savingLink = ref(false);

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
            formData.append('type', 'file');
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

async function saveLink() {
    if (!linkUrl.value.trim() || !linkName.value.trim()) {
        error.value = 'Link URL and name are required.';
        return;
    }
    savingLink.value = true;
    error.value = '';
    try {
        const { data } = await axios.post('/api/v1/documents', {
            type: 'link',
            documentable_type: props.documentableType,
            documentable_id: props.documentableId,
            link_url: linkUrl.value,
            file_name: linkName.value,
            description: linkDescription.value,
        });
        localDocs.value.push(data.data || data);
        linkUrl.value = '';
        linkName.value = '';
        linkDescription.value = '';
        showLinkForm.value = false;
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to save link.';
    } finally {
        savingLink.value = false;
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

function docHref(doc) {
    if (doc.type === 'link') return doc.link_url;
    return `/api/v1/documents/${doc.id}`;
}
</script>

<template>
    <div class="mt-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-gray-900">Documents & Links</h3>
            <button
                @click="showLinkForm = !showLinkForm"
                class="text-sm text-[#4e1a77] hover:text-[#361963] font-medium"
            >
                {{ showLinkForm ? 'Cancel' : '+ Add Link' }}
            </button>
        </div>

        <!-- Link form -->
        <div v-if="showLinkForm" class="mb-4 rounded-lg border border-[#ddd0f7] bg-[#f5f0ff] p-4 space-y-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Link Name</label>
                <input
                    v-model="linkName"
                    type="text"
                    placeholder="e.g. Design Spec, Figma File..."
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77]"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">URL</label>
                <input
                    v-model="linkUrl"
                    type="url"
                    placeholder="https://..."
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77]"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Description <span class="text-gray-400">(optional)</span></label>
                <input
                    v-model="linkDescription"
                    type="text"
                    placeholder="Brief description..."
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77]"
                />
            </div>
            <button
                @click="saveLink"
                :disabled="savingLink"
                class="rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white hover:bg-[#3d1560] disabled:opacity-50"
            >
                {{ savingLink ? 'Saving...' : 'Save Link' }}
            </button>
        </div>

        <!-- Document / link list -->
        <ul v-if="localDocs.length" class="mt-3 divide-y divide-gray-200 rounded-lg border border-gray-200">
            <li
                v-for="(doc, index) in localDocs"
                :key="doc.id"
                class="flex items-center justify-between px-4 py-3"
            >
                <div class="flex items-center gap-2 min-w-0">
                    <span class="flex-shrink-0 text-xs font-medium px-1.5 py-0.5 rounded"
                        :class="doc.type === 'link' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600'">
                        {{ doc.type === 'link' ? 'Link' : 'File' }}
                    </span>
                    <a
                        :href="docHref(doc)"
                        target="_blank"
                        class="text-sm font-medium text-[#4e1a77] hover:underline truncate"
                    >
                        {{ doc.file_name || doc.original_name || doc.name }}
                    </a>
                    <span v-if="doc.description" class="text-xs text-gray-400 truncate">— {{ doc.description }}</span>
                </div>
                <button
                    @click="deleteDoc(doc, index)"
                    class="ml-3 flex-shrink-0 text-sm text-red-500 hover:text-red-700"
                >
                    Delete
                </button>
            </li>
        </ul>
        <p v-else class="mt-3 text-sm text-gray-400">No documents or links yet.</p>

        <!-- File drag & drop -->
        <div
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="handleDrop"
            :class="dragOver ? 'border-[#4e1a77] bg-[#f5f0ff]' : 'border-gray-300'"
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
