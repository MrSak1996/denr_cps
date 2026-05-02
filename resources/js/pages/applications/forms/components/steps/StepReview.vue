<script setup lang="ts">
import axios from 'axios';
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import Fieldset from 'primevue/fieldset'
import Dialog from 'primevue/dialog'
import FileCard from '../../file_card.vue'
import Tag from 'primevue/tag'
import AssessmentTable from '@/pages/applications/form_edit/assessment_tbl.vue';
import ConfirmModal from '../../../modal/confirmation_modal.vue';
const page = usePage();

const emit = defineEmits(['back', 'submit'])
const roleId = page.props.auth?.user?.role_id;
const onsite = ref({
  findings: '',
  recommendations: ''
});
const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  application: Object,
  application_type: String,
  mode: String,
  supplier: Array,
  files: Array,
  routingHistory: Array
})
const routingHistory = computed(() => props.routingHistory || []);
const isEdit = computed(() => props.mode === 'edit');
const isRoutingCollapsed = ref(true)
const isChainsawInfoCollapsed = ref(true)
const isCollapsed = ref(true)
const assessmentRows = ref([])

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

const companyRequirements = computed(() => {
  if (props.application_type === 'Individual') {
    return assessmentRows.value.filter(
      r => r.applicant_type === 'Individual'
    );
  } else {
    return assessmentRows.value.filter(
      r => r.applicant_type === 'Company'
    );
  }
});

const updateAssessment = (checklist_entry_id, assessment) => {
  const row = companyRequirements.value.find(
    r => r.checklist_entry_id === checklist_entry_id
  );
  if (row) {
    row.assessment = assessment;
    row.is_saved = false; // unlock save again if changed
  }
};

const updateRemarks = (checklist_entry_id, remarks) => {
  const row = companyRequirements.value.find(
    r => r.checklist_entry_id === checklist_entry_id
  );
  if (row) {
    row.remarks = remarks;
    row.is_saved = false;
  }
}; const updateOnsite = ({ field, value }) => {
  onsite.value[field] = value;
};

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

const getDateField = (item) => {
  if (item.route_order == 2) return item.date_received_rps_chief;

  if (item.route_order == 4 && item.action == 'Submitted to CHIEF RPS')
    return item.date_endorsed_chiefrps;

  if (item.route_order == 4 && item.action == 'Received by the CENRO Officer')
    return item.date_cenro_chief_received;

  if (item.route_order == 6) return item.date_received_penro_technical;
  if (item.route_order == 8) return item.date_received_penro_rps_chief;
  if (item.route_order == 10) return item.date_received_penro_tsd_chief;
  if (item.route_order == 12) return item.date_received_penro_chief;
  if (item.route_order == 14) return item.date_received_region_technical;
  if (item.route_order == 16) return item.date_received_fus_chief;
  if (item.route_order == 18) return item.date_received_lpddchief;
  if (item.route_order == 20) return item.date_received_ardts;
  if (item.route_order == 22) return item.date_received_red;

  return null;
};

