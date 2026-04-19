<template>
    <Fieldset :legend="title" toggleable v-model:collapsed="collapsed">
        <div class="overflow-x-auto mt-4">
            <!-- Assessment Table -->
            <table class="min-w-full border border-gray-300 rounded-lg bg-white">
                <thead class="bg-blue-900 text-white">
                    <tr>
                        <th class="px-3 py-2 border">#</th>
                        <th class="px-3 py-2 border">File Uploaded</th>
                        <th class="px-3 py-2 border">Resubmitted File</th>
                        <!-- <th class="px-3 py-2 border">File Name</th> -->
                        <th class="px-3 py-2 border">Comments</th>
                        <th class="px-3 py-2 border">Assessment</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in rows" :key="row.checklist_entry_id" class="hover:bg-gray-50">
                        <!-- Row number -->
                        <td class="px-3 py-2 border">{{ index + 1 }} </td>

                        <!-- Original MOV -->
                        <td class="px-3 py-2 border">
                            <template v-if="row.attachments?.length > 0">
                                <div class="flex items-center gap-2">
                                    <!-- VIEW BUTTON -->
                                    <button
                                        class="inline-flex items-center gap-1 px-3 py-1 h-7 rounded bg-yellow-500 text-white text-xs leading-none hover:bg-yellow-600 transition"
                                        @click="$emit('view-file', row.attachments[0])">
                                        <View class="w-3.5 h-3.5" />
                                        <span>View</span>
                                    </button>

                                    <!-- UPLOAD BUTTON -->
                                    <button v-if="row.assessment === 'failed' && !isDraft"
                                        class="inline-flex items-center gap-1 px-3 py-1 h-7 rounded bg-blue-500 text-white text-xs leading-none hover:bg-blue-600 transition"
                                        @click="triggerFileInput(row.checklist_entry_id)">
                                        <Upload class="w-3.5 h-3.5" />
                                        <span>Upload</span>
                                    </button>
                                </div>

                                <!-- Hidden file input -->
                                <input type="file" class="hidden" multiple
                                    :ref="el => fileInputRefs[row.checklist_entry_id] = el"
                                    @change="handleFileSelection($event, row.checklist_entry_id)" />
                            </template>
                            <span v-else class="text-gray-400 text-xs">No file</span>
                        </td>

                        <!-- Resubmissions Cell -->
                        <td class="px-3 py-2 border text-center">
                            <!-- List existing resubmissions -->
                            <div class="mb-2">
                                <!-- <div v-if="row.resubmissions?.length" class="mb-2"> -->
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
                            </div>
                        </td>

                        <!-- Original file name -->
                        <!-- <td class="px-3 py-2 border">
              {{ row.attachments?.length > 0 ? row.attachments[0].file_name : '—' }}
            </td> -->

                        <!-- Remarks -->
                        <td class="px-2 py-2 border whitespace-pre-line" v-if="roleId === 1">
                            {{ row.remarks || '—' }}
                        </td>
                        <td class="px-2 py-2 border" v-else>
                            <Textarea :modelValue="row.remarks" rows="4" class="text-xs w-full"
                                @update:modelValue="$emit('update-remarks', row.checklist_entry_id, $event)" />
                        </td>

                        <!-- Assessment buttons -->
                        <td class="px-3 py-2 border">
                            <div class="flex gap-2">
                                <button @click="$emit('update-assessment', row.checklist_entry_id, 'passed')"
                                    :disabled="isDraft || roleId === 1 || roleId == 4" :class="[
                                        'flex items-center px-3 py-1 text-xs rounded',
                                        row.assessment === 'passed' ? 'bg-green-900 text-white' : 'bg-gray-300 text-gray-700'
                                    ]">
                                    <i class="fa fa-check mr-1"></i> Compliant
                                </button>
                                <button @click="$emit('update-assessment', row.checklist_entry_id, 'failed')"
                                    :disabled="isDraft || roleId === 1 || roleId == 4" :class="[
                                        'flex items-center px-3 py-1 text-xs rounded',
                                        row.assessment === 'failed' ? 'bg-red-900 text-white' : 'bg-gray-300 text-gray-700'
                                    ]">
                                    <i class="fa fa-times mr-1"></i> Non-compliant
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- No requirements message -->
                    <tr v-if="rows.length === 0">
                        <td colspan="7" class="text-center py-4 text-gray-500">No requirements found.</td>
                    </tr>
                </tbody>
            </table>

            <!-- Onsite Validation -->
            <!-- <table class="mt-4 min-w-full border border-gray-300 rounded-lg bg-white" v-if="!isDraft">
                <thead class="bg-blue-900 text-white">
                    <tr>
                        <th class="px-3 py-2 border" colspan="2">FOR ONSITE VALIDATION / INSPECTION</th>
                    </tr>
                </thead>
                <tbody v-if="company_form.findings !== null && company_form.recommendations !== null">
                    <tr>
                        <td class="px-3 py-2 border font-medium">Findings</td>
                        <td class="px-3 py-2 border">
                            <textarea v-model="company_form.findings" class="form-control w-full" rows="3"
                                placeholder="Enter findings..." />
                        </td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 border font-medium">Recommendations</td>
                        <td class="px-3 py-2 border">
                            <textarea v-model="company_form.recommendations" class="form-control w-full" rows="3"
                                placeholder="Enter recommendations..." />
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td class="px-3 py-2 border font-medium">Findings</td>
                        <td class="px-3 py-2 border">
                            <textarea :value="onsite.findings" class="form-control w-full" rows="3"
                                placeholder="Enter findings..."
                                @input="$emit('update-onsite', { field: 'findings', value: $event.target.value })" />
                        </td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 border font-medium">Recommendations</td>
                        <td class="px-3 py-2 border">
                            <textarea :value="onsite.recommendations" class="form-control w-full" rows="3"
                                placeholder="Enter recommendations..."
                                @input="$emit('update-onsite', { field: 'recommendations', value: $event.target.value })" />
                        </td>
                    </tr>
                </tbody>
            </table> -->
        </div>
    </Fieldset>
