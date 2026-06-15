<template>
    <Fieldset :legend="title" toggleable v-model:collapsed="collapsed">

        <!-- ========================= -->
        <!-- 📱 MOBILE VIEW (ACCORDION) -->
        <!-- ========================= -->
        <div class="block md:hidden mt-4 space-y-2">

            <div v-for="(row, index) in rows" :key="row.checklist_entry_id"
                class="border rounded-lg bg-white shadow-sm">
                <!-- Accordion Header -->
                <button class="w-full flex justify-between items-center p-3 text-left" @click="toggle(index)">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold">
                            #{{ index + 1 }} - {{ row.requirement }}
                        </span>

                        <span class="text-xs mt-1" :class="row.assessment === 'passed'
                            ? 'text-green-600'
                            : row.assessment === 'failed'
                                ? 'text-red-600'
                                : 'text-gray-500'">
                            {{ row.assessment || 'Pending' }}
                        </span>
                    </div>

                    <span class="text-gray-500">
                        {{ openIndex === index ? '▲' : '▼' }}
                    </span>
                </button>

                <!-- Accordion Content -->
                <div v-if="openIndex === index" class="p-3 border-t space-y-3">

                    <!-- FILE -->
                    <div class="text-xs">
                        <div class="font-medium mb-1">File</div>

                        <div v-if="row.attachments?.length > 0" class="flex gap-2 flex-wrap">
                            <button class="px-2 py-1 bg-yellow-500 text-white rounded text-xs"
                                @click="$emit('view-file', row.attachments[0])">
                                View
                            </button>

                            <button v-if="row.assessment === 'failed' && !isDraft && canUpload"
                                class="px-2 py-1 bg-blue-500 text-white rounded text-xs"
                                @click="triggerFileInput(row.checklist_entry_id)">
                                Upload
                            </button>
                        </div>

                        <span v-else class="text-gray-400">No file</span>

                        <input type="file" class="hidden" multiple
                            :ref="el => fileInputRefs[row.checklist_entry_id] = el"
                            @change="handleFileSelection($event, row.checklist_entry_id)" />
                    </div>

                    <!-- RESUBMISSIONS -->
                    <div class="text-xs">
                        <div class="font-medium mb-1">Resubmissions</div>

                        <ul class="text-xs text-gray-700">
                            <li v-for="(file, fIndex) in row.resubmissions" :key="fIndex" :class="[
                                'flex justify-between items-center mb-1 px-2 py-1 rounded',
                                fIndex === row.resubmissions.length - 1 ? 'bg-green-100 font-semibold' : ''
                            ]">
                                <span class="flex items-center gap-2 cursor-pointer" @click="$emit('view-file', file)">
                                    <!-- ✅ show icon only for latest file -->
                                    <CircleCheck v-if="fIndex === row.resubmissions.length - 1"
                                        class="w-4 h-4 text-green-600" />

                                    {{ file.file_name }}
                                    <small class="text-gray-500">
                                        ({{ formatDate(file.created_at) }})
                                    </small>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <!-- COMMENTS -->
                    <div>
                        <div class="text-xs font-medium mb-1">Comments</div>

                        <Textarea v-if="roleId !== 1" :modelValue="row.remarks" rows="2" class="text-xs w-full"
                            @update:modelValue="$emit('update-remarks', row.checklist_entry_id, $event)" />

                        <div v-else class="text-xs text-gray-600">
                            {{ row.remarks || '—' }}
                        </div>
                    </div>

                    <!-- ACTIONS -->
                    <div class="flex gap-2 pt-2">
                        <button class="px-2 py-1 text-xs rounded" :class="row.assessment === 'passed'
                            ? 'bg-green-900 text-white'
                            : 'bg-gray-200'" @click="$emit('update-assessment', row.checklist_entry_id, 'passed')"
                            :disabled="isDraft || roleId === 1">
                            Compliant
                        </button>

                        <button class="px-2 py-1 text-xs rounded" :class="row.assessment === 'failed'
                            ? 'bg-red-900 text-white'
                            : 'bg-gray-200'" @click="$emit('update-assessment', row.checklist_entry_id, 'failed')"
                            :disabled="isDraft || roleId === 1">
                            Non-compliant
                        </button>
                    </div>

                </div>
            </div>

        </div>

        <!-- ========================= -->
        <!-- 💻 DESKTOP VIEW (TABLE) -->
        <!-- ========================= -->
        <div class="hidden md:block overflow-x-auto mt-4">

            <table class="min-w-full border border-gray-300 rounded-lg bg-white">
                <thead class="bg-blue-900 text-white">
                    <tr>
                        <th class="px-3 py-2 border">#</th>
                        <th class="px-3 py-2 border">File Uploaded</th>
                        <th class="px-3 py-2 border">Resubmitted File</th>
                        <th class="px-3 py-2 border">Comments</th>
                        <th class="px-3 py-2 border">Assessment</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(row, index) in rows" :key="row.checklist_entry_id">

                        <td class="px-3 py-2 border">{{ index + 1 }}</td>

                        <td class="px-3 py-2 border">
                            <div v-if="row.attachments?.length" class="flex items-center gap-2">
                                {{ row.requirement }}

                                <button class="px-3 py-1 bg-yellow-500 text-white text-xs rounded"
                                    @click="$emit('view-file', row.attachments[0])">
                                    View
                                </button>

                                <button v-if="row.assessment === 'failed' && !isDraft && canUpload"
                                    class="px-3 py-1 bg-blue-500 text-white text-xs rounded"
                                    @click="triggerFileInput(row.checklist_entry_id)">
                                    Upload
                                </button>
                            </div>

                            <span v-else class="text-xs text-gray-400">No file</span>
                        </td>

                        <td class="px-3 py-2 border text-xs">
                            <ul class="text-xs text-gray-700">
                                <li v-for="(file, fIndex) in row.resubmissions" :key="fIndex" :class="[
                                    'flex justify-between items-center mb-1 px-2 py-1 rounded',
                                    fIndex === row.resubmissions.length - 1 ? 'bg-green-100 font-semibold' : ''
                                ]">
                                    <span class="flex items-center gap-2 cursor-pointer"
                                        @click="$emit('view-file', file)">
                                        <!-- ✅ show icon only for latest file -->
                                        <CircleCheck v-if="fIndex === row.resubmissions.length - 1"
                                            class="w-4 h-4 text-green-600" />

                                        {{ file.file_name }}
                                        <small class="text-gray-500">
                                            ({{ formatDate(file.created_at) }})
                                        </small>
                                    </span>
                                </li>
                            </ul>
                        </td>

                        <td class="px-3 py-2 border">
                            <Textarea v-if="roleId !== 1" :modelValue="row.remarks" rows="3" class="text-xs w-full"
                                @update:modelValue="$emit('update-remarks', row.checklist_entry_id, $event)" />
                            <span v-else class="text-xs">{{ row.remarks || '—' }}</span>
                        </td>

                        <td class="px-3 py-2 border">
                            <div class="flex gap-2">
                                <button class="px-2 py-1 text-xs rounded" :class="row.assessment === 'passed'
                                    ? 'bg-green-900 text-white'
                                    : 'bg-gray-300'"
                                    @click="$emit('update-assessment', row.checklist_entry_id, 'passed')">
                                    Compliant
                                </button>

                                <button class="px-2 py-1 text-xs rounded" :class="row.assessment === 'failed'
                                    ? 'bg-red-900 text-white'
                                    : 'bg-gray-300'"
                                    @click="$emit('update-assessment', row.checklist_entry_id, 'failed')">
                                    Non-compliant
                                </button>
                            </div>
                        </td>

                    </tr>
                </tbody>
            </table>

        </div>

        <!-- ========================= -->
        <!-- OVERALL COMMENTS -->
        <!-- ========================= -->
        <div class="mt-6">
            <div class="flex flex-col gap-2">

                <span class="font-semibold text-sm text-gray-800">
                    Other Comments
                </span>

                <Textarea :modelValue="overallRemarks" rows="6" class="text-xs w-full"
                    @update:modelValue="$emit('update-overall-remarks', $event)" />
            </div>
        </div>

    </Fieldset>
