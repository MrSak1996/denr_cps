<script setup lang="ts">

/* -------------------------------------------------------
| IMPORTS
------------------------------------------------------- */
import axios from 'axios';
import { ref, computed, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { Info, Undo2 } from 'lucide-vue-next'
import AssessmentTable from '@/pages/applications/form_edit/assessment_tbl.vue';
import AssessmentModal from '@/pages/applications/modal/assessment_modal.vue';
import ReusableConfirmDialog from '@/pages/applications/modal/endorsed_modal.vue';
import Button from 'primevue/button';
import Fieldset from 'primevue/fieldset';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';

/* -------------------------------------------------------
| GLOBAL / PAGE CONTEXT
------------------------------------------------------- */
const page = usePage();
const toast = useToast();

const userId = page.props.auth?.user?.id;
const officeId = page.props.auth?.user?.office_id;
const roleId = page.props.auth?.user?.role_id;

/* -------------------------------------------------------
| EMITS & PROPS
------------------------------------------------------- */
const emit = defineEmits(['back', 'submit'])

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  currentStep: Number,
  application: Object,
  application_type: String,
  mode: String,
  supplier: Array,
  files: Array,
  routingHistory: Array
})

/* -------------------------------------------------------
| STATE
------------------------------------------------------- */
const onsite = ref({ findings: '', recommendations: '' });
const assessmentRows = ref([]);
const isLoading = ref(false);

const showModal = ref(false);
const selectedFile = ref<any>(null);

const isRoutingCollapsed = ref(true)
const isChainsawInfoCollapsed = ref(true)
const isCollapsed = ref(true)

const confirmDialogRef = ref<any>(null);

/* -------------------------------------------------------
| COMPUTED
------------------------------------------------------- */

// normalize application data
const applicationData = computed(() => props.application || {})

// supplier list
const suppliers = computed(() => props.supplier || [])

// routing history
const routingHistory = computed(() => props.routingHistory || [])

// check edit mode
const isEdit = computed(() => props.mode === 'edit')

// normalize files
const files = computed(() => {
  return (props.files || []).map((file: any) => ({
    name: file.file_name,
    url: file.file_url
  }))
})

// payment info
const payment = computed(() => applicationData.value?.payment || {})

// filter requirements depending on applicant type
const companyRequirements = computed(() => {
  return assessmentRows.value.filter(
    r => r.applicant_type === applicationData.value.application_type
  );
});

// check if any failed assessment exists
const hasFailed = computed(() =>
  companyRequirements.value.some(r => r.assessment === 'failed')
);

/* -------------------------------------------------------
| BASIC ACTIONS
------------------------------------------------------- */

// emit save event
const save = () => {
  emit('submit', {
    ...props.form,
    application_type: props.application_type
  })
}

// open file preview modal
const openFileModal = (file: any) => {
  selectedFile.value = file
  showModal.value = true
}

/* -------------------------------------------------------
| APPLICATION FLOW ACTIONS
------------------------------------------------------- */

// open return dialog and submit return request
const openReturnDialog = (id: number) => {
    const user_id = page.props.auth.user.id;
    const role_id = page.props.auth.user.role_id;

    confirmDialogRef.value?.open({
        header: 'Return Application?',
        message: 'Please indicate the reason and office to return this application.',
        showTextarea: false,  // user can add remarks
        showDropdown: false,  // optional: can be made dynamic later
        onConfirm: async (data?: { remarks?: string }) => {
            try {
                // Build payload for your Laravel return controller
                const payload = {
                    id: id,
                    user_id,
                    role_id,
                    assessments: companyRequirements.value.map(row => ({
                        permit_checklist_id: row.permit_checklist_id,
                        assessment: row.assessment,
                        remarks: row.remarks,
                    })),
                    onsite: {
                        findings: onsite.value.findings,
                        recommendations: onsite.value.recommendations,
                    },
                    extra_remarks: data?.remarks || null,
                };

                await axios.post(route('applications.rps.return'), payload);

                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Application returned successfully.',
                    life: 3000,
                });


            } catch (error: any) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.response?.data?.message || 'Something went wrong',
                    life: 5000,
                });
            }
        },
    });
};

