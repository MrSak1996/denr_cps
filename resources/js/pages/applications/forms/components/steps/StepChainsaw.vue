<script setup lang="ts">
import { ref, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

import Select from 'primevue/select'
import Fieldset from 'primevue/fieldset'
import Dialog from 'primevue/dialog'
import FloatLabel from 'primevue/floatlabel'
import InputText from 'primevue/inputtext'
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
const isCreate = computed(() => props.mode === 'create')

/* -------------------- STATE -------------------- */
const defaultSupplierDialog = ref(false)
const showModal = ref(false)
const isLoading = ref(false)

const selectedFile = ref<any>(null)
const selectedFileToUpdate = ref<any>(null)
const updateFileInput = ref<HTMLInputElement | null>(null)

/* -------------------- PURPOSE (FIXED CORE ISSUE) -------------------- */
const selectedPurpose = computed(() => {
    if (isEdit.value) {
        return props.suppliers?.[0]?.purpose || null
    }
    return props.form.purpose
})

/* -------------------- FILE FILTER (FIXED) -------------------- */
const showFiles = computed(() => {
    return (props.files || [])
        .filter((file: any) => {
            if (!file?.file_name) return false

            const validPrefixes = [
                'notarized_affidavit_',
                'mayors_permit_',
                'permit_'
            ]

            return (
                file.application_id === props.form.application_id &&
                validPrefixes.some(prefix => file.file_name.startsWith(prefix))
            )
        })
        .map((file: any) => ({
            id: file.id,
            application_type: file.application_type,
            application_id: file.application_id,
            attachment_id: file.attachment_id,
            name: file.file_name,
            url: file.file_url
        }))
})

/* -------------------- UPLOAD TYPE -------------------- */
const getUploadType = (purpose: string | null) => {
    if (!purpose) return null

    if (['For selling / re-selling', 'Forestry/landscaping service provider'].includes(purpose)) {
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

/* -------------------- FILE UPLOAD -------------------- */
const uploadFiles = ref<Record<string, File | null>>({
    mayorDTI: null,
    affidavit: null,
    permit: null
})

const handleFileUpload = (event: Event, field: string | null) => {
    if (!field) return

    const file = (event.target as HTMLInputElement).files?.[0]
    if (!file) return

    if (file.type !== 'application/pdf') {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Only PDF allowed', life: 3000 })
        return
    }

    if (file.size > 5 * 1024 * 1024) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Max 5MB', life: 3000 })
        return
    }

    uploadFiles.value[field] = file
}

/* -------------------- EMBED -------------------- */
const getEmbedUrl = (url: string) => url?.replace('/view', '/preview') || ''

/* -------------------- SUBMIT -------------------- */
const submitStep = () => {
    if (props.isProcessing) return
    isLoading.value = true

    emit('next', {
        application_type: props.application_type,
        purpose: isEdit.value
            ? props.suppliers.map(s => s.purpose)
            : props.form.purpose,
        suppliers: props.suppliers,
        ...uploadFiles.value
    })
}

/* -------------------- FILE MODAL -------------------- */
const openFileModal = (file: any) => {
    selectedFile.value = file
    showModal.value = true
}

/* -------------------- FILE UPDATE -------------------- */
const triggerUpdateFile = (file: any) => {
    selectedFileToUpdate.value = file
    updateFileInput.value?.click()
}

const handleFileUpdate = async (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0]
    if (!file || !selectedFileToUpdate.value) return

    const formData = new FormData()
    formData.append('application_id', selectedFileToUpdate.value.application_id)
    formData.append('application_type', selectedFileToUpdate.value.application_type)
    formData.append('file', file)
    formData.append('attachment_id', selectedFileToUpdate.value.attachment_id)
    formData.append('name', selectedFileToUpdate.value.name)

    try {
        await axios.post('https://cps.denrcalabarzon.com/api/files/update', formData)
        toast.add({ severity: 'success', summary: 'Updated', detail: 'File updated', life: 3000 })
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Update failed', life: 3000 })
    } finally {
        updateFileInput.value = null
        selectedFileToUpdate.value = null
    }
}

/* -------------------- SUPPLIER -------------------- */
const handleSupplierSaved = async (data: any) => {
    emit('supplierSaved', data)
    defaultSupplierDialog.value = false
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center gap-2" v-if="isEdit">
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

            <!-- FILES -->
            <div class="mt-6">

                <!-- EDIT MODE FILES -->
                <div v-if="isEdit && showFiles.length > 0">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <FileCard v-for="(file, i) in showFiles" :key="i" :file="file" @openPreview="openFileModal"
                            @updateFile="triggerUpdateFile" />
                    </div>

                    <input type="file" ref="updateFileInput" class="hidden" @change="handleFileUpdate" />
                </div>

                <!-- UPLOAD (ALWAYS REACTIVE) -->
                <div v-if="getUploadType(selectedPurpose)" class="mt-6">
                    <label class="text-sm font-medium">Upload Document</label>

                    <div class="relative mt-2 border-4 border-dashed p-6 rounded-xl bg-gray-50">
                        <MonitorUp class="w-10 h-10 text-blue-400 mb-2" />

                        <p class="text-sm text-gray-600">Click or drag PDF file</p>

                        <input type="file" class="absolute inset-0 opacity-0 cursor-pointer"
                            @change="(e) => handleFileUpload(e, getUploadType(selectedPurpose))" />
                    </div>
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
        <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
            <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500"
                allow="autoplay"></iframe>
        </Dialog>
    </div>
</template>