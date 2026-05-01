<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import Fieldset from 'primevue/fieldset'
import Dialog from 'primevue/dialog'
import FileCard from '../../file_card.vue'
import Tag from 'primevue/tag'

import ConfirmModal from '../../../modal/confirmation_modal.vue';
const page = usePage();

const emit = defineEmits(['back', 'submit'])
const roleId = page.props.auth?.user?.role_id;

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  application: Object,
  application_type: String,
  mode: String,
  supplier: Array,
  files: Array
})
const isEdit = computed(() => props.mode === 'edit');

const save = () => {
  emit('submit', {
    ...props.form,
    application_type: props.application_type
  })
}

const applicationData = computed(() => props.application || {})

const suppliers = computed(() => props.supplier || [])

const files = computed(() => {
  return (props.files || []).map((file: any) => ({
    name: file.file_name,
    url: file.file_url
  }))
})

const payment = computed(() =>
  applicationData.value?.payment || {}
)

const showModal = ref(false)

const selectedFile = ref<any>(null)

const openFileModal = (file: any) => {
  selectedFile.value = file
  showModal.value = true
}

const getEmbedUrl = (url: string) => {
  if (!url) return ''
  return url.replace('/view', '/preview')
}

const formatDate = (date: any) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: '2-digit',
  })
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

    <Fieldset legend="Applicant Details" :toggleable="true">
      <!-- Applicant Info (non-file fields) -->

      <div class="relative">
        <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
          <div class="flex">
            <span class="w-48 font-semibold">Application No:</span>
            <Tag :value="applicationData?.application_no" severity="success" />
          </div>
          <div class="flex">
            <span class="w-48 font-semibold">Date Applied:</span>
            <span>{{ formatDate(applicationData.date_applied) }}</span>
          </div>
          <div class="flex">
            <span class="w-48 font-semibold">Type of Transaction:</span>
            <span>{{ applicationData.type_of_transaction }}</span>
          </div>
          <div class="flex">
            <span class="w-48 font-semibold">Classification:</span>
            <span>{{ applicationData.classification }}</span>
          </div>
          <!-- COMPANY -->
          <div v-if="applicationData.application_type === 'Company'">
            <div class="flex">
              <span class="w-48 font-semibold">Company Name:</span>
              <span>{{ applicationData.company_name }}</span>
            </div>

            <div class="flex">
              <span class="w-48 font-semibold">Authorized Representative:</span>
              <span>{{ applicationData.authorized_representative }}</span>
            </div>
          </div>

          <!-- INDIVIDUAL -->
          <div v-else>
            <div class="flex">
              <span class="w-48 font-semibold">Applicant Name:</span>
              <span>
                {{ applicationData.first_name }}
                {{ applicationData.middle_name }}
                {{ applicationData.last_name }}
              </span>
            </div>
          </div>

          <div class="flex">
            <span class="w-48 font-semibold">Contact Details:</span>
            <span>{{ applicationData.mobile_no }}</span>
          </div>
          <div class="flex">
            <span class="w-48 font-semibold">Region:</span>
            <span>REGION IV-A (CALABARZON)</span>
          </div>

          <div class="flex">
            <span class="w-48 font-semibold">Complete Address:</span>
            <span>{{ applicationData.applicant_complete_address }}</span>
          </div>

        </div>
      </div>
    </Fieldset>

    <Fieldset legend="Chainsaw Information" :toggleable="true">
      <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
        <div class="md:col-span-2">
          <table class="w-full border border-gray-300 text-sm">
            <tbody>
              <tr class="border-b">
                <td class="w-56 bg-gray-50 p-2 font-semibold">Supplier Name</td>
                <td class="p-2">
                  <ul class="ml-4 list-disc">
                    <li v-for="(supplier, i) in suppliers" :key="i">
                      {{ supplier.supplier_name }}
                    </li>
                  </ul>
                </td>
              </tr>

              <tr class="border-b">
                <td class="bg-gray-50 p-2 font-semibold">Purpose of Purchase</td>
                <td class="p-2">
                  <ul class="ml-4 list-disc">
                    <li v-for="(supplier, i) in suppliers" :key="i">
                      {{ supplier.purpose }}
                    </li>
                  </ul>
                </td>
              </tr>

              <tr class="border-b">
                <td class="bg-gray-50 p-2 font-semibold">Other Details</td>
                <td class="p-2">
                  <ul class="ml-4 list-disc">
                    <li v-for="(supplier, i) in suppliers" :key="i" class="mb-2">
                      Covered by Permit to Sell
                      <b>{{ supplier.permit_to_sell_no }}</b>
                      issued on {{ formatDate(supplier.issued_date) }}, valid
                      until
                      {{ formatDate(supplier.valid_until) }} approved/issued
                      by {{ supplier.issued_by }}
                    </li>
                  </ul>
                </td>
              </tr>

              <tr class="border-b">
                <td class="bg-gray-50 p-2 font-semibold">Official Receipt</td>
                <td class="p-2">
                  <Tag :value="applicationData.official_receipt" severity="success" />
                </td>
              </tr>

              <tr>
                <td class="bg-gray-50 p-2 font-semibold">Permit Fee</td>
                <td class="p-2">₱ {{ applicationData.permit_fee }}.00</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ✅ Brands & Models -->
        <div class="md:col-span-2">
          <span class="mb-2 block font-semibold">Chainsaw Details:</span>

          <!-- SUPPLIERS -->
          <div v-for="(supplier, sIndex) in suppliers" :key="sIndex" class="mb-6 rounded-lg border bg-gray-100 p-4">
            <!-- Supplier Info -->
            <div class="mb-3 text-sm">
              <div><span class="font-semibold">Supplier:</span> {{
                supplier.supplier_name }}</div>
              <div><span class="font-semibold">Permit To Sell:</span> {{
                supplier.permit_to_sell_no }}</div>
            </div>

            <!-- BRANDS -->
            <div v-for="(brand, bIndex) in supplier.brands" :key="bIndex" class="mb-4 rounded-lg border bg-gray-50 p-4">
              <div class="mb-2">
                <span class="font-semibold">Brand:</span>
                <span class="ml-2">{{ brand.name }}</span>
              </div>

              <!-- MODELS TABLE -->
              <table class="w-full border text-sm">
                <thead class="bg-blue-900 text-white">
                  <tr>
                    <th class="px-3 py-2 text-left">Model</th>
                    <th class="px-3 py-2 text-left">Serial No</th>
                    <th class="w-32 px-3 py-2 text-center">No. of Units</th>
                    <th class="px-3 py-2 text-left">Date of Issuances</th>
                    <th class="px-3 py-2 text-left">Date of Expiry</th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="(model, mIndex) in brand.models" :key="mIndex">
                    <td>{{ model.model }}</td>
                    <td>{{ model.serial_no }}</td>
                    <td>{{ model.quantity }}</td>
                    <td>{{ formatDate(supplier.issued_date) }}</td>
                    <td>{{ formatDate(supplier.valid_until) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </Fieldset>

    <Fieldset legend="Uploaded Files" :toggleable="true">
      <div class="container">
        <div class="file-list">
          <FileCard v-for="(file, index) in files" :key="index" :file="file" @openPreview="openFileModal" />
        </div>
      </div>

      <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
        <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500"
          allow="autoplay"></iframe>
      </Dialog>
    </Fieldset>

    <div class="grid grid-cols-2 gap-4">
      <Button variant="outline" @click="emit('back')">Back</Button>

      <Button class="w-full bg-green-900 text-white transition-colors hover:bg-green-500 text-white" @click="save">
        Save & Continue
      </Button>
      <ConfirmModal v-if="isEdit" class="w-full" :applicationId="Number(props.form.application_id)" :role_id="roleId" />
    </div>
  </div>
</template>