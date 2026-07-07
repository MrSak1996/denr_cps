<script setup lang="ts">

/* -------------------------------------------------------
| IMPORTS
------------------------------------------------------- */
import axios from 'axios';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
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

const isMobile = ref(window.innerWidth < 768)

const handleResize = () => {
  isMobile.value = window.innerWidth < 768
}

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

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
const isRegistrationInfoCollapsed = ref(true);
const isChainsawInfoCollapsed = ref(true)
const isCollapsed = ref(true)

const confirmDialogRef = ref<any>(null);

/* -------------------------------------------------------
| COMPUTED
------------------------------------------------------- */

// normalize application data
const applicationData = computed(() => props.application || {})

const overallRemarks = ref('');

// Populate when application changes
watch(
  applicationData,
  (val) => {
    overallRemarks.value = val.findings || '';
  },
  { immediate: true }
);

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
// const companyRequirements = computed(() => {
//   return assessmentRows.value.filter(
//     r => r.applicant_type === applicationData.value.application_type
//   );
// });

const companyRequirements = computed(() => {

  const orders = {
    Individual: [28, 4, 3, 19, 18, 20],

    Company: [29, 10, 8, 9, 31, 11, 14, 12],

    Government: [30, 24, 23, 26, 22, 21]
  };

  const applicationType =
    applicationData.value.application_type;

  const order =
    orders[applicationType] || [];

  return assessmentRows.value
    .filter(
      row => row.applicant_type === applicationType
    )
    .sort((a, b) => {
      const indexA = order.indexOf(a.permit_checklist_id);
      const indexB = order.indexOf(b.permit_checklist_id);

      return (
        (indexA === -1 ? 999 : indexA) -
        (indexB === -1 ? 999 : indexB)
      );
    });
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


const returnApplication = async () => {

  const incompleteRows = companyRequirements.value
    .map((row, index) => ({
      index: index + 1,
      assessment: row.assessment
    }))
    .filter(r => !r.assessment);

  if (incompleteRows.length) {

    toast.add({
      severity: 'warn',
      summary: 'Incomplete Assessment',
      detail: `Incomplete assessment on row(s): ${incompleteRows.map(r => r.index).join(', ')}`,
      life: 5000,
    });

    return;
  }

  try {

    await axios.post('/api/return', {
      id: props.form.id,
      user_id: userId,
      role_id: roleId,
      overall_remarks: overallRemarks.value,
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

    const redirectMap = {
      1: 'applications/pending_application',
      2: '/dashboard/rps-chief',
      3: '/dashboard/cenro-dashboard',
      4: '/dashboard/penro-technical',
      5: '/dashboard/penro-rps-chief',
      6: '/dashboard/penro-tsd-chief',
      7: '/dashboard/penro',
      8: '/dashboard/rts',
      9: '/dashboard/fus',
      10: '/dashboard/lpdd-chief',
      11: '/dashboard/ardts',
      12: '/dashboard/regional-executive',
    };

    const redirectPath = redirectMap[roleId];

    if (redirectPath) {

      setTimeout(() => {
        router.visit(redirectPath);
      }, 2000);

    }

  } catch (error: any) {

    console.error(error);

    toast.add({
      severity: 'error',
      summary: 'Return Failed',
      detail:
        error?.response?.data?.message ||
        'Something went wrong.',
      life: 5000,
    });

  }
};

// submit all assessments and handle workflow
const submitAllAssessments = async (applicationId) => {

  // validate required assessments
  if (![1, 4, 11, 12].includes(roleId)) {
    const incomplete = companyRequirements.value.some(row => !row.assessment);
    if (incomplete) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Please complete all assessments before submitting.',
        life: 3000,
      });

      return;
    }
  }



  const workflowType = roleId === 4 ? 'implementing_agency' : 'smooth';

  const isEndorsingToRD = !hasFailed.value;

  try {
    await axios.post('/api/saveAssessment', {
      application_id: applicationId,
      user_id: userId,
      application_status: 4,
      toTSD: isEndorsingToRD,
      role_id: roleId,
      workflow_type: workflowType,
      office_id: officeId,
      overall_remarks: overallRemarks.value,


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
    const emailRoutingMap = {
      10: 'oardts.r4a@denr.gov.ph', // LPDD Chief → ARD/TSD
      11: 'hadornado@gmail.com',   // ARD/TSD
    };

    const recipientEmail = emailRoutingMap[roleId];

    if (recipientEmail) {
      await sendEmail(recipientEmail, roleId);
    }


    // role-based redirect
    const redirectMap = {
      1: '/applications/pending_application',
      2: '/dashboard/rps-chief',
      3: '/dashboard/cenro',
      4: '/dashboard/penro-technical',
      5: '/dashboard/penro-rps-chief',
      6: '/dashboard/penro-tsd-chief',
      7: '/dashboard/penro',
      8: '/dashboard/rts',
      9: '/dashboard/fus',
      10: '/dashboard/lpdd-chief',
      11: '/dashboard/ardts',
      12: '/dashboard/regional-executive',
    };

    const redirectPath = redirectMap[roleId];
    if (redirectPath) {
      router.visit(redirectPath);
    }

  } catch (error) {
    console.error(error);
  }
};

/* -------------------------------------------------------
| EMAIL
------------------------------------------------------- */

// send notification email
const sendEmail = async (recipientEmail, roleId) => {
  try {
    const response = await axios.post('/api/send-email', {
      email: recipientEmail, // or the actual recipient
      applicant_name:
        props.form.applicant_type === 'Individual'
          ? `${props.form.first_name || ''} ${props.form.last_name || ''}`.trim()
          : props.form.authorized_representative || 'N/A',
      address:
        props.form.applicant_type === 'Individual'
          ? props.form.i_complete_address
          : props.form.company_address,
      application_no: props.form.application_no,
      role_id: roleId
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
      detail:
        error.response?.data?.message ||
        'Unable to send email.',
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


      const requirementMap: Record<number, string> = {
        28: 'Duly accomplished Application Form and/or Letter of Intent',
        4: 'Copy of Permit to Sell/Re-Sell Chainsaw',
        3: 'Official Receipt of Permit Fee',
        19: 'Duly signed and notarized affidavit (purpose of chainsaw purchase)',
        18: 'Authorization of representative/requesting person (if applicable)',
        20: 'Other Supporting Documents',

        29: 'Duly accomplished Application Form and/or Letter of Intent',
        10: 'Copy of Permit to Sell/Re-Sell Chainsaw',
        8: 'Official Receipt of Permit Fee',
        9: 'Certificate of Registration of Business Name from DTI or SEC',
        31: 'Business License/Mayors Permit',
        11: 'Authorization/Secretarys Certificate',
        14: 'Duly signed and notarized affidavit (purpose of chainsaw purchase)',
        12: 'Other Supporting Documents',

        30: 'Duly accomplished Application Form and/or Letter of Intent',
        24: 'Copy of Permit to Sell/Re-Sell Chainsaw',
        23: 'Official Receipt of Permit Fee',
        26: 'Authorization of representative/requesting person (if applicable)',
        22: 'Duly signed and notarized affidavit (purpose of chainsaw purchase)',
        21: 'Other Supporting Documents'

      };

      assessmentRows.value = checklistEntries.map(entry => {
        const files = attachmentsMap[entry.checklist_entry_id] || {
          original: null,
          resubmissions: []
        };

        return {
          ...entry,
          application_type: entry.applicant_type,
          permit_checklist_id: entry.permit_checklist_id ?? null,
          original_file: files.original,
          attachments: files.original ? [files.original] : [],
          resubmissions: files.resubmissions.sort(
            (a, b) => new Date(a.created_at) - new Date(b.created_at)
          ),
          requirement:
            requirementMap[entry.permit_checklist_id] ??
            entry.requirement ??
            'N/A',
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
    formData.append('application_type', props.form.application_type);
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

const hasFailedRequirements = computed(() => {
  return companyRequirements.some((row: any) => row.assessment === 'failed')
})

// remove resubmitted file
const handleRemoveResubmission = (checklistId: number, index: number) => {
  const row = companyRequirements.value.find(r => r.checklist_entry_id === checklistId);
  if (!row) return;
  row.resubmissions.splice(index, 1);
};
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
/* -------------------------------------------------------
| LIFECYCLE
------------------------------------------------------- */

// initial load
onMounted(() => {
  getApplicantFile(props.form.application_id);
  window.addEventListener('resize', handleResize)

});

</script>

<template>
  <div v-if="!isMobile">

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

      <Fieldset legend="Applicant Details" :toggleable="applicationData.application_status != 25">
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
                <span class="w-64 font-semibold">Authorized Representative:</span>
                <span>{{ applicationData.authorized_representative }}</span>
              </div>
            </div>
            <div v-else-if="applicationData.application_type === 'Government'">
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

            <div class="flex" v-if="applicationData.application_type === 'Company'">
              <span class="w-48 font-semibold">Complete Address:</span>
              <span>{{ applicationData.company_address }}</span>
            </div>
            <div class="flex" v-else-if="applicationData.application_type === 'Government'">
              <span class="w-48 font-semibold">Complete Address:</span>
              <span>{{ applicationData.company_address }}</span>
            </div>
            <div class="flex" v-else>
              <span class="w-48 font-semibold">Complete Address:</span>
              <span>{{ applicationData.i_complete_address }}</span>
            </div>

          </div>
        </div>
      </Fieldset>

      <Fieldset legend="Registration Information" toggleable v-model:collapsed="isRegistrationInfoCollapsed">
        <table class="w-full border border-gray-300 text-sm">
          <thead class="bg-blue-900 text-white">
            <tr>
              <th class="px-3 py-2 text-left">Field</th>
              <th class="px-3 py-2 text-left">Details</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-t border-gray-300">
              <td class="px-3 py-2 font-semibold">Encoded By</td>
              <td class="px-3 py-2">
                <Tag severity="success">{{ props.form.registered_by }}</Tag><br />
                {{ props.form.office_title }} - {{ props.form.role_title }}
              </td>
            </tr>
            <tr class="border-t border-gray-300">
              <td class="px-3 py-2 font-semibold">Registered Date & Time</td>
              <td class="px-3 py-2">{{ props.form.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </Fieldset>

      <Fieldset legend="Routing History" toggleable v-model:collapsed="isRoutingCollapsed">
        <table class="min-w-full rounded-lg border border-gray-300 bg-white text-[12px]">
          <thead class="bg-gray-100">
            <tr>
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
                <span
                  v-if="[1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31, 33, 35, 37, 39, 41, 43, 45, 47, 49, 51].includes(item.route_order)">
                  {{
                    item.created_at
                      ? new Date(item.created_at).toLocaleString('en-PH', {
                        year: 'numeric',
                        month: 'long',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true,
                      })
                      : '-'
                  }}
                </span>

              </td>

              <!-- Date Endorsed -->
              <td class="border px-4">

                <span
                  v-if="[2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36, 38, 40, 42, 44, 46, 48, 50].includes(item.route_order)">
                  {{
                    item.updated_at
                      ? new Date(item.updated_at).toLocaleString('en-PH', {
                        year: 'numeric',
                        month: 'long',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true,
                      })
                      : '-'
                  }}
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
                <!-- <tr class="border-b">
                <td class="w-56 bg-gray-50 p-2 font-semibold">Supplier Name</td>
                <td class="p-2">
                  <ul class="ml-4 list-disc">
                    <li v-for="(supplier, i) in suppliers" :key="i">
                      {{ supplier.supplier_name }}
                    </li>
                  </ul>
                </td>
              </tr> -->

                <tr>
                  <td class="bg-gray-50 p-2 font-semibold">
                    Purpose of Purchase
                  </td>

                  <td class="p-2">
                    {{ suppliers[0].purpose }}
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
              </tbody>
            </table>
          </div>

          <div class="md:col-span-2">
            <table class="w-full border border-gray-300 text-sm">
              <tbody>

                <tr class="border-b">
                  <td class="bg-gray-50 p-2 font-semibold">Official Receipt No.</td>
                  <td class="p-2">
                    <Tag :value="applicationData.official_receipt" severity="success" />
                  </td>
                </tr>

                <tr>
                  <td class="bg-gray-50 p-2 font-semibold">Permit Fee</td>
                  <td class="p-2">₱ {{ applicationData.permit_fee }}</td>
                </tr>


                <tr>
                  <td class="bg-gray-50 p-2 font-semibold">Date of Payment</td>
                  <td class="p-2">
                    <b>{{ formatDate(applicationData.date_of_payment) }}</b>
                  </td>
                </tr>


              </tbody>
            </table>
          </div>

          <!-- ✅ Brands & Models -->
          <div class="md:col-span-2">

            <h3 class="mb-4 text-lg font-semibold">
              Supplier Information
            </h3>

            <div v-for="(supplier, sIndex) in suppliers" :key="sIndex" class="mb-6 rounded-lg border shadow-sm">

              <!-- Header -->

              <div class="bg-blue-900 text-white px-4 py-2 font-semibold">

                Supplier {{ sIndex + 1 }}

              </div>

              <!-- Supplier Details -->

              <div class="grid grid-cols-2 gap-3 p-4 text-sm">

                <div>

                  <b>Supplier Name</b><br>

                  {{ supplier.supplier_name }}

                </div>

                <div>

                  <b>Supplier Address</b><br>

                  {{ supplier.supplier_address }}

                </div>

                <div>

                  <b>Permit To Sell No.</b><br>

                  {{ supplier.permit_to_sell_no }}

                </div>

                <div>

                  <b>Issued By</b><br>

                  {{ supplier.issued_by }}

                </div>

                <div>

                  <b>Issued Date</b><br>

                  {{ formatDate(supplier.issued_date) }}

                </div>

                <div>

                  <b>Valid Until</b><br>

                  {{ formatDate(supplier.permit_validity || supplier.valid_until) }}

                </div>

              </div>

              <!-- Chainsaw Details -->

              <div class="px-4 pb-4">

                <table class="w-full border text-sm">

                  <thead class="bg-gray-100">

                    <tr>

                      <th class="border p-2">
                        Brand
                      </th>

                      <th class="border p-2">
                        Model
                      </th>

                      <th class="border p-2">
                        Quantity
                      </th>

                    </tr>

                  </thead>

                  <tbody style="text-align: center;">

                    <tr v-for="(row, index) in supplier.rows" :key="index">

                      <td class="border p-2">
                        {{ row.brand_name }}
                      </td>

                      <td class="border p-2">
                        {{ row.model || row.model_name }}
                      </td>

                      <td class="border p-2 text-center">
                        {{ row.quantity }}
                      </td>

                    </tr>

                  </tbody>

                </table>

              </div>

            </div>

          </div>
        </div>
      </Fieldset>

      <AssessmentTable title="Applicant Requirements" :collapsed="applicationData.application_status === 25"
        :application_status="props.form.status_title" :roleId="roleId" :rows="companyRequirements" :onsite="onsite"
        @view-file="openFileModal" @update-assessment="updateAssessment" @update-remarks="updateRemarks"
        @update-onsite="updateOnsite" @upload-resubmission="handleResubmissionUpload"
        @remove-resubmission="handleRemoveResubmission" :overallRemarks="overallRemarks"
        @update-overall-remarks="overallRemarks = $event" />

      <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
        <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.file_url)" width="100%" height="500"
          allow="autoplay"></iframe>
      </Dialog>

      <div :class="[
        'pt-6 w-full',
        currentStep == 4
          ? 'grid grid-cols-2 gap-4'
          : 'flex justify-end'
      ]">
        <Button v-if="roleId === 1 || (props.form.status_title !== 'Draft' && currentStep === 4)"
          :disabled="roleId === 1"
          class="h-10 ml-auto px-4 py-2 flex items-center gap-2 rounded-md bg-red-700 text-white hover:bg-red-800"
          @click="returnApplication">
          <Undo2 />
          Return Application
        </Button>

        <!-- <Button v-else-if="props.form.application_status === 1 || [1, 2, 3].includes(currentStep)" variant="outline" -->
        <Button v-else-if="![1, 25, 26, 27].includes(props.form.application_status)" variant="outline"
          @click="emit('back')" class="w-full bg-gray-300 hover:bg-gray-400">Back</Button>

        <AssessmentModal
          :disabled="[1, 4].includes(roleId) && props.form.application_status >= 3 && props.form.application_status <= 13"
          :status_id="props.form.application_status" class="w-full" :applicationId="Number(props.form.id)"
          @submit-assessments="submitAllAssessments" />

      </div>
    </div>


  </div>

  <!-- Mobile -->
  <div v-else class="space-y-3">

    <div>
      <Toast />
      <ReusableConfirmDialog ref="confirmDialogRef" />

      <!-- Status -->
      <div v-if="isEdit" class="flex items-center justify-between rounded-xl p-4 shadow-sm border">
        <Info class="h-5 w-5" />
        <h1 class="text-xl font-bold text-gray-800">
          Application Status
        </h1>
        <Tag severity="danger">
          {{ props.form.status_title }}
        </Tag>
      </div>

      <!-- Applicant Details -->
      <Fieldset legend="Applicant Details" :toggleable="applicationData.application_status != 25">
        <div class="space-y-3">

          <!-- Application No -->
          <div class="rounded-lg border p-3 bg-gray-50">
            <div class="text-xs text-gray-500 font-semibold">
              Application No.
            </div>

            <div class="mt-1">
              <Tag :value="applicationData?.application_no" severity="success" />
            </div>
          </div>

          <!-- Date Applied -->
          <div class="rounded-lg border p-3">
            <div class="text-xs text-gray-500 font-semibold">
              Date Applied
            </div>

            <div class="mt-1 font-medium">
              {{ formatDate(applicationData.date_applied) }}
            </div>
          </div>

          <!-- Transaction -->
          <div class="rounded-lg border p-3">
            <div class="text-xs text-gray-500 font-semibold">
              Type of Transaction
            </div>

            <div class="mt-1">
              {{ applicationData.type_of_transaction }}
            </div>
          </div>

          <!-- Classification -->
          <div class="rounded-lg border p-3">
            <div class="text-xs text-gray-500 font-semibold">
              Classification
            </div>

            <div class="mt-1">
              {{ applicationData.classification }}
            </div>
          </div>

          <!-- Company -->
          <template v-if="applicationData.application_type === 'Company'">
            <div class="rounded-lg border p-3">
              <div class="text-xs text-gray-500 font-semibold">
                Company Name
              </div>

              <div class="mt-1">
                {{ applicationData.company_name }}
              </div>
            </div>

            <div class="rounded-lg border p-3">
              <div class="text-xs text-gray-500 font-semibold">
                Authorized Representative
              </div>

              <div class="mt-1">
                {{ applicationData.authorized_representative }}
              </div>
            </div>
          </template>

          <!-- Government -->
          <template v-else-if="applicationData.application_type === 'Government'">
            <div class="rounded-lg border p-3">
              <div class="text-xs text-gray-500 font-semibold">
                Office Name
              </div>

              <div class="mt-1">
                {{ applicationData.company_name }}
              </div>
            </div>

            <div class="rounded-lg border p-3">
              <div class="text-xs text-gray-500 font-semibold">
                Authorized Representative
              </div>

              <div class="mt-1">
                {{ applicationData.authorized_representative }}
              </div>
            </div>
          </template>

          <!-- Individual -->
          <template v-else>
            <div class="rounded-lg border p-3">
              <div class="text-xs text-gray-500 font-semibold">
                Applicant Name
              </div>

              <div class="mt-1">
                {{ applicationData.first_name }}
                {{ applicationData.middle_name }}
                {{ applicationData.last_name }}
              </div>
            </div>
          </template>

          <!-- Contact -->
          <div class="rounded-lg border p-3">
            <div class="text-xs text-gray-500 font-semibold">
              Contact Details
            </div>

            <div class="mt-1">
              {{ applicationData.mobile_no }}
            </div>
          </div>

          <!-- Region -->
          <div class="rounded-lg border p-3">
            <div class="text-xs text-gray-500 font-semibold">
              Region
            </div>

            <div class="mt-1">
              REGION IV-A (CALABARZON)
            </div>
          </div>

          <!-- Address -->
          <div class="rounded-lg border p-3">
            <div class="text-xs text-gray-500 font-semibold">
              Complete Address
            </div>

            <div class="mt-1 break-words">
              <template v-if="applicationData.application_type === 'Company'">
                {{ applicationData.company_address }}
              </template>

              <template v-else-if="applicationData.application_type === 'Government'">
                {{ applicationData.company_address }}
              </template>

              <template v-else>
                {{ applicationData.i_complete_address }}
              </template>
            </div>
          </div>

        </div>
      </Fieldset>


      <!-- ================= Registration Information ================= -->
      <Fieldset legend="Registration Information" toggleable v-model:collapsed="isRegistrationInfoCollapsed">
        <div class="space-y-3">

          <!-- Encoded By -->
          <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
              Encoded By
            </div>

            <div class="mt-2">
              <Tag severity="success">
                {{ props.form.registered_by }}
              </Tag>
            </div>

            <div class="mt-2 text-sm text-gray-700">
              {{ props.form.office_title }} - {{ props.form.role_title }}
            </div>
          </div>

          <!-- Registered Date -->
          <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
              Registered Date & Time
            </div>

            <div class="mt-2 text-sm font-medium">
              {{ props.form.created_at }}
            </div>
          </div>

        </div>
      </Fieldset>

      <!-- ================= Routing History ================= -->

      <Fieldset legend="Routing History" toggleable v-model:collapsed="isRoutingCollapsed">
        <div v-if="routingHistory.length === 0" class="rounded-xl border bg-white p-5 text-center text-gray-500">
          No routing history found
        </div>

        <div v-for="(item, index) in routingHistory" :key="index" class="mb-4 rounded-xl border bg-white shadow-sm">
          <!-- Header -->

          <div class="flex items-center justify-between rounded-t-xl bg-blue-900 px-4 py-3 text-white">
            <div class="font-semibold">
              Route #2026-00{{ item.route_order }}
            </div>

            <Tag v-if="item.action === 'Received'" severity="danger">
              Received
            </Tag>

            <Tag v-else-if="item.action === 'Endorsed'" severity="info">
              Endorsed
            </Tag>

            <Tag v-else-if="
              item.action === 'Returned to Technical Staff' ||
              item.action === 'Returned to PENRO Technical Staff'
            " severity="danger">
              Returned
            </Tag>

            <Tag v-else severity="success">
              {{ item.action }}
            </Tag>
          </div>

          <!-- Body -->

          <div class="space-y-4 p-4 text-sm">

            <!-- Sender -->

            <div v-if="![2, 4, 6, 8, 10].includes(item.route_order)" class="rounded-lg bg-gray-50 p-3">
              <div class="text-xs font-semibold text-gray-500">
                Sender
              </div>

              <div class="mt-1 font-semibold">
                {{ item.sender_role }}
              </div>

              <div class="italic text-gray-600">
                {{ item.sender }}
              </div>
            </div>

            <!-- Receiver -->

            <div class="rounded-lg bg-gray-50 p-3">
              <div class="text-xs font-semibold text-gray-500">
                Receiver
              </div>

              <div class="mt-1 font-semibold">
                {{ item.receiver_role }}
              </div>
            </div>

            <!-- Date Received -->
             

            <div
            v-if="[1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31, 33, 35, 37, 39, 41, 43, 45, 47, 49, 51, 
            53, 55, 57, 59, 61, 63, 65, 67, 69, 71, 73, 75, 77, 79, 81, 83, 85, 87, 89, 91, 93, 95, 97, 99, 101, 103, 
            105, 107, 109, 111, 113, 115, 117, 119, 121, 123, 125, 127, 129, 131, 133, 135, 137, 139, 141, 143, 145, 147, 149].includes(item.route_order)"
            class="rounded-lg bg-gray-50 p-3">
              <div class="text-xs font-semibold text-gray-500">
                Date Received
              </div>

              <div class="mt-1">
                {{
                  item.created_at
                    ? new Date(item.created_at).toLocaleString(
                      'en-PH',
                      {
                        year: 'numeric',
                        month: 'long',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true
                      }
                    )
                    : '-'
                }}
              </div>
            </div>

            <!-- Date Endorsed -->

            <div
            v-if="[2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36, 38, 40, 42, 44, 46, 48, 50, 52, 
                54, 56, 58, 60, 62, 64, 66, 68, 70, 72, 74, 76, 78, 80, 82, 84, 86, 88, 90, 92, 94, 96, 98, 100, 102, 104, 
                106, 108, 110, 112, 114, 116, 118, 120, 122, 124, 126, 128, 130, 132, 134, 136, 138, 140, 142, 144, 146, 148, 150].includes(item.route_order)"
            class="rounded-lg bg-gray-50 p-3">
              <div class="text-xs font-semibold text-gray-500">
                Date Endorsed
              </div>

              <div class="mt-1">
                {{
                  item.updated_at
                    ? new Date(item.updated_at).toLocaleString(
                      'en-PH',
                      {
                        year: 'numeric',
                        month: 'long',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true
                      }
                    )
                    : '-'
                }}
              </div>
            </div>

            <!-- Date Returned -->

            <div v-if="
              item.action === 'Returned to Technical Staff' ||
              item.action === 'Returned to PENRO Technical Staff'
            " class="rounded-lg bg-red-50 p-3">
              <div class="text-xs font-semibold text-red-600">
                Date Returned
              </div>

              <div class="mt-1">
                {{
                  new Date(item.updated_at).toLocaleString(
                    'en-PH',
                    {
                      year: 'numeric',
                      month: 'long',
                      day: '2-digit',
                      hour: '2-digit',
                      minute: '2-digit',
                      second: '2-digit',
                      hour12: true
                    }
                  )
                }}
              </div>
            </div>

            <!-- Remarks -->

            <div class="rounded-lg bg-gray-50 p-3">
              <div class="text-xs font-semibold text-gray-500">
                Remarks
              </div>

              <div class="mt-1 break-words">
                {{ item.remarks ?? '-' }}
              </div>
            </div>

          </div>
        </div>
      </Fieldset>

      <!-- Chainsaw Info -->
      <Fieldset legend="Chainsaw Information" toggleable v-model:collapsed="isChainsawInfoCollapsed">
        <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
          <div class="md:col-span-2">
            <table class="w-full border border-gray-300 text-sm">
              <tbody>
                <!-- <tr class="border-b">
                <td class="w-56 bg-gray-50 p-2 font-semibold">Supplier Name</td>
                <td class="p-2">
                  <ul class="ml-4 list-disc">
                    <li v-for="(supplier, i) in suppliers" :key="i">
                      {{ supplier.supplier_name }}
                    </li>
                  </ul>
                </td>
              </tr> -->

                <tr>
                  <td class="bg-gray-50 p-2 font-semibold">
                    Purpose of Purchase
                  </td>

                  <td class="p-2">
                    {{ suppliers[0].purpose }}
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
              </tbody>
            </table>
          </div>

          <div class="md:col-span-2">
            <table class="w-full border border-gray-300 text-sm">
              <tbody>

                <tr class="border-b">
                  <td class="bg-gray-50 p-2 font-semibold">Official Receipt No.</td>
                  <td class="p-2">
                    <Tag :value="applicationData.official_receipt" severity="success" />
                  </td>
                </tr>

                <tr>
                  <td class="bg-gray-50 p-2 font-semibold">Permit Fee</td>
                  <td class="p-2">₱ {{ applicationData.permit_fee }}</td>
                </tr>


                <tr>
                  <td class="bg-gray-50 p-2 font-semibold">Date of Payment</td>
                  <td class="p-2">
                    <b>{{ formatDate(applicationData.date_of_payment) }}</b>
                  </td>
                </tr>


              </tbody>
            </table>
          </div>

          <!-- ✅ Brands & Models -->
          <div class="md:col-span-2">

            <h3 class="mb-4 text-lg font-semibold">
              Supplier Information
            </h3>

            <div v-for="(supplier, sIndex) in suppliers" :key="sIndex" class="mb-6 rounded-lg border shadow-sm">

              <!-- Header -->

              <div class="bg-blue-900 text-white px-4 py-2 font-semibold">

                Supplier {{ sIndex + 1 }}

              </div>

              <!-- Supplier Details -->

              <div class="grid grid-cols-2 gap-3 p-4 text-sm">

                <div>

                  <b>Supplier Name</b><br>

                  {{ supplier.supplier_name }}

                </div>

                <div>

                  <b>Supplier Address</b><br>

                  {{ supplier.supplier_address }}

                </div>

                <div>

                  <b>Permit To Sell No.</b><br>

                  {{ supplier.permit_to_sell_no }}

                </div>

                <div>

                  <b>Issued By</b><br>

                  {{ supplier.issued_by }}

                </div>

                <div>

                  <b>Issued Date</b><br>

                  {{ formatDate(supplier.issued_date) }}

                </div>

                <div>

                  <b>Valid Until</b><br>

                  {{ formatDate(supplier.permit_validity || supplier.valid_until) }}

                </div>

              </div>

              <!-- Chainsaw Details -->

              <div class="px-4 pb-4">

                <table class="w-full border text-sm">

                  <thead class="bg-gray-100">

                    <tr>

                      <th class="border p-2">
                        Brand
                      </th>

                      <th class="border p-2">
                        Model
                      </th>

                      <th class="border p-2">
                        Quantity
                      </th>

                    </tr>

                  </thead>

                  <tbody style="text-align: center;">

                    <tr v-for="(row, index) in supplier.rows" :key="index">

                      <td class="border p-2">
                        {{ row.brand_name }}
                      </td>

                      <td class="border p-2">
                        {{ row.model || row.model_name }}
                      </td>

                      <td class="border p-2 text-center">
                        {{ row.quantity }}
                      </td>

                    </tr>

                  </tbody>

                </table>

              </div>

            </div>

          </div>
        </div>
      </Fieldset>


      <AssessmentTable title="Applicant Requirements" :collapsed="applicationData.application_status === 25"
        :application_status="props.form.status_title" :roleId="roleId" :rows="companyRequirements" :onsite="onsite"
        @view-file="openFileModal" @update-assessment="updateAssessment" @update-remarks="updateRemarks"
        @update-onsite="updateOnsite" @upload-resubmission="handleResubmissionUpload"
        @remove-resubmission="handleRemoveResubmission" :overallRemarks="overallRemarks"
        @update-overall-remarks="overallRemarks = $event" />

      <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
        <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.file_url)" width="100%" height="500"
          allow="autoplay"></iframe>
      </Dialog>

      <div :class="[
        'pt-6 w-full gap-4',
        currentStep === 4
          ? 'grid grid-cols-1 sm:grid-cols-2'
          : 'flex flex-col sm:flex-row sm:justify-end'
      ]">
        <!-- Return Application -->
        <Button v-if="roleId === 1 || (props.form.status_title !== 'Draft' && currentStep === 4)"
          :disabled="roleId === 1"
          class="h-10 w-full sm:w-auto sm:ml-auto px-4 py-2 flex items-center justify-center gap-2 rounded-md bg-red-700 text-white hover:bg-red-800"
          @click="returnApplication">
          <Undo2 />
          Return Application
        </Button>

        <!-- Back -->
        <Button v-else-if="![1, 25, 26, 27].includes(props.form.application_status)" variant="outline"
          @click="emit('back')" class="w-full sm:w-auto bg-gray-300 hover:bg-gray-400">
          Back
        </Button>

        <!-- Assessment -->
        <AssessmentModal :disabled="[1, 4].includes(roleId) &&
          props.form.application_status >= 3 &&
          props.form.application_status <= 13" :status_id="props.form.application_status" class="w-full sm:w-auto"
          :applicationId="Number(props.form.id)" @submit-assessments="submitAllAssessments" />
      </div>

    </div>

  </div>
</template>