</template>

<script setup lang="ts">
import Fieldset from 'primevue/fieldset';
import Textarea from 'primevue/textarea';
import { reactive, ref, computed } from 'vue';
import { Upload, View, CircleCheck } from 'lucide-vue-next';

const formatDate = (dateString: string) => {
    const d = new Date(dateString);
    return d.toLocaleString([], { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
// Props
const props = defineProps({
    title: String,
    roleId: Number,
    application_status: String,
    collapsed: { type: Boolean, default: true },
    rows: Array,
    overallRemarks: String
});

const collapsed = ref(props.collapsed);

// Accordion state (mobile only)
const openIndex = ref<number | null>(null);

const toggle = (index: number) => {
    openIndex.value = openIndex.value === index ? null : index;
};

// File inputs
const fileInputRefs = reactive<{ [key: number]: HTMLInputElement | null }>({});

// Computed
const isDraft = computed(() => props.application_status === 'Draft');

const canUpload = computed(() => {
    return props.roleId === 1 || props.roleId === 4;
});

// Emits
const emit = defineEmits([
    'view-file',
    'update-remarks',
    'update-assessment',
    'upload-resubmission',
    'update-overall-remarks'
]);

const triggerFileInput = (id: number) => {
    fileInputRefs[id]?.click();
};

const handleFileSelection = (event: Event, id: number) => {
    const target = event.target as HTMLInputElement;
    if (!target.files?.length) return;

    const files = Array.from(target.files);
    emit('upload-resubmission', id, files);

    target.value = '';
};
</script>