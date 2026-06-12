<script setup lang="ts">
import axios from 'axios'
import { reactive, watch, computed, ref } from 'vue'

import { Button } from '@/components/ui/button'
import { useToast } from 'primevue/usetoast'

import { Info } from 'lucide-vue-next'

import ProgressBar from 'primevue/progressbar'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import FloatLabel from 'primevue/floatlabel'
import Fieldset from 'primevue/fieldset'
import FileCard from '../../file_card.vue'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import DatePicker from 'primevue/datepicker'
const emit = defineEmits(['next', 'back'])
const toast = useToast()

const props = defineProps({
  form: {
    type: Object,
    required: true
  },

  application_type: String,
  mode: String,
  currentStep: Number,

  files: {
    type: Array,
    default: () => []
  },

  isProcessing: {
    type: Boolean,
    default: false
  }
})

const isEdit = computed(() => props.mode === 'edit')
const isCreate = computed(() => props.mode === 'create')

/* -----------------------------------
   PAYMENT STATE
----------------------------------- */

const payment = reactive({
  application_attachment_id: 0,

  official_receipt: '',

  permit_fee: 500,

  or_copy: null as File | null,

  // ✅ MUST BE DATE OBJECT
  date_of_payment: null,

  remarks: ''
})

/* -----------------------------------
   UI STATE
----------------------------------- */

const showModal = ref(false)
const isLoading = ref(false)

const selectedFile = ref<any>(null)

const selectedFileToUpdate = ref<any>(null)

const updateFileInput = ref<HTMLInputElement | null>(null)

/* -----------------------------------
   FILE HANDLERS
----------------------------------- */

const handleORFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement

  const file = target.files?.[0]

  if (!file) return

  // ✅ PDF VALIDATION
  if (file.type !== 'application/pdf') {
    toast.add({
      severity: 'error',
      summary: 'Invalid File',
      detail: 'Only PDF files are allowed.',
      life: 3000
    })

    return
  }

  // ✅ 5MB LIMIT
  if (file.size > 5 * 1024 * 1024) {
    toast.add({
      severity: 'error',
      summary: 'File Too Large',
      detail: 'Maximum file size is 5MB.',
      life: 3000
    })

    return
  }

  payment.or_copy = file
}

/* -----------------------------------
   FILE LIST
----------------------------------- */

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
                file.name.startsWith('official_receipt')
            )
        );
});

/* -----------------------------------
   WATCH FORM
----------------------------------- */

watch(
  () => props.form,
  (val: any) => {
    if (!val) return

    payment.official_receipt = val.official_receipt ?? ''

    payment.permit_fee = val.permit_fee ?? 500

    payment.remarks = val.remarks ?? ''

    // ✅ CONVERT STRING TO DATE
    payment.date_of_payment = val.date_of_payment
      ? new Date(val.date_of_payment)
      : null
  },
  {
    immediate: true,
    deep: true
  }
)

/* -----------------------------------
   SUBMIT
----------------------------------- */

const submitStep = async () => {
  if (props.isProcessing) return

  // ✅ VALIDATION FIRST
  if (!payment.official_receipt) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'O.R Number is required.',
      life: 3000
    })

    return
  }

  if (props.mode === 'create' && !payment.or_copy) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please upload the Official Receipt.',
      life: 3000
    })

    return
  }

  isLoading.value = true

  try {
    // ✅ SEND CLEAN PAYLOAD
    emit('next', {
      ...props.form,

      application_id: props.form.application_id,

      application_type: props.application_type,

      official_receipt: payment.official_receipt,

      permit_fee: payment.permit_fee,

      ...(payment.or_copy && {
        or_copy: payment.or_copy
      }),

      // ✅ FORMAT DATE
      

     
  date_of_payment: payment.date_of_payment
    ? new Date(
        payment.date_of_payment.getTime() -
        payment.date_of_payment.getTimezoneOffset() * 60000
      )
        .toISOString()
        .split('T')[0]
    : null,

      remarks: payment.remarks
    })
  } catch (error) {
    console.error(error)

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to process request.',
      life: 3000
    })
  } finally {
    isLoading.value = false
  }
}

/* -----------------------------------
   FILE PREVIEW
----------------------------------- */

const openFileModal = (file: any) => {
  selectedFile.value = file
  showModal.value = true
}

const getEmbedUrl = (url: string) => {
  if (!url) return ''

  return url.replace('/view', '/preview')
}

/* -----------------------------------
   FILE UPDATE
----------------------------------- */

