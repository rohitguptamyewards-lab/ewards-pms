<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['filter']);

const local = reactive({ ...props.filters });

watch(
    () => props.filters,
    (val) => Object.assign(local, val),
    { deep: true }
);

function apply() {
    emit('filter', { ...local });
}

function reset() {
    Object.keys(local).forEach((k) => { local[k] = ''; });
    emit('filter', { ...local });
}
</script>

<template>
    <div class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div v-for="(opts, key) in options" :key="key" class="min-w-[160px]">
            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">
                {{ key.replace(/_/g, ' ') }}
            </label>
            <select
                v-if="Array.isArray(opts)"
                v-model="local[key]"
                @change="apply"
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
                <option value="">All</option>
                <option v-for="opt in opts" :key="opt.value ?? opt" :value="opt.value ?? opt">
                    {{ opt.label ?? opt }}
                </option>
            </select>
            <input
                v-else-if="opts === 'date'"
                v-model="local[key]"
                type="date"
                @change="apply"
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
            <input
                v-else
                v-model="local[key]"
                type="text"
                :placeholder="`Search ${key}...`"
                @input="apply"
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
        </div>
        <button
            @click="reset"
            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
            Reset
        </button>
    </div>
</template>
