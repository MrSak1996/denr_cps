<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Fieldset from 'primevue/fieldset'
import Dialog from 'primevue/dialog'
import FloatLabel from 'primevue/floatlabel'
import ProgressBar from 'primevue/progressbar'
import { Button } from '@/components/ui/button'
import ChainsawSupplierForm from '@/components/ChainsawSupplierForm.vue'
import FileCard from '../../file_card.vue'
import { MonitorUp, Info } from 'lucide-vue-next'

/* -------------------- EMITS -------------------- */
const emit = defineEmits(['next', 'back', 'supplierSaved'])

/* -------------------- PROPS -------------------- */
const props = defineProps({
    form: { type: Object, required: true },
    suppliers: { type: Array, default: () => [] },
    files: { type: Array, default: () => [] },
    application_type: String,
    mode: String,
    currentStep: Number,
    isProcessing: { type: Boolean, default: false }
})

const toast = useToast()

const isEdit = computed(() => props.mode === 'edit')

/* -------------------- PURPOSE -------------------- */
const selectedPurpose = computed({
    get: () => props.form.purpose,
    set: (val) => props.form.purpose = val
})

/* -------------------- OPTIONS -------------------- */
const options = [
    'For cutting of trees with legal permit',
    'For post-calamity clearing operations',
    'For farm lot/tree orchard maintenance',
    'For maintenance of trees/vegetation within private property',
    'For cutting/trimming of trees posing danger within a private property',
    'For selling / re-selling',
    'For cutting of trees to be used for house repair/perimeter fencing/residential area development',
    'For commercial use',
    'Forestry/landscaping service provider',
    'Other legal purpose(s)',
    'Other Supporting Documents'
]

/* -------------------- PURPOSE REQUIREMENTS -------------------- */
const purposeRequirements: Record<string, { label: string; field: string }[]> = {
    'For selling / re-selling': [
        { label: 'Mayor’s Permit / DTI', field: 'mayorDTI' }
    ],
    'Forestry/landscaping service provider': [
        { label: 'Mayor’s Permit / DTI', field: 'mayorDTI' }
    ],
    'Other legal purpose(s)': [
        { label: 'Notarized Affidavit', field: 'affidavit' }
    ],
    'Other Supporting Documents': [
        { label: 'Supporting Document', field: 'otherDocs' }
    ]
}

const requiredDocs = computed(() =>
    purposeRequirements[selectedPurpose.value] || []
)

/* -------------------- FILE STATE -------------------- */
const uploadFiles = reactive<Record<string, File | null>>({})

const handleFileUpload = (event: Event, field: string) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return

    if (file.type !== 'application/pdf') {
        toast.add({ severity: 'error', summary: 'Invalid', detail: 'PDF only', life: 3000 })
        return
    }

    if (file.size > 5 * 1024 * 1024) {
        toast.add({ severity: 'error', summary: 'Too large', detail: 'Max 5MB', life: 3000 })
        return
    }

    uploadFiles[field] = file
    toast.add({
        severity: 'success',
        summary: 'Uploaded',
        detail: 'This file is valid for upload.',
        life: 3000,
    });
}
const showFiles = computed(() => {
    return (props.files || [])
        .map((file: any) => ({
            id: file.id,
            application_type: file.application_type,
            application_id: file.application_id,
            attachment_id: file.attachment_id,
            name: file.file_name,
            url: file.file_url,
        }))
        .filter((file: any) =>
            typeof file.name === 'string' &&
            file.application_id === props.form.id && (
                file.name.startsWith('other_supporting_documents') ||
                file.name.startsWith('permit_to_sell') ||
                file.name.startsWith('mayors_permit') ||
                file.name.startsWith('notarized_affidavit')
            )
        );
});