</template>

<script setup lang="ts">
import Fieldset from 'primevue/fieldset';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import { reactive, ref, computed } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { Upload, View, CircleCheck } from 'lucide-vue-next';


// Props
const props = defineProps({
    title: String,
    roleId: Number,
    application_status: String,
    collapsed: { type: Boolean, default: true },
    rows: Array,
    company_form: Object,
    onsite: {
        type: Object,
        default: () => ({ findings: '', recommendations: '' })
    }
});

// Local reactive collapsed
const collapsed = ref(props.collapsed);

// File input refs
const fileInputRefs = reactive<{ [key: number]: HTMLInputElement | null }>({});

// Computed
const isDraft = computed(() => props.application_status === 'Draft');

// PrimeVue helpers
const confirm = useConfirm();
const toast = useToast();

// Emits
const emit = defineEmits([
    'view-file',
    'update-remarks',
    'update-assessment',
    'update-onsite',
    'upload-resubmission',
    'remove-resubmission'
]);

// Trigger hidden file input
const triggerFileInput = (checklistId: number) => {
    fileInputRefs[checklistId]?.click();
};

// Handle file selection
const handleFileSelection = (event: Event, checklistId: number) => {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const files = Array.from(target.files);
    requireResubmissionConfirmation(checklistId, files);

    target.value = ''; // Reset input
};

// Show confirmation before upload
const requireResubmissionConfirmation = (checklistEntryId: number, files: File[]) => {
    confirm.require({
        group: 'headless',
        header: 'Confirm Resubmission',
        message: 'You are about to resubmit files for this requirement. Do you want to proceed?',
        accept: () => {
            emit('upload-resubmission', checklistEntryId, files);
            toast.add({
                severity: 'info',
                summary: 'Uploading',
                detail: `${files.length} file(s) are being uploaded...`,
                life: 1500
            });
        },
        reject: () => {
            toast.add({
                severity: 'warn',
                summary: 'Cancelled',
                detail: 'File resubmission cancelled.',
                life: 1500
            });
        }
    });
};

// Format timestamp
const formatDate = (dateString: string) => {
    const d = new Date(dateString);
    return d.toLocaleString([], { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>