const getEndorsedDate = (item) => {
  if (item.route_order == 1) return item.date_endorsed_chiefrps;

  if (item.route_order == 3 && item.action != 'Returned to Technical Staff')
    return item.date_endorsed_cenro_chief;

  if (item.route_order == 5 && item.action === 'Submitted to PENRO Technical Staff')
    return item.date_endorsed_penro_technical;

  if (item.route_order == 7) return item.date_endorsed_penro_chief_rps;
  if (item.route_order == 9) return item.date_endorsed_penro_chief_tsd;
  if (item.route_order == 11) return item.date_endorsed_penro;
  if (item.route_order == 13) return item.date_endorsed_region_technical;
  if (item.route_order == 15) return item.date_endorsed_fus_chief;
  if (item.route_order == 17) return item.date_endorsed_lpddchief;
  if (item.route_order == 19) return item.date_endorsed_ardts;
  if (item.route_order == 21) return item.date_endorse_red;

  return null;
};
const getApplicantFile = async (application_id) => {
  try {
    const checklistRes = await axios.get(
      `https://cps.denrcalabarzon.com/api/getChecklistEntries/${application_id}`
    );

    const attachmentsRes = await axios.get(
      `https://cps.denrcalabarzon.com/api/getApplicantFile/${application_id}`
    );

    if (checklistRes.data.status && attachmentsRes.data.status) {
      const checklistEntries = checklistRes.data.data;
      const attachments = attachmentsRes.data.data;
      const attachmentsMap = attachments.reduce((acc, file) => {
        const id = file.checklist_entry_id;

        if (!acc[id]) {
          acc[id] = {
            original: null,
            resubmissions: []
          };
        }

        if (file.file_name) {
          if (/_v\d+\./i.test(file.file_name)) {
            // ✅ resubmitted file
            acc[id].resubmissions.push(file);
          } else {
            // ✅ original file
            acc[id].original = file;
          }
        }

        return acc;
      }, {});
      assessmentRows.value = checklistEntries.map(entry => {
        const entryAttachments = attachmentsMap[entry.checklist_entry_id] || [];

        const files = attachmentsMap[entry.checklist_entry_id] || {
          original: null,
          resubmissions: []
        };

        return {
          ...entry,
          application_type: entry.applicant_type, // normalize here
          // permit_checklist_id: entry.chklist_id ?? null,

          permit_checklist_id: entry.permit_checklist_id ?? null,
          original_file: files.original,
          attachments: files.original ? [files.original] : [], // for your existing VIEW button
          resubmissions: files.resubmissions.sort(
            (a, b) => new Date(a.created_at) - new Date(b.created_at)
          ),
          requirement: entry.requirement || 'N/A',
          assessment: entry.assessment ?? null,
          is_saved: Boolean(entry.assessment)
        };
      });


    }
  } catch (err) {
    console.error('Error loading applicant data:', err);
  }
};