const returnApplication = async () => {

    const incompleteRows = companyRequirements.value
        .map((row, index) => ({ index: index + 1, assessment: row.assessment }))
        .filter(r => !r.assessment);

    if (incompleteRows.length) {
        alert(`Incomplete assessment on row(s): ${incompleteRows.map(r => r.index).join(', ')}`);
        return;
    }


    await axios.post('/api/return', {
        id: props.form.id,
        user_id: userId,
        role_id: roleId,
        assessments: companyRequirements.value.map(row => ({
            permit_checklist_id: row.permit_checklist_id,
            assessment: row.assessment,
            remarks: row.remarks,
        })),
        onsite: {
            findings: onsite.value.findings,
            recommendations: onsite.value.recommendations
        }
    });

    toast.add({
        severity: 'success',
        summary: 'Application Returned',
        detail: 'Application has been returned successfully.',
        life: 5000,
    });

    setTimeout(() => {
        router.get(route('rps.chief.dashboard'));
    }, 2000);
};

// submit all assessments and handle workflow
const submitAllAssessments = async (applicationId) => {

  // validate required assessments
  if (![1, 4, 11, 12].includes(roleId)) {
    const incomplete = companyRequirements.value.some(row => !row.assessment);
    if (incomplete) {
      alert('Please complete all assessments before submitting.');
      return;
    }
  }

  const workflowType = roleId === 4 ? 'implementing_agency' : 'smooth';
  const isARDTSD = roleId === 11;
  const isEndorsingToRD = !hasFailed.value;

  try {
    await axios.post('/api/saveAssessment', {
      application_id: applicationId,
      userId,
      application_status: 4,
      toTSD: isEndorsingToRD,
      role_id: roleId,
      workflow_type: workflowType,
      office_id: officeId,
      assessments: companyRequirements.value.map(row => ({
        permit_checklist_id: row.permit_checklist_id,
        assessment: row.assessment,
        remarks: row.remarks
      })),
      onsite: {
        findings: onsite.value.findings,
        recommendations: onsite.value.recommendations
      }
    });

    // send email only for ARD/TSD
    if (isARDTSD) await sendEmail();

    // role-based redirect
    const redirectMap = {
      1: '/pending_application',
      2: '/rps-chief',
      3: '/cenro-dashboard',
      4: '/penro-technical-dashboard',
      5: '/penro-rps-chief-dashboard',
      6: '/penro-tsd-chief-dashboard',
      7: '/penro-dashboard',
      8: '/rts-dashboard',
      9: '/fus-dashboard',
      10: '/lpdd-chief-dashboard',
      11: '/ardts-dashboard',
      12: '/regional-executive-dashboard',
    };

    const redirectPath = redirectMap[roleId];
    if (redirectPath) {
      router.visit('/dashboard' + redirectPath);
    }

  } catch (error) {
    console.error(error);
  }
};

/* -------------------------------------------------------
| EMAIL
------------------------------------------------------- */

// send notification email
const sendEmail = async () => {
  try {
    const response = await axios.post('/api/send-email', {
      email: 'kimsacluti10101996@gmail.com',
      applicant_name: props.form.applicant_type === 'Individual'
        ? `${props.form.first_name} ${props.form.last_name}`
        : props.form.authorized_representative,
      address: props.form.applicant_type === 'Individual'
        ? props.form.i_complete_address
        : props.form.company_address,
      application_no: props.form.application_no
    });

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: response.data.message,
      life: 3000,
    });

    return response.data;

  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message,
      life: 3000,
    });
    throw error;
  }
};

/* -------------------------------------------------------
| ASSESSMENT HELPERS
------------------------------------------------------- */

