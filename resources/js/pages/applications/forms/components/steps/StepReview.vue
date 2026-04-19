<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import Fieldset from 'primevue/fieldset'
import Dialog from 'primevue/dialog'
import FileCard from '../../file_card.vue'

const emit = defineEmits(['back', 'submit'])

const props = defineProps({
  application: Object
})

/* 🔥 DATA */
const applicationData = props.application

const suppliers = computed(() => applicationData?.suppliers || [])

/* 🔥 FILES FROM GOOGLE DRIVE */
const files = computed(() => {
  if (!applicationData?.google_drive) return []

  return Object.values(applicationData.google_drive).map((file: any) => ({
    name: file.file_name,
    url: file.file_url
  }))
})

/* Preview */
const showModal = ref(false)
const selectedFile = ref<any>(null)

const openFileModal = (file: any) => {
  selectedFile.value = file
  showModal.value = true
}

const getEmbedUrl = (url: string) => {
  return url?.replace('/view', '/preview')
}

const formatDate = (date: any) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-xl font-semibold">Review & Submit</h2>

    <!-- Applicant -->
    <Fieldset legend="Applicant Details">
      <div class="grid md:grid-cols-2 gap-4 text-sm">
        <div><b>Application No:</b> {{ applicationData?.application_id }}</div>
        <div><b>Applicant:</b> {{ applicationData?.first_name }} {{ applicationData?.last_name }}</div>
      </div>
    </Fieldset>

    <!-- Suppliers -->
    <Fieldset legend="Chainsaw Info">
      <div v-for="(s, i) in suppliers" :key="i" class="mb-4 border p-3">
        <div><b>Supplier:</b> {{ s.supplier_name }}</div>
        <div><b>Permit:</b> {{ s.permit_to_sell_no }}</div>
      </div>
    </Fieldset>

    <!-- Payment -->
    <Fieldset legend="Payment">
      <div><b>OR:</b> {{ applicationData?.payment?.official_receipt }}</div>
      <div><b>Fee:</b> ₱{{ applicationData?.payment?.permit_fee }}</div>
    </Fieldset>

    <!-- Files -->
    <Fieldset legend="Uploaded Files">
      <FileCard
        v-for="(file, i) in files"
        :key="i"
        :file="file"
        @openPreview="openFileModal"
      />

      <Dialog v-model:visible="showModal" modal header="Preview">
        <iframe
          v-if="selectedFile"
          :src="getEmbedUrl(selectedFile.url)"
          width="100%"
          height="500"
        />
      </Dialog>
    </Fieldset>

    <div class="grid grid-cols-2 gap-4">
      <Button @click="emit('back')">Back</Button>
      <Button @click="emit('submit')" class="bg-green-700 text-white">
        Submit
      </Button>
    </div>
  </div>
</template>