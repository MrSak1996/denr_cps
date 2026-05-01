<script setup lang="ts">
import { ref, computed } from 'vue'
import axios from 'axios';
import Select from 'primevue/select'
import Fieldset from 'primevue/fieldset'
import Dialog from 'primevue/dialog'
import FloatLabel from 'primevue/floatlabel'
import { Button } from '@/components/ui/button'
import ChainsawSupplierForm from '@/components/ChainsawSupplierForm.vue'
import { MonitorUp,Info } from 'lucide-vue-next'
import InputText from 'primevue/inputtext'
import { useToast } from 'primevue/usetoast';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import FileCard from '../../file_card.vue';
/* -------------------------------------------------------
| EMITS (FIXED)
------------------------------------------------------- */
const emit = defineEmits(['next', 'back', 'supplierSaved'])
const PREFIX = 'DENR-IV-A-';

/* -------------------------------------------------------
| PROPS
------------------------------------------------------- */
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    suppliers: {
        type: Array,
        default: () => []
    },
    application_type: String,
    currentStep: Number,
    mode: String,
    files: Array,
    isProcessing: {
        type: Boolean,
        default: false
    }
})
const toast = useToast();

const isEdit = computed(() => props.mode === 'edit');
const isCreate = computed(() => props.mode === 'create');

/* -------------------------------------------------------
| STATE
------------------------------------------------------- */
const defaultSupplierDialog = ref(false)

const files = ref({
    mayorDTI: null,
    affidavit: null,
    permit: null
})
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
                file.name.startsWith('notarized_affidavit_') ||
                file.name.startsWith('mayors_permit_') ||
                file.name.startsWith('permit_')
            )
        );
});
const showModal = ref(false);
const isLoading = ref(false);
const selectedFileToUpdate = ref(null);
const updateFileInput = ref(null);
const selectedFile = ref<any>(null);
/* -------------------------------------------------------
| OPTIONS
------------------------------------------------------- */
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
    'Other Supporting Documents',
]

/* -------------------------------------------------------
| COMPUTED UPLOAD TYPE
------------------------------------------------------- */
const getUploadType = (purpose: string | null) => {
    if (['For selling / re-selling', 'Forestry/landscaping service provider'].includes(purpose ?? '')) {
        return 'mayorDTI'
    }

    if (purpose === 'Other legal purpose(s)') {
        return 'affidavit'
    }

    if (purpose === 'Other Supporting Documents') {
        return 'permit'
    }

    return null
}

/* -------------------------------------------------------
| FILE HANDLER
------------------------------------------------------- */
const handleFileUpload = (event: Event, field: string | null) => {
    if (!field) return

    const file = (event.target as HTMLInputElement).files?.[0]
    if (!file) return

    if (file.type !== 'application/pdf') {
        alert('Only PDF files are allowed')
        return
    }

    if (file.size > 5 * 1024 * 1024) {
        alert('Max file size is 5MB')
        return
    }

    files.value[field] = file
}
const formData = computed(() => props.form);


const permitNo = computed({
    get: () => formData.value.permit_no || PREFIX,
    set: (value) => {
        formData.value.permit_no = value.startsWith(PREFIX) ? value : PREFIX + value;
    },
});
/* -------------------------------------------------------
| SUPPLIER HANDLER (FIXED EMIT)
------------------------------------------------------- */
const handleSupplierSaved = async (data: any) => {
    const success = await emit('supplierSaved', data)

    setTimeout(() => {
        defaultSupplierDialog.value = false;
    }, 2000);
}

/* -------------------------------------------------------
| STEP SUBMIT
------------------------------------------------------- */
const submitStep = () => {
    if (props.isProcessing) return
    isLoading.value = true;
    const purpose = props.suppliers.map(s => s.purpose)

    emit('next', {
        application_type: props.application_type,
        purpose,
        suppliers: props.suppliers,
        ...files.value
    })
}

const onFileChange = (e: Event, supplier: any) => {
    const type = getUploadType(supplier.purpose)
    handleFileUpload(e, type)
}
const openFileModal = (file: any) => {
    selectedFile.value = file;
    showModal.value = true;
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
const triggerUpdateFile = (file) => {
    selectedFileToUpdate.value = file;
    updateFileInput.value.click();
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center gap-2">
            <Info class="h-5 w-5" />
            <h1 class="text-xl font-semibold">
                Application Status:
            </h1>

            <Tag severity="danger">
                {{ props.form.status_title }}
            </Tag>
        </div>
        <Fieldset legend="Chainsaw Information">

            <!-- Supplier Dialog -->
            <Dialog v-model:visible="defaultSupplierDialog" modal header="Supplier Form">
                <ChainsawSupplierForm :supplierData=props.suppliers @cancel="defaultSupplierDialog = false"
                    @save="handleSupplierSaved" />
            </Dialog>

            <!-- Open Dialog -->
            <Button class="w-full bg-blue-900 hover:bg-blue-700" @click="defaultSupplierDialog = true">
                Chainsaw Supplier Form
            </Button>

            <!-- Purpose -->
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <FloatLabel>
                    <InputText v-model="props.form.application_no" :disabled="props.mode === 'edit'" class="w-full"
                        readonly />
                    <label>Application No.</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="permitNo" :disabled="props.mode === 'edit'" class="w-full" readonly />
                    <label>Permit No.</label>
                </FloatLabel>
            </div>

            <div v-for="(supplier, index) in suppliers" :key="index" class="mt-6">
                <FloatLabel>
                    <Select v-model="supplier.purpose" :options="options" class="w-full" />
                    <label>Purpose of Purchase</label>
                </FloatLabel>
            </div>

            <div class="mb-3" v-if="isEdit">
                <div class="container">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <FileCard v-for="(file, index) in showFiles" :key="index" :file="file"
                            @openPreview="openFileModal" @updateFile="triggerUpdateFile" />
                    </div>
                </div>
                <input type="file" ref="updateFileInput" class="hidden" @change="handleFileUpdate" />

            </div>

            <div v-else v-for="(supplier, index) in suppliers" :key="index" class="mt-6">

                <!-- ✅ SHOW ONLY IN CREATE MODE AND ONLY IF TYPE EXISTS -->
                <div v-if="getUploadType(supplier.purpose)"
                    class="relative mt-2 flex h-[330px] w-full cursor-pointer flex-col items-center justify-center rounded-xl border-4 border-dashed border-blue-400 bg-white transition hover:bg-blue-50">
                    <label class="text-sm font-medium">
                        Upload Required Document
                    </label>
                    <MonitorUp :size="64" class="mb-4 h-12 w-12 text-blue-400" />

                    <p class="mb-2 text-center text-sm text-gray-700">
                        Drag & drop file or click to upload
                    </p>

                    <p class="text-center text-xs text-gray-400">
                        PDF only, max 5MB
                    </p>

                    <input type="file" accept="application/pdf"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        @change="(e) => onFileChange(e, supplier)" />
                </div>

                <!-- ❌ SHOW ONLY IN CREATE MODE BUT NO TYPE -->
                <div v-else-if="isCreate && !getUploadType(supplier.purpose)"
                    class="p-4 text-gray-600 bg-gray-100 rounded mt-2">
                    No required document for this purpose.
                </div>

            </div>

            <!-- Actions -->
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

        <Dialog v-model:visible="isLoading" modal :closable="false" :draggable="false" :style="{ width: '300px' }">
            <div class="flex flex-col items-center gap-4 py-4">
                <span>Saving, please wait...</span>
                <ProgressBar mode="indeterminate" style="width: 100%; height: 6px" />
            </div>
        </Dialog>
    </div>
</template>