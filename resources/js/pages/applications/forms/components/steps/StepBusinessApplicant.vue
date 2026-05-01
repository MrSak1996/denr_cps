<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { computed, ref } from 'vue';

import axios from 'axios';

import Fieldset from 'primevue/fieldset';
import FloatLabel from 'primevue/floatlabel';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import ProgressBar from 'primevue/progressbar';

import { useToast } from 'primevue/usetoast';
import FileCard from '../../file_card.vue';
import { Info } from 'lucide-vue-next';

const toast = useToast();

const props = defineProps({
    form: { type: Object, required: true },
    application_type: String,
    isProcessing: { type: Boolean, default: false },
    mode: String,
    files: Array,
});

const emit = defineEmits(['next', 'proceed']);

const isEdit = computed(() => props.mode === 'edit');

/* ---------------------------
   LOADING STATE
----------------------------*/
const isLoading = ref(false);

/* ---------------------------
   FILE STATE
----------------------------*/
const files = computed(() => {
    return (props.files || []).map((file: any) => ({
        id: file.id,
        application_id: file.application_id,
        attachment_id: file.attachment_id,
        name: file.file_name,
        url: file.file_url,
    }));
});

/* ---------------------------
   FILE PREVIEW
----------------------------*/
const selectedFile = ref<any>(null);
const showModal = ref(false);

const openFileModal = (file: any) => {
    selectedFile.value = file;
    showModal.value = true;
};

const getEmbedUrl = (url: string) => {
    if (!url) return '';
    return url.replace('/view', '/preview');
};

/* ---------------------------
   FILE UPDATE
----------------------------*/
const selectedFileToUpdate = ref<any>(null);
const updateFileInput = ref<HTMLInputElement | null>(null);

const triggerUpdateFile = (file: any) => {
    selectedFileToUpdate.value = file;
    updateFileInput.value?.click();
};

const handleFileUpdate = async (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file || !selectedFileToUpdate.value) return;

    try {
        const formData = new FormData();
        formData.append('application_id', selectedFileToUpdate.value.application_id);
        formData.append('attachment_id', selectedFileToUpdate.value.attachment_id);
        formData.append('file', file);
        formData.append('name', selectedFileToUpdate.value.name);

        const res = await axios.post(
            'https://cps.denrcalabarzon.com/api/files/update',
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        const index = files.value.findIndex(f => f.id === selectedFileToUpdate.value.id);
        if (index !== -1) {
            files.value[index] = res.data.updatedFile;
        }

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'File updated successfully',
            life: 3000,
        });

    } catch (err) {
        console.error(err);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update file',
            life: 3000,
        });
    } finally {
        if (updateFileInput.value) updateFileInput.value.value = '';
        selectedFileToUpdate.value = null;
    }
};

/* ---------------------------
   FORM
----------------------------*/
const formData = computed(() => props.form);

const PREFIX = 'DENR-IV-A-';

const permitNo = computed({
    get: () => formData.value.permit_no || PREFIX,
    set: (val) => {
        formData.value.permit_no = val.startsWith(PREFIX)
            ? val
            : PREFIX + val;
    },
});

/* ---------------------------
   SAVE (with loading like StepApplicant)
----------------------------*/
const save = () => {
    if (props.isProcessing) return;

    isLoading.value = true;

    emit('next', {
        ...props.form,
        application_type: props.application_type,
    });
};

/* ---------------------------
   FILE UPLOAD (PDF ONLY)
----------------------------*/
const handleFileUpload = (event: Event, field: string) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    const isPDF =
        file.type === 'application/pdf' ||
        file.name.toLowerCase().endsWith('.pdf');

    if (!isPDF) {
        toast.add({
            severity: 'warn',
            summary: 'Invalid File',
            detail: 'Only PDF files are allowed.',
            life: 3000,
        });
        return;
    }

    props.form[field] = file;

    toast.add({
        severity: 'success',
        summary: 'Uploaded',
        detail: 'File uploaded successfully',
        life: 3000,
    });
};
</script>

