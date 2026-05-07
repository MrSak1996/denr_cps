<script setup lang="ts">
import axios from 'axios'
import { reactive, watch, computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { useToast } from 'primevue/usetoast';
import { Info } from 'lucide-vue-next'

import ProgressBar from 'primevue/progressbar';
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import FloatLabel from 'primevue/floatlabel'
import Fieldset from 'primevue/fieldset'
import FileCard from '../../file_card.vue'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag';
const emit = defineEmits(['next', 'back'])
const toast = useToast();

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  application_type: String,
  mode: String,
  currentStep: Number,
  files: Array,
  isProcessing: {
    type: Boolean,
    default: false
  }
})

const isEdit = computed(() => props.mode === 'edit');
const isCreate = computed(() => props.mode === 'create');
/* ✅ Single source of truth */
const payment = reactive({
  application_attachment_id: 0,
  official_receipt: '',
  permit_fee: 500,
  or_copy: null as File | null,
  date_of_payment: new Date().toISOString().slice(0, 10),
  remarks: ''
})

const showModal = ref(false);
const isLoading = ref(false);
const selectedFileToUpdate = ref(null);
const updateFileInput = ref(null);
const selectedFile = ref<any>(null);

/* ✅ File upload handler */
const handleORFileUpload = (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  payment.or_copy = file
}
const files = computed(() => {
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
      file.name.startsWith('valid_id_')
    );
});

/* ✅ Submit with validation */
// const submitStep = () => {
//   if (props.isProcessing) return

//   if (!payment.official_receipt) {
//     alert('O.R Number is required')
//     return
//   }

//   if (!payment.or_copy) {
//     alert('Please upload the Official Receipt file')
//     return
//   }

//   emit('next', {
//     ...props.form,
//     application_id: props.form.application_id,
//     application_type: props.application_type,

//     // 🔥 FLATTEN PAYMENT (IMPORTANT)
//     official_receipt: payment.official_receipt,
//     permit_fee: payment.permit_fee,
//     or_copy: payment.or_copy,
//     date_of_payment: payment.date_of_payment,
//     remarks: payment.remarks
//   })
// }
const submitStep = () => {
  if (props.isProcessing) return
  isLoading.value = true;
  if (!payment.official_receipt) {
    alert('O.R Number is required')
    return
  }

  // ✅ Only require file in CREATE mode
  if (props.mode === 'create' && !payment.or_copy) {
    alert('Please upload the Official Receipt file')
    return
  }

  emit('next', {
    ...props.form,
    application_id: props.form.application_id,
    application_type: props.application_type,

    official_receipt: payment.official_receipt,
    permit_fee: payment.permit_fee,

    // ✅ Only send file if exists
    ...(payment.or_copy && { or_copy: payment.or_copy }),

    date_of_payment: payment.date_of_payment,
    remarks: payment.remarks
  })
}

watch(
  () => props.form,
  (val) => {
    if (!val) return

    payment.official_receipt = val.official_receipt ?? ''
    payment.permit_fee = val.permit_fee ?? 500
    payment.date_of_payment = val.date_of_payment
      ? val.date_of_payment.slice(0, 10)
      : ''
    payment.remarks = val.remarks ?? ''
  },
  { immediate: true, deep: true }
)

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

const openFileModal = (file: any) => {
  selectedFile.value = file;
  showModal.value = true;
};
const triggerUpdateFile = (file) => {
  selectedFileToUpdate.value = file;
  updateFileInput.value.click();
};
const getEmbedUrl = (url: string) => {
  if (!url) return '';
  return url.replace('/view', '/preview');
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
    <Fieldset legend="Payment of Application Fee">

      <div :class="{ 'pointer-events-none opacity-60': isProcessing }">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 mt-4">

          <!-- Application No -->
          <FloatLabel>
            <InputText v-model="props.form.application_no" disabled class="w-full font-bold" />
            <label>Application No.</label>
          </FloatLabel>

          <!-- OR Number -->
          <FloatLabel>
            <InputText v-model="payment.official_receipt" class="w-full" />
            <label>O.R No.</label>
          </FloatLabel>

          <!-- Fee -->
          <FloatLabel>
            <InputNumber v-model="payment.permit_fee" class="w-full" />
            <label>Permit Fee</label>
          </FloatLabel>

          <!-- Date of Payment -->
          <FloatLabel>
            <InputText v-model="payment.date_of_payment" type="date" class="w-full" />
            <label>Date of Payment</label>
          </FloatLabel>

          <!-- File Upload -->
          <div v-if="isEdit && showFiles.length > 0" class="md:col-span-2">
            <div class="container">
              <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <FileCard v-for="(file, index) in showFiles" :key="index" :file="file" @openPreview="openFileModal"
                  @updateFile="triggerUpdateFile" />
                  <input type="file" ref="updateFileInput" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleFileUpdate" />

              </div>
            </div>

          </div>
          <div v-else class="md:col-span-2">
            <label class="text-sm font-medium">
              Upload Official Receipt
            </label>

            <div class="relative mt-2 border rounded-md p-4 bg-gray-50 hover:bg-gray-100 transition">
              <span class="text-sm text-gray-600">
                {{ payment.or_copy ? payment.or_copy.name : 'Click to upload (.jpg, .jpeg, .pdf)' }}
              </span>

              <input type="file" accept=".jpg,.jpeg,.pdf"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="handleORFileUpload" />
            </div>
          </div>

        </div>
      </div>
    </Fieldset>

    <!-- Actions -->
    <div :class="[
      'w-full pt-6',
      currentStep > 1 ? 'grid grid-cols-2 gap-4' : 'flex justify-end'
    ]">
      <Button v-if="currentStep > 1" @click="$emit('back')" class="w-full bg-gray-300 hover:bg-gray-400">
        Back
      </Button>
      <Button
  v-if="currentStep > 1"
  type="button"
  @click="$emit('back')"
  class="w-full bg-gray-300 hover:bg-gray-400"
>
  Back
</Button>

      <Button :disabled="isProcessing"
        class="w-full bg-green-900 text-white transition-colors hover:bg-green-500 text-white" @click="submitStep">
        {{ isProcessing ? 'Saving...' : 'Save & Continue' }}
      </Button>
      <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
        <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500"
          allow="autoplay"></iframe>
      </Dialog>
    </div>
    <Dialog v-model:visible="isLoading" modal :closable="false" :draggable="false" :style="{ width: '300px' }">
      <div class="flex flex-col items-center gap-4 py-4">
        <span>Saving, please wait...</span>
        <ProgressBar mode="indeterminate" style="width: 100%; height: 6px" />
      </div>
    </Dialog>
  </div>
</template>