/* -------------------- FILE PREVIEW -------------------- */
const selectedFile = ref<any>(null)
const showModal = ref(false)
const selectedFileToUpdate = ref(null);
const updateFileInput = ref(null);
const openFileModal = (file: any) => {
    selectedFile.value = file
    showModal.value = true
}
const triggerUpdateFile = (file) => {
    selectedFileToUpdate.value = file;
    updateFileInput.value.click();
};
const handleFileUpdate = async (event) => {
    const newFile = event.target.files[0];
    if (!newFile || !selectedFileToUpdate.value) return;

    try {
        const formData = new FormData();
        formData.append('application_id', selectedFileToUpdate.value.application_id);
        formData.append('application_type', selectedFileToUpdate.value.application_type);
        formData.append('file', newFile);
        formData.append('attachment_id', selectedFileToUpdate.value.attachment_id);
        formData.append('name', selectedFileToUpdate.value.name);

        const response = await axios.post('https://cps.denrcalabarzon.com/api/files/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        // Update file list
        const updatedIndex = files.value.findIndex((f) => f.id === selectedFileToUpdate.value.id);
        if (updatedIndex !== -1) {
            files.value[updatedIndex] = response.data.updatedFile;
        }

        toast.add({ severity: 'success', summary: 'Successful', detail: 'File updated successfully', life: 3000 });
    } catch (error) {
        console.error(error);
        toast.add({ severity: 'error', summary: 'Successful', detail: 'Failed to update the file.', life: 3000 });
    } finally {
        updateFileInput.value.value = ''; // reset file input
        selectedFileToUpdate.value = null;
    }
};

const getEmbedUrl = (url: string) =>
    url ? url.replace('/view', '/preview') : ''

/* -------------------- SUPPLIER -------------------- */
const defaultSupplierDialog = ref(false)

const handleSupplierSaved = (data: any) => {
    emit('supplierSaved', data)
    defaultSupplierDialog.value = false
}

/* -------------------- SUBMIT -------------------- */
const isLoading = ref(false)

const submitStep = () => {
    if (props.isProcessing) return

    isLoading.value = true
    emit('next', {
        ...props.form,

        permit: uploadFiles.permit,
        affidavit: uploadFiles.affidavit,
        mayorDTI: uploadFiles.mayorDTI,
        otherDocs: uploadFiles.otherDocs,
        application_type: props.application_type,
    })
}

</script>
<template>
    <div class="space-y-6">

        <!-- STATUS -->
        <div class="flex items-center gap-2" v-if="isEdit">
            <Info class="h-5 w-5" />
            <h1 class="text-xl font-semibold">
                Application Status:
            </h1>
            <Tag severity="danger">{{ props.form.status_title }}</Tag>
        </div>

        <Fieldset legend="Chainsaw Information">

            <!-- SUPPLIER -->
            <Dialog v-model:visible="defaultSupplierDialog" modal header="Supplier Form">
                <ChainsawSupplierForm :supplierData="props.suppliers" @save="handleSupplierSaved"
                    @cancel="defaultSupplierDialog = false" />
            </Dialog>

            <Button class="w-full bg-blue-900 hover:bg-blue-600" @click="defaultSupplierDialog = true">
                Chainsaw Supplier Form
            </Button>

            <!-- PURPOSE -->
            <FloatLabel>Purpose of Purchase</FloatLabel>
            <Select v-model="selectedPurpose" :options="options" class="w-full" />
          
            <!-- REQUIRED DOCS -->

            <div v-if="isEdit && showFiles.length > 0" class="md:col-span-2">
                <div class="container">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <FileCard v-for="(file, index) in showFiles" :key="index" :file="file"
                            @openPreview="openFileModal" @updateFile="triggerUpdateFile" />

                    </div>
                    <input type="file" ref="updateFileInput" class="hidden" @change="handleFileUpdate" />

                </div>
            </div>
            <div v-else>
                <div v-if="requiredDocs.length" class="mt-4 rounded-lg">

                    <div v-for="doc in requiredDocs" :key="doc.field" class="mb-3">

                        <div
                            class="mt-4 group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-white p-8 transition hover:bg-gray-50">
                            <!-- Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="mb-3 h-10 w-10 text-gray-400 group-hover:text-gray-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4" />
                            </svg>

                            <!-- Text -->
                            <p class="font-medium text-gray-700">Required: <b>{{ doc.label }}</b></p>
                            <p class="mt-1 text-sm text-gray-500">PDF File up to 5MB</p>

                            <input type="file" id="permitToSell" accept="application/pdf"
                                @change="(e) => handleFileUpload(e, doc.field)"
                                class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />

                        </div>
                    </div>
                </div>

                <FloatLabel>Permit to Sell:</FloatLabel>
                <div
                    class="mt-4 group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-white p-8 transition hover:bg-gray-50">
                    <!-- Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="mb-3 h-10 w-10 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4" />
                    </svg>

                    <!-- Text -->
                    <p class="font-medium text-gray-700">Upload Permit to Sell</p>
                    <p class="mt-1 text-sm text-gray-500">PDF File up to 5MB</p>

                    <!-- Click overlay -->
                    <input type="file" id="permitToSell" accept="application/pdf"
                        @change="(e) => handleFileUpload(e, 'permit')"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />
                </div>
            </div>




            <!-- ACTION -->
            <div :class="[
                'w-full pt-6',
                currentStep > 1 ? 'grid grid-cols-2 gap-4' : 'flex justify-end'
            ]">
                <Button v-if="currentStep > 1" @click="$emit('back')" class="w-full bg-gray-300 hover:bg-gray-400">
                    Back
                </Button>

                <Button :disabled="isProcessing"
                    class="w-full bg-green-900 text-white transition-colors hover:bg-green-500 text-white"
                    @click="submitStep">
                    {{ isProcessing ? 'Saving...' : 'Save & Continue' }}
                </Button>
            </div>

        </Fieldset>

        <!-- LOADING -->
        <Dialog v-model:visible="isLoading" modal :closable="false">
            <ProgressBar mode="indeterminate" />
        </Dialog>

        <!-- PREVIEW -->
        <Dialog v-model:visible="showModal" modal header="File Preview" style="width:70vw">
            <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500" />
        </Dialog>
        <Dialog v-model:visible="isLoading" modal :closable="false" :draggable="false" :style="{ width: '300px' }">
            <div class="flex flex-col items-center gap-4 py-4">
                <span>Saving, please wait...</span>
                <ProgressBar mode="indeterminate" style="width: 100%; height: 6px" />
            </div>
        </Dialog>

    </div>
</template>