// update assessment value
const updateAssessment = (checklist_entry_id, assessment) => {
  const row = companyRequirements.value.find(r => r.checklist_entry_id === checklist_entry_id);
  if (row) {
    row.assessment = assessment;
    row.is_saved = false;
  }
};

// update remarks
const updateRemarks = (checklist_entry_id, remarks) => {
  const row = companyRequirements.value.find(
    r => r.checklist_entry_id === checklist_entry_id
  );

  if (row) {
    row.remarks = remarks;
    row.is_saved = false;
  }
};

// update onsite findings/recommendations
const updateOnsite = ({ field, value }) => {
  onsite.value[field] = value;
};

/* -------------------------------------------------------
| FILE HELPERS
------------------------------------------------------- */

// convert google drive/view links to preview
const getEmbedUrl = (url: string) => {
  return url ? url.replace('/view', '/preview') : '';
};

// format date
const formatDate = (date: any) => {
  return date
    ? new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: '2-digit',
    })
    : '';
};

/* -------------------------------------------------------
| API CALLS
------------------------------------------------------- */

// fetch applicant checklist + attachments
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
          // permit_checklist_id: entry.chklist_id ?? null,
          application_type: entry.applicant_type, // normalize here
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


// upload resubmitted files
const handleResubmissionUpload = async (checklistId: number, files: File[]) => {
  try {
    isLoading.value = true;

    const formData = new FormData();
    files.forEach(file => formData.append('files[]', file));

    formData.append('uploaded_by', userId);
    formData.append('checklist_entry_id', checklistId.toString());
    formData.append('application_no', props.form.application_no);
    formData.append('application_id', props.form.id);

    const response = await axios.post('/api/resubmit-files', formData);

    const row = companyRequirements.value.find(r => r.checklist_entry_id === checklistId);
    if (row) row.resubmissions.push(...response.data.files);

  } catch (error) {
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};

// remove resubmitted file
const handleRemoveResubmission = (checklistId: number, index: number) => {
  const row = companyRequirements.value.find(r => r.checklist_entry_id === checklistId);
  if (!row) return;
  row.resubmissions.splice(index, 1);
};

/* -------------------------------------------------------
| LIFECYCLE
------------------------------------------------------- */

// initial load
onMounted(() => {
  getApplicantFile(props.form.application_id);
  console.log(companyRequirements)
});

</script>

<template>
  <div class="space-y-6">
    <Toast />
    <ReusableConfirmDialog ref="confirmDialogRef" />

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

    <AssessmentTable title="Applicant Requirements" :collapsed="isCollapsed.value"
      :application_status="props.form.status_title" :roleId="roleId" :rows="companyRequirements" :onsite="onsite"
      @view-file="openFileModal" @update-assessment="updateAssessment" @update-remarks="updateRemarks"
      @update-onsite="updateOnsite" @upload-resubmission="handleResubmissionUpload"
      @remove-resubmission="handleRemoveResubmission" />

    <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
      <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.file_url)" width="100%" height="500"
        allow="autoplay"></iframe>
    </Dialog>

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

    <div :class="[
      'pt-6 w-full',
      currentStep == 4
        ? 'grid grid-cols-2 gap-4'
        : 'flex justify-end'
    ]">
      <Button v-if="roleId === 1 || (props.form.status_title !== 'Draft' && currentStep === 4)" :disabled="roleId === 1"
        class="h-10 ml-auto px-4 py-2 flex items-center gap-2 rounded-md bg-red-700 text-white hover:bg-red-800"
        @click="returnApplication">
        <Undo2 />
        Return Application
      </Button>

      <Button v-else-if="props.form.application_status === 1 || [1, 2, 3].includes(currentStep)" variant="outline"
        @click="emit('back')" class="w-full bg-gray-300 hover:bg-gray-400">Back</Button>
      <AssessmentModal :status_id="props.form.application_status" class="w-full" :applicationId="Number(props.form.id)"
        @submit-assessments="submitAllAssessments" />

    </div>
  </div>
</template>