const handleResubmissionUpload = async (checklistId: number, files: File[]) => {
  try {
    isLoading.value = true; // ✅ SHOW LOADING OVERLAY

    const formData = new FormData();
    files.forEach(file => formData.append('files[]', file));
    formData.append('uploaded_by', userId);
    formData.append('checklist_entry_id', checklistId.toString());
    formData.append('application_no', company_form.application_no);
    formData.append('application_id', page.props.application.id);

    // Example API endpoint
    const response = await axios.post('/api/resubmit-files', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    // Assume response returns the uploaded files with timestamps
    const uploadedFiles = response.data.files; // [{file_name, uploaded_at}, ...]

    // Find the row and push new resubmissions
    const row = companyRequirements.value.find(r => r.checklist_entry_id === checklistId);
    if (row) {
      row.resubmissions.push(...uploadedFiles);
    }
  } catch (error) {
    console.error(error);
  } finally {
    isLoading.value = false; // ✅ HIDE LOADING OVERLAY
  }
};

const handleRemoveResubmission = (checklistId: number, index: number) => {
  const row = companyRequirements.find(r => r.checklist_entry_id === checklistId);
  if (!row || !row.resubmissions[index]) return;
  row.resubmissions.splice(index, 1);
};

onMounted(async () => {
await getApplicantFile(props.form.application_id);
});
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
    <Fieldset legend="Routing History" toggleable v-model:collapsed="isRoutingCollapsed">
      <table class="min-w-full rounded-lg border border-gray-300 bg-white text-[12px]">
        <thead class="bg-gray-100">
          <tr>
            <th class="border px-4 py-2 text-left">#</th>
            <th class="border px-4 py-2 text-left">Sender</th>
            <th class="border px-4 py-2 text-left">Route Details</th>
            <th class="border px-4 py-2 text-left">Receiver</th>
            <th class="border px-4 py-2 text-left">Date Returned</th>
            <th class="border px-4 py-2 text-left">Date Received</th>
            <th class="border px-4 py-2 text-left">Date Endorsed</th>
            <th class="border px-4 py-2 text-left">Remarks</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="(item, index) in routingHistory" :key="index" class="hover:bg-gray-50">
            <!-- # -->
            <td class="border px-4">
              {{ index + 1 }}
            </td>

            <!-- Sender -->
            <td class="border px-4" style="width: 10rem">
              <div v-if="[2, 4, 6, 8, 10].includes(item.route_order)"></div>

              <div v-else>
                <b>{{ item.sender_role }}</b><br />
                <i>{{ item.sender }}</i>
              </div>
            </td>

            <!-- Route details -->
            <td class="border px-4" style="width: 7rem">
              <b>Route No. 2026-00{{ item.route_order }}</b>
            </td>

            <!-- Receiver -->
            <td class="border px-4" style="width: 20rem">
              <b>{{ item.receiver_role }}</b><br />

              <Tag v-if="item.action === 'Received'" severity="danger" size="small"> Received
              </Tag>

              <Tag v-else-if="item.action === 'Endorsed'" severity="info" size="small"> Endorsed
              </Tag>

              <Tag
                v-else-if="item.action == 'Returned to Technical Staff' || item.action == 'Returned to PENRO Technical Staff'"
                severity="danger" size="small">
                {{ item.action }}

              </Tag>
              <Tag v-else severity="success" size="small">
                {{ item.action }}

              </Tag>



              <br />
            </td>
            <!-- Date Retured -->
            <td class="birder px-4">
              <span
                v-if="item.action == 'Returned to Technical Staff' || item.action === 'Returned to PENRO Technical Staff'">
                {{
                  new Date(item.updated_at).toLocaleString('en-PH', {
                    year: 'numeric',
                    month: 'long',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true,
                  })

                }}
              </span>
            </td>

            <!-- Date Received -->
            <td class="border px-4">
              <span>
                {{ formatDate(getDateField(item)) }}
              </span>

            </td>

            <!-- Date Endorsed -->
            <td class="border px-4">

              <span>
                {{ formatDate(getEndorsedDate(item)) }}
              </span>


            </td>

            <!-- Remarks -->
            <td class="border px-4">
              {{ item.remarks ?? '-' }}
            </td>
          </tr>

          <!-- Empty state -->
          <tr v-if="routingHistory.length === 0">
            <td colspan="5" class="p-4 text-center text-gray-500">No routing history found</td>
          </tr>
        </tbody>
      </table>
    </Fieldset>
    <Fieldset legend="Chainsaw Information" toggleable v-model:collapsed="isChainsawInfoCollapsed">
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

    <AssessmentTable v-if="props.form.application_type === 'Company'" title="Company Applicant Requirements"
      :collapsed="isCollapsed.value" :application_status="props.form.status_title" :roleId="roleId"
      :rows="companyRequirements" :onsite="onsite" @view-file="openFileModal" @update-assessment="updateAssessment"
      @update-remarks="updateRemarks" @update-onsite="updateOnsite" @upload-resubmission="handleResubmissionUpload"
      @remove-resubmission="handleRemoveResubmission" />

    <!-- <Fieldset legend="Uploaded Files">
      <div class="container">
        <div class="file-list">
          <FileCard v-for="(file, index) in files" :key="index" :file="file" @openPreview="openFileModal" />
        </div>
      </div>

      <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
        <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500"
          allow="autoplay"></iframe>
      </Dialog>
    </Fieldset> -->

    <div class="grid grid-cols-2 gap-4 mt-2">
      <Button variant="outline" @click="emit('back')" class="w-full bg-gray-300 hover:bg-gray-400">Back</Button>

      <ConfirmModal v-if="isEdit" class="w-full" :applicationId="Number(props.form.application_id)" :role_id="roleId" />
    </div>
  </div>
</template>