const triggerUpdateFile = (file: any) => {
  selectedFileToUpdate.value = file

  updateFileInput.value?.click()
}
const deleteApplicantFile = async (file) => {

    const confirmDelete = confirm(
        `Are you sure you want to delete "${file.name}" ?`
    );

    if (!confirmDelete) return;

    try {

        const response = await axios.delete(
            'https://cps.denrcalabarzon.com/api/files/delete',
            {
                data: {
                    application_id:file.application_id,
                    attachment_id: file.attachment_id,
                },
            }
        );

        // ✅ Remove file from array
        showFiles.value = showFiles.value.filter(
            (f) => f.attachment_id !== file.attachment_id
        );

        toast.add({
            severity: 'success',
            summary: 'Deleted',
            detail: 'File deleted successfully',
            life: 3000,
        });
        location.reload();

    } catch (error) {

        console.error(error);

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail:
                error?.response?.data?.message ||
                'Failed to delete file.',
            life: 3000,
        });
    }
};

const handleFileUpdate = async (event: Event) => {
  const target = event.target as HTMLInputElement

  const newFile = target.files?.[0]

  if (!newFile || !selectedFileToUpdate.value) return

  try {
    isLoading.value = true

    const formData = new FormData()

    formData.append(
      'application_id',
      selectedFileToUpdate.value.application_id
    )

    formData.append(
      'application_type',
      selectedFileToUpdate.value.application_type
    )

    formData.append(
      'attachment_id',
      selectedFileToUpdate.value.attachment_id
    )

    formData.append(
      'name',
      selectedFileToUpdate.value.name
    )

    formData.append('file', newFile)

    await axios.post(
      'https://cps.denrcalabarzon.com/api/files/update',
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'File updated successfully.',
      life: 3000
    })
  } catch (error) {
    console.error(error)

    toast.add({
      severity: 'error',
      summary: 'Upload Failed',
      detail: 'Failed to update file.',
      life: 3000
    })
  } finally {
    isLoading.value = false

    if (updateFileInput.value) {
      updateFileInput.value.value = ''
    }

    selectedFileToUpdate.value = null
  }
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

      <Tag severity="danger">
        {{ props.form.status_title }}
      </Tag>
    </div>

    <!-- FIELDSET -->

    <Fieldset legend="Payment of Application Fee">

      <div :class="{ 'pointer-events-none opacity-60': isProcessing }">

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 mt-4">

          <!-- APPLICATION NUMBER -->

          <FloatLabel>
            <InputText v-model="props.form.application_no" disabled class="w-full font-bold" />

            <label>Application No.</label>
          </FloatLabel>

          <!-- OR NUMBER -->

          <FloatLabel>
            <InputText v-model="payment.official_receipt" class="w-full" />

            <label>O.R No.</label>
          </FloatLabel>

          <!-- PERMIT FEE -->

          <FloatLabel>
            <InputNumber v-model="payment.permit_fee" class="w-full" />

            <label>Permit Fee</label>
          </FloatLabel>

          <!-- DATE -->

          <FloatLabel>
            <DatePicker v-model="payment.date_of_payment" date-format="yy-mm-dd" show-icon class="w-full" />  
            <label>Date of Payment</label>
          </FloatLabel>

          <!-- EXISTING FILES -->

          <div v-if="isEdit && showFiles.length > 0" class="md:col-span-2">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

              <FileCard
              v-for="(file, index) in showFiles"
              :key="index"
              :file="file"
              @openPreview="openFileModal"
              @updateFile="triggerUpdateFile"
              @deleteFile="deleteApplicantFile"
              />

            </div>

            <input type="file" ref="updateFileInput" class="hidden" accept="application/pdf"
              @change="handleFileUpdate" />
          </div>

          <!-- UPLOAD -->

          <div v-else
            class="mt-4 grid grid-cols-1 group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-white p-8 transition hover:bg-gray-50">

            <svg xmlns="http://www.w3.org/2000/svg" class="mb-3 h-10 w-10 text-gray-400 group-hover:text-gray-500"
              fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4" />
            </svg>

            <p class="font-medium text-gray-700">
              Upload Official Receipt
            </p>

            <p class="mt-1 text-sm text-gray-500">
              PDF File up to 5MB
            </p>

            <input type="file" accept="application/pdf" @change="handleORFileUpload"
              class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />
          </div>

        </div>
      </div>
    </Fieldset>

    <!-- ACTIONS -->

    <div :class="[
      'w-full pt-6',
      currentStep > 1
        ? 'grid grid-cols-2 gap-4'
        : 'flex justify-end'
    ]">

      <Button v-if="currentStep > 1" @click="$emit('back')" class="w-full bg-gray-300 hover:bg-gray-400">
        Back
      </Button>

      <Button :disabled="isProcessing" class="w-full bg-green-900 text-white hover:bg-green-500" @click="submitStep">
        {{ isProcessing ? 'Saving...' : 'Save & Continue' }}
      </Button>
    </div>

    <!-- PREVIEW MODAL -->

    <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
      <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500" allow="autoplay" />
    </Dialog>

    <!-- LOADING -->

            <Dialog v-model:visible="isLoading" modal :closable="false" :draggable="false" :style="{ width: '300px' }">
            <div class="flex flex-col items-center gap-4 py-4">
                <span>Saving, please wait...</span>
                <ProgressBar mode="indeterminate" style="width: 100%; height: 6px" />
            </div>
        </Dialog>

  </div>
</template>