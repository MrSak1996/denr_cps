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
        { label: 'Supporting Document', field: 'permit' }
    ]
}

const requiredDocs = computed(() =>
    purposeRequirements[selectedPurpose.value] || []
)

/* -------------------- FILE STATE -------------------- */
const uploadFiles = reactive<Record<string, File | null>>({})

const handleFileUpload = (event: Event, field: string) => {
    const file = (event.target as HTMLInputElement).files?.[0]
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
}

/* -------------------- FILE PREVIEW -------------------- */
const selectedFile = ref<any>(null)
const showModal = ref(false)

const openFileModal = (file: any) => {
    selectedFile.value = file
    showModal.value = true
}

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
        application_type: props.application_type,
        purpose: selectedPurpose.value,
        suppliers: props.suppliers,
        files: uploadFiles
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

            <Button class="w-full bg-blue-900" @click="defaultSupplierDialog = true">
                Chainsaw Supplier Form
            </Button>

            <!-- PURPOSE -->
            <FloatLabel class="mt-2">
                <Select v-model="selectedPurpose" :options="options" class="w-full" />
                <label>Purpose of Purchase</label>
            </FloatLabel>

            <!-- REQUIRED DOCS -->
            <div v-if="requiredDocs.length" class="p-3 bg-blue-50 border border-blue-200 mt-4 rounded-lg">

                <div v-for="doc in requiredDocs" :key="doc.field" class="mb-3">

                    <div class="flex items-center gap-2 mb-2">
                        <Info class="w-4 h-4 text-blue-600" />
                        <span class="text-sm font-medium text-blue-800">
                            Required: {{ doc.label }}
                        </span>
                    </div>

                    <!-- UPLOAD -->
                    <div class="relative border-2 border-dashed p-4 rounded bg-white">
                        <input type="file" class="absolute inset-0 opacity-0 cursor-pointer"
                            @change="(e) => handleFileUpload(e, doc.field)" />

                        <div class="text-sm text-gray-500">
                            Click or drop PDF file
                        </div>
                    </div>

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