<template>
    <div class="space-y-6">

        <!-- STATUS -->
        <div class="flex items-center gap-2" v-if="isEdit">
            <Info class="h-5 w-5" />
            <h1 class="text-xl font-semibold">Application Status:</h1>
            <Tag severity="danger">
                {{ props.form.status_title }}
            </Tag>
        </div>

        <!-- APPLICATION INFO -->
        <Fieldset legend="Chainsaw Application">
            <div class="mt-4 grid gap-4 md:grid-cols-3">

                <FloatLabel>
                    <InputText v-model="props.form.application_no" class="w-full" disabled />
                    <label>Application No.</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="permitNo" class="w-full" disabled />
                    <label>Permit No.</label>
                </FloatLabel>

                <FloatLabel>
                    <Select
                        v-model="props.form.classification"
                        :options="['Highly Technical']"
                        class="w-full"
                    />
                    <label>Classification</label>
                </FloatLabel>

            </div>
        </Fieldset>

        <!-- BUSINESS INFO -->
        <Fieldset legend="Business Information">
            <div class="mt-4 grid gap-4 md:grid-cols-3">

                <FloatLabel class="md:col-span-2">
                    <InputText v-model="props.form.company_name" class="w-full" />
                    <label>Company Name</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="props.form.authorized_representative" class="w-full" />
                    <label>Authorized Representative</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="props.form.company_mobile_no" class="w-full" />
                    <label>Mobile No</label>
                </FloatLabel>

                <FloatLabel>
                    <Select
                        v-model="props.form.type_of_transaction"
                        :options="['G2C','G2B','G2G']"
                        class="w-full"
                    />
                    <label>Type of Transaction</label>
                </FloatLabel>

            </div>
        </Fieldset>

        <!-- FILES -->
        <Fieldset legend="Requirements">

            <!-- EDIT MODE -->
            <div v-if="isEdit">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <FileCard
                        v-for="(file, index) in files"
                        :key="index"
                        :file="file"
                        @openPreview="openFileModal"
                        @updateFile="triggerUpdateFile"
                    />
                </div>

                <input
                    type="file"
                    ref="updateFileInput"
                    class="hidden"
                    @change="handleFileUpdate"
                />
            </div>

            <!-- CREATE MODE -->
            <div v-else class="space-y-4">

                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Application Letter (PDF)
                    </label>

                    <input
                        type="file"
                        accept="application/pdf"
                        @change="(e) => handleFileUpload(e, 'request_letter')"
                        class="w-full border p-3 rounded-lg border-dashed"
                    />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Secretary Certificate (PDF)
                    </label>

                    <input
                        type="file"
                        accept="application/pdf"
                        @change="(e) => handleFileUpload(e, 'soc_certificate')"
                        class="w-full border p-3 rounded-lg border-dashed"
                    />
                </div>

            </div>
        </Fieldset>

        <!-- SAVE BUTTON -->
        <div class="pt-6 flex justify-end">
            <Button
                :disabled="props.isProcessing"
                class="w-full bg-green-900 text-white hover:bg-green-500"
                @click="save"
            >
                {{ props.isProcessing ? 'Saving...' : 'Save & Continue' }}
            </Button>
        </div>

        <!-- LOADING DIALOG -->
        <Dialog
            v-model:visible="isLoading"
            modal
            :closable="false"
            :draggable="false"
            :style="{ width: '300px' }"
        >
            <div class="flex flex-col items-center gap-4 py-4">
                <span>Saving, please wait...</span>
                <ProgressBar
                    mode="indeterminate"
                    style="width: 100%; height: 6px"
                />
            </div>
        </Dialog>

        <!-- FILE PREVIEW -->
        <Dialog
            v-model:visible="showModal"
            modal
            header="File Preview"
            :style="{ width: '70vw' }"
        >
            <iframe
                v-if="selectedFile"
                :src="getEmbedUrl(selectedFile.url)"
                width="100%"
                height="500"
                allow="autoplay"
            />
        </Dialog>

    </div>
</template>