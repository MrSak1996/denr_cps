<script setup lang="ts">
// Imports
import { onMounted, reactive, ref, computed } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Fieldset from 'primevue/fieldset';
import { ShieldAlert, LoaderCircle, Info, CircleCheckBig, Undo2, Send, Undo, MonitorUp } from 'lucide-vue-next';
import AssessmentTable from './assessment_tbl.vue';
import AssessmentModal from '../modal/assessment_modal.vue';
import ReusableConfirmDialog from '../modal/endorsed_modal.vue';
import ChainsawSupplierForm from '@/components/ChainsawSupplierForm.vue';

// Custom Components
import { useApi } from '@/composables/useApi';
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';
import { updateChainsawForm } from '@/lib/chainsaw';
import { ChainsawData } from '@/types/chainsaw';
import LoadingSpinner from '../../LoadingSpinner.vue';
import Chainsaw_applicationField from '../forms/chainsaw_applicationField.vue';
import Chainsaw_companyField from '../forms/chainsaw_companyField.vue';
import Chainsaw_operationField from '../forms/chainsaw_operationField.vue';
import FileCard from '../forms/file_card.vue';
import { Button } from '@/components/ui/button';
import Toast from 'primevue/toast';
import InputText from 'primevue/inputtext';
import FloatLabel from 'primevue/floatlabel';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import ProgressBar from 'primevue/progressbar';
const page = usePage();

const props = defineProps({
    application: Object,
    mode: String,
});
const defaultSupplierDialog = ref(false);
function openDefaultSupplierDialog() { defaultSupplierDialog.value = true; }

const application = page.props.application.application_status;


// Form Data
const isCollapsed = ref(true)
const isRoutingCollapsed = ref(true)
const isChainsawInfoCollapsed = ref(true)
const { company_form, chainsaw_form, payment_form } = useAppForm();
const { insertFormData } = useFormHandler();
const { getProvinceCode, prov_name } = useApi();

Object.assign(company_form, page.props.application || {});
Object.assign(chainsaw_form, page.props.application || {});
Object.assign(payment_form, page.props.application || {});

// Refs & Reactives
const chainsaws = reactive<ChainsawForm[]>([{ ...JSON.parse(JSON.stringify(chainsaw_form)) }]);
const toast = useToast();
const userId = page.props.auth?.user?.id;
const roleId = page.props.auth?.user?.role_id;
const officeId = page.props.auth?.user?.office_id;
const files = ref([]);
const routingHistory = ref([]);
const assessmentRows = ref([])
const progress = ref(0);

const isLoading = ref(false);
const showPrivacyDialog = ref(false);
const isloadingSpinner = ref(false);
const applicationData = ref<any>({});

const i_city_mun = ref<number | string>(0);

const currentStep = ref(4);
const errorMessage = ref('');
const selectedFile = ref(null);
const showModal = ref(false);
const showFileModal = ref(false);
const selectedFileToUpdate = ref(null)
const updateFileInput = ref(null)
const confirmDialogRef = ref<any>(null);

const brands = ref([
    {
        name: '',
        models: [{ model: '', quantity: 1 }]
    }
])

const suppliers = ref([
    {
        supplier_name: '',
        supplier_address: '',
        permit_to_sell_no: '',
        serial_no: '',
        issued_date: '',
        valid_until: '',
        issued_by: '',
        brands: [
            {
                name: '',
                models: [
                    { model: '', quantity: 1, serial_no: '' }
                ]
            }
        ]
    }
])

const onsite = ref({
    findings: '',
    recommendations: ''
});

const hasFailed = computed(() => {
    return companyRequirements.value.some(r => r.assessment === 'failed')
})

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: '2-digit'
    });
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

const updateAssessment = (checklist_entry_id, assessment) => {
    const row = companyRequirements.value.find(
        r => r.checklist_entry_id === checklist_entry_id
    );
    if (row) {
        row.assessment = assessment;
        row.is_saved = false; // unlock save again if changed
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

// Remove handler
const handleRemoveResubmission = (checklistId: number, index: number) => {
    const row = companyRequirements.find(r => r.checklist_entry_id === checklistId);
    if (!row || !row.resubmissions[index]) return;

    // Optional: call API to remove file from server
    // await axios.delete(`/api/remove-resubmission/${row.resubmissions[index].id}`);

    // Remove from array
    row.resubmissions.splice(index, 1);
};
const updateRemarks = (checklist_entry_id, remarks) => {
    const row = companyRequirements.value.find(
        r => r.checklist_entry_id === checklist_entry_id
    );
    if (row) {
        row.remarks = remarks;
        row.is_saved = false;
    }
};

const updateOnsite = ({ field, value }) => {
    onsite.value[field] = value;
};

const returnApplication = async () => {

    const incompleteRows = companyRequirements.value
        .map((row, index) => ({ index: index + 1, assessment: row.assessment }))
        .filter(r => !r.assessment);

    if (incompleteRows.length) {
        alert(`Incomplete assessment on row(s): ${incompleteRows.map(r => r.index).join(', ')}`);
        return;
    }


    await axios.post('/api/returnApplication', {
        application_id: page.props.application.id,
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

// Inside your component setup

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

const fetchRoutingHistory = async () => {

    routingHistory.value = [];

    try {
        const res = await axios.get(`/api/application-routing/${page.props.application.id}`);
        routingHistory.value = res.data;
    } catch (error) {
        console.error(error);
    }

};

const sendEmail = async () => {
    try {
        const response = await axios.post('/api/send-email', {
            email: 'kimsacluti10101996@gmail.com',
            applicant_name: company_form.authorized_representative,
            address: company_form.company_address,
            application_no: company_form.application_no
        });

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: response.data.message,
            life: 3000,
        });
        return response.data;

    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message,
            life: 3000,
        });
        throw error;
    }
};

const submitAllAssessments = async (applicationId) => {
    if (roleId !== 1 && roleId !== 4 && roleId !== 11 && roleId !== 12) {
        const incomplete = companyRequirements.value.some(row => !row.assessment);

        if (incomplete) {
            alert('Please complete all assessments before submitting.');
            return;
        }
    }

    const workflowType = roleId === 4 ? 'implementing_agency' : 'smooth';

    const isARDTSD = roleId === 11; // ✅ ONLY ARD/TSD
    const isEndorsingToRD = !hasFailed;

    try {
        await axios.post('/api/saveAssessment', {
            application_id: applicationId,
            userId: userId,
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
        if (isARDTSD) {
            await sendEmail();
            console.log("sent email");
        }
        // ✅ NOW ONLY TRIGGERS FOR ROLE 11
        const redirectMap = {
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
            setTimeout(() => {
                router.visit(redirectPath);
            }, 5000);
        }


    } catch (error) {
        console.error(error);
    }
};





// BRAND ACTIONS
const addBrand = () => {
    brands.value.push({
        name: '',
        models: [{ model: '', quantity: 1 }]
    })
}

const removeBrand = (index: number) => {
    if (brands.value.length > 1) {
        brands.value.splice(index, 1)
    }
}

// MODEL ACTIONS
const addModel = (brandIndex: number) => {
    brands.value[brandIndex].models.push({ model: '', quantity: 1 })
}

const removeModel = (brandIndex: number, modelIndex: number) => {
    const models = brands.value[brandIndex].models
    if (models.length > 1) {
        models.splice(modelIndex, 1)
    }
}






const purposeOptions = ref([
    'For cutting of trees with legal permit',
    'For Post-calamity cleaning operations',
    'For farm lot/tree orchard maintenance',
    'For cutting-trimming of trees posing danger within a private property',
    'For selling / re-selling',
    'For cutting of trees to be used for house repair or perimeter fencing',
    'Forestry/landscaping service provider',
    'Other Legal Purpose',
    'Other Supporting Documents',
]);

// Utility
const getApplicationIdFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('application_id') || urlParams.get('id');
};


// Step Navigation
const isStepValid = (stepId) => {
    // Implement validation per step if needed
    return true;
};

// const handleStepClick = (targetStep) => {
//     if (targetStep <= currentStep.value || isStepValid(currentStep.value)) {
//         currentStep.value = targetStep;
//     } else {
//         showError();
//     }
// };



// ─────────────────────────────────────────────────────────────
// STEPPER
// ─────────────────────────────────────────────────────────────
const steps = ref([
    { label: 'Applicant Form', id: 1 },
    { label: 'Permit to Sell Chainsaw', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 },
]);

const formValidationRules = {
    1: {
        form: 'company_form',
        fields: [
            'application_no',
            'application_type',
            'company_name',
            'company_address',
            'authorized_representative',
            'c_province',
            'c_city_mun',
            'c_barangay',

        ],
        labels: {
            application_no: 'Application No',
            application_type: 'Application Type',
            company_name: 'Company Name',
            company_address: 'Company Address',
            authorized_representative: 'Authorized Representative',
            c_province: 'Company Province',
            c_city_mun: 'Company City/Municipality',
            c_barangay: 'Company Barangay',
            p_place_of_operation_address: 'Place of Operation Address',
            p_province: 'Place of Operation Province',
            p_city_mun: 'Place of Operation City/Municipality',
            p_barangay: 'Place of Operation Barangay'
        }
    },
    2: {
        form: 'chainsaw_form',
        fields: [
            'permit_validity',
            // 'permit_chainsaw_no',
            'brand',
            'model',
            'quantity',
            'supplier_name',
            'supplier_address',
            'purpose'
        ],
        labels: {
            permit_validity: 'Permit Validity',
            // permit_chainsaw_no: 'Permit Chainsaw No',
            brand: 'Brand',
            model: 'Model',
            quantity: 'Quantity',
            supplier_name: 'Supplier Name',
            supplier_address: 'Supplier Address',
            purpose: 'Purpose'
        }
    },
    3: {
        form: 'payment_form',
        fields: [
            'official_receipt',
            'permit_fee',
            'date_of_payment'
        ],
        labels: {
            official_receipt: 'Official Receipt',
            permit_fee: 'Permit Fee',
            date_of_payment: 'Date of Payment'
        }
    }
};

/**
 * ✅ Validate the current step form dynamically and return missing fields
 */
const validateForm = () => {
    const stepRules = formValidationRules[currentStep.value];

    if (!stepRules || !stepRules.fields || stepRules.fields.length === 0) return true;

    let formToCheck = [];

    // ✅ Determine which form to validate
    if (stepRules.form === 'company_form') {
        formToCheck = [company_form]; // wrap in array for uniform processing
    } else if (stepRules.form === 'chainsaw_form') {
        formToCheck = chainsaws; // this is an array of chainsaws
    } else if (stepRules.form === 'payment_form') {
        formToCheck = [payment_form];
    }

    const missingFields = [];

    // ✅ Loop through each form entry
    formToCheck.forEach((form, index) => {
        stepRules.fields.forEach((field) => {
            if (
                form[field] === '' ||
                form[field] === null ||
                form[field] === undefined
            ) {
                // If multiple chainsaws, indicate which one is missing
                const label = stepRules.labels[field] || field;
                if (formToCheck.length > 1) {
                    missingFields.push(`${label} (Chainsaw ${index + 1})`);
                } else {
                    missingFields.push(label);
                }
            }
        });
    });

    // ✅ Show toast if any missing fields
    if (missingFields.length > 0) {
        toast.add({
            severity: 'warn',
            summary: 'Incomplete Fields',
            detail: `Please fill out the following fields: ${missingFields.join(', ')}`,
            life: 5000,
        });

        return false;
    }

    return true;
};

/**
 * ✅ Next step logic when user clicks "Next" button
 */
const getApplicationDetails = async () => {
    const applicationId = getApplicationIdFromUrl();
    if (!applicationId) {
        errorMessage.value = 'No application ID found in the query.';
        isLoading.value = false;
        return;
    }

    try {
        const response = await axios.get(`http://cps.denrcalabarzon.com/api/getApplicationDetails/${applicationId}`);
        applicationData.value = response.data.data ?? {};
        i_city_mun.value = response.data.data?.i_city_mun ?? i_city_mun.value;
    } catch (error: any) {
        errorMessage.value = error.message || 'Error fetching application data.';
    } finally {
        isLoading.value = false;
    }
};

const nextStep = async () => {
    // Prevent advancing past the last step
    if (currentStep.value > steps.value.length) return;

    isLoading.value = true;
    showPrivacyDialog.value = false;

    // ✅ DEFINE progress interval
    progress.value = 0;
    let progressInterval: any = setInterval(() => {
        if (progress.value < 90) {
            progress.value += 10;
        }
    }, 200);

    try {
        const handlers: Record<number, Function> = {
            1: async () => {
                if (!company_form.request_letter || !company_form.soc_certificate) {
                    toast.add({
                        severity: 'warn',
                        summary: 'Missing Requirements',
                        detail: 'Please upload Request Letter and SEC/DTI Certificate.',
                        life: 4000
                    });
                    return false;
                }

                if (applicationData.value?.application_no) {
                    currentStep.value = 2;
                    return true;
                }

                return await saveCompanyApplication();
            },
            2: submitChainsawForm,
            3: submitORPayment,
            4: saveAsDraft
        };

        const handler = handlers[currentStep.value];

        if (handler) {
            const isSaved = await handler();

            if (!isSaved) {
                return; // ❗ let finally handle cleanup
            }

            await getApplicationDetails();

            if (!applicationData.value || !applicationData.value.application_no) {
                console.error('Application details missing after save.');
                return;
            }
        }

        if (!completedSteps.value.includes(currentStep.value)) {
            completedSteps.value = [...completedSteps.value, currentStep.value];
        }

        currentStep.value++;

        // ✅ Finish progress
        clearInterval(progressInterval);
        progress.value = 100;

        await new Promise(resolve => setTimeout(resolve, 300));

    } catch (error) {
        console.error(error);
    } finally {
        // ✅ ALWAYS CLEANUP HERE ONLY
        clearInterval(progressInterval);
        isLoading.value = false;
        progress.value = 0;
    }
};

const saveCompanyApplication = async () => {
    isLoading.value = true;
    isloadingSpinner.value = false;
    const applicationId = page.props.id;

    const formData = new FormData();
    formData.append('request_letter', company_form.request_letter);
    formData.append('soc_certificate', company_form.soc_certificate);
    formData.append('id', applicationId);

    try {
        const response = await insertFormData(
            'http://cps.denrcalabarzon.com/api/chainsaw/company_apply',
            {
                ...company_form,
                ...formData,
                encoded_by: userId,
            }
        );

        toast.add({
            severity: 'success',
            summary: 'Saved',
            detail: 'Company application submitted successfully.',
            life: 3000
        });

        router.get(
            route('applications.index', {
                application_id: response.application_id,
                type: 'company',
                step: 2
            })
        );

        return true;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: 'There was an error saving the application.',
            life: 3000
        });

        return false;
    } finally {
        isLoading.value = false;
        isloadingSpinner.value = false;
    }
};

const submitChainsawForm = async () => {
    isLoading.value = true;
    console.log(props.application);
    const applicationId = page.props.id;
    const application_no = page.props.application_no;
    const application_type = page.props.application_type;
    try {
        const formData = new FormData();

        formData.append('id', applicationId);
        formData.append('applicant_type', applicationData.value.application_type);

        // Example for first supplier
        formData.append('supplier_name', suppliers.value[0]?.supplier_name || '');
        formData.append('supplier_address', suppliers.value[0]?.supplier_address || '');
        formData.append('purpose', applicationData.value.purpose);
        formData.append('permit_validity', formatDate(applicationData.value.validity_date));
        formData.append('permit_chainsaw_no', applicationData.value.permit_chainsaw_no);
        formData.append('uploaded_by', userId);
        formData.append('issued_date', formatDate(applicationData.value.issued_date));
        formData.append('issued_by', applicationData.value.issued_by);

        // ✅ send brands as JSON
        // formData.append('brands', JSON.stringify(brands.value));
        formData.append('suppliers', JSON.stringify(suppliers.value));

        // ✅ send files once
        ['mayorDTI', 'affidavit', 'otherDocs', 'permit'].forEach((key) => {
            const file = applicationData[key];
            if (file instanceof File) {
                formData.append(key, file);
            }
        });

        const response = await axios.post(
            'http://cps.denrcalabarzon.com/api/chainsaw/insertChainsawInfo',
            formData
        );

        const newApplicationId = response.data.application_id;

        router.get(
            route('applications.index', {
                application_id: newApplicationId,
                type: 'company',
                step: 3
            })
        );

        return true;
    } catch (error) {
        console.error(error);
        return false;
    } finally {
        isLoading.value = false;
    }
};

const saveAsDraft = async () => {
    toast.add({
        severity: 'info',
        summary: 'Saved',
        detail: 'Application saved as draft.',
        life: 3000
    });

    router.get(
        route('applications.pending_application', {
            application_id: getApplicationIdFromUrl(),
            type: 'company',
            step: currentStep.value
        })
    );

    return true;
};

const submitORPayment = async () => {
    isLoading.value = true;
    isloadingSpinner.value = true;

    const applicationId = page.props.id;
    const application_no = page.props.application_no;
    const application_type = page.props.application_type;

    const formData = new FormData();

    formData.append('id', applicationId);
    formData.append('applicant_type', application_type);
    formData.append('official_receipt', payment_form.official_receipt);
    formData.append('permit_fee', payment_form.permit_fee);
    formData.append('application_no', application_no);
    formData.append('or_copy', payment_form.or_copy);
    formData.append('uploaded_by', userId);

    try {
        const response = await axios.post(
            'http://cps.denrcalabarzon.com/api/chainsaw/insert_payment',
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }
        );

        toast.add({
            severity: 'success',
            summary: 'Saved',
            detail: 'Payment Details submitted successfully',
            life: 3000
        });

        const newApplicationId =
            response.data.application_id || applicationId;

        router.get(
            route('applications.index', {
                application_id: newApplicationId,
                type: application_type,
                step: 4
            })
        );

        return true;

    } catch (error) {
        console.error(error);

        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: 'There was an error saving the payment details.',
            life: 3000
        });

        return false;

    } finally {
        isLoading.value = false;
        isloadingSpinner.value = false;
    }
};




const handleStepClick = (targetStep) => {
    if (targetStep <= currentStep.value || isStepValid(currentStep.value)) {
        currentStep.value = targetStep;
    } else {
        showError();
    }
};


const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const showError = () => {
    toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please complete all required fields before proceeding.',
        life: 3000,
    });
};


const openFileModal = (file) => {
    selectedFile.value = file;
    showModal.value = true;
};


const copyAllFields = (index) => {
    if (chainsaws[index].copyAll && index > 0) {
        const first = chainsaws[0];
        chainsaws[index] = {
            ...first,
            copyAll: true,
            letterRequest: null,
        };
    }
};

const addChainsaw = () => {
    chainsaws.push(JSON.parse(JSON.stringify(chainsaw_form)));
};

const removeChainsaw = (index) => {
    if (chainsaws.length > 1) chainsaws.splice(index, 1);
};

const handleFileUpload = (event, index) => {
    chainsaws[index].letterRequest = event.target.files[0];
};

const handlePurposeFileUpload = (event, fieldName, index) => {
    chainsaws[index][fieldName] = event.target.files[0];
};

const handleORFileUpload = (event, field) => {
    payment_form[field] = event.target.files[0];
};


const updateCompanyApplication = async () => {
    isLoading.value = true;
    isloadingSpinner.value = true;

    const applicationId = page.props.application.id;

    const formData = new FormData();

    // 🔥 Append ALL fields from company_form automatically
    Object.entries(company_form).forEach(([key, value]) => {
        // Skip null values safely
        if (value !== null && value !== undefined) {
            formData.append(key, value);
        }
    });

    // 🔥 Add encoded_by separately
    formData.append("encoded_by", userId);

    try {
        const response = await axios.post(
            `/applications/${applicationId}/update-company-data`,
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );

        if (response.data.status === "success") {
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Company application updated successfully.",
                life: 3000,
            });
            return true;
        } else {
            toast.add({
                severity: "warn",
                summary: "No Changes",
                detail: response.data.message,
                life: 3000,
            });
            return false;
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: "error",
            summary: "Failed",
            detail: "There was an error saving the application.",
            life: 3000,
        });
        return false;
    } finally {
        isLoading.value = false;
        isloadingSpinner.value = false;
    }
};

const updateChainsawInformation = async () => {
    isLoading.value = true;
    // isloadingSpinner.value = true;
    const applicationId = page.props.application.id;
    try {
        for (const chainsaw of chainsaws) {
            const formData = new FormData();

            // Object.entries(chainsaw_form).forEach(([key, value]) => {
            //     if (value !== null && value !== undefined && !(value instanceof File)) {
            //         formData.append(key, value);
            //     }
            // });

            ['mayorDTI', 'affidavit', 'otherDocs', 'permit'].forEach((fileKey) => {
                if (chainsaw[fileKey]) formData.append(fileKey, chainsaw[fileKey]);
            });

            const response = await axios.put(`/applications/${applicationId}/update-chainsaw-info`, {
                application_id: applicationId,
                permit_chainsaw_no: chainsaw_form.permit_chainsaw_no,
                permit_validity: chainsaw_form.permit_validity,
                brand: chainsaw_form.brand,
                model: chainsaw_form.model,
                quantity: chainsaw_form.quantity,
                supplier_name: chainsaw_form.supplier_name,
                supplier_address: chainsaw_form.supplier_address,
                purpose: chainsaw_form.purpose,
                other_details: chainsaw_form.other_details,
            });

            if (response.data.status === "success") {
                toast.add({
                    severity: "success",
                    summary: "Updated",
                    detail: "Company application updated successfully.",
                    life: 3000,
                });
                return true;
            } else {
                toast.add({
                    severity: "warn",
                    summary: "No Changes",
                    detail: response.data.message,
                    life: 3000,
                });
                return false;
            }
        }
        return true;
    } catch (error) {
        console.error('Upload failed:', error);
        return false;
    } finally {
        isloadingSpinner.value = false;
    }
};

const updatePaymentInfo = async () => {
    isLoading.value = true;
    isloadingSpinner.value = true;

    const applicationId = page.props.application.id;

    const formData = new FormData();

    // 🔥 Append ALL fields from company_form automatically
    Object.entries(payment_form).forEach(([key, value]) => {
        // Skip null values safely
        if (value !== null && value !== undefined) {
            formData.append(key, value);
        }
    });

    // 🔥 Add encoded_by separately
    formData.append("encoded_by", userId);

    try {
        const response = await axios.post(
            `/applications/${applicationId}/update-company-payment-data`,
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );

        if (response.data.status === "success") {
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Payment Info updated successfully.",
                life: 3000,
            });
            return true;
        } else {
            toast.add({
                severity: "warn",
                summary: "No Changes",
                detail: response.data.message,
                life: 3000,
            });
            return false;
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: "error",
            summary: "Failed",
            detail: "There was an error saving the application.",
            life: 3000,
        });
        return false;
    } finally {
        isLoading.value = false;
        isloadingSpinner.value = false;
    }
};

const submitApplication = async () => {
    try {
        const applicationId = page.props.application.id;
        const officeId = page.props.application.office_title;

        const response = await axios.put(`/applications/${applicationId}/submit-application`, {

            application_id: applicationId,
            office: officeId
        });

        if (response.data.status === "success") {
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Company application updated successfully.",
                life: 3000,
            });
            router.get(route('applications.pending_application'));
            return true;
        } else {
            toast.add({
                severity: "warn",
                summary: "No Changes",
                detail: response.data.message,
                life: 3000,
            });
            return false;
        }
    } catch (error) {

    }
}
// API Calls


// Map tab number → allowed prefixes (from your folderMap)
const tabMap = {
    1: ['request_letter', 'secretary_certificate'],
    2: ['mayors_permit', 'notarized_documents', 'permit', 'others'],
    3: ['official_receipt']
};

const filesByTab = ref({
    0: [],
    1: [],
    2: [],
    3: []
});


const getApplicantFile = async (application_id) => {
    try {
        const checklistRes = await axios.get(
            `http://cps.denrcalabarzon.com/api/getChecklistEntries/${application_id}`
        );

        const attachmentsRes = await axios.get(
            `http://cps.denrcalabarzon.com/api/getApplicantFile/${application_id}`
        );

        if (checklistRes.data.status && attachmentsRes.data.status) {
            const checklistEntries = checklistRes.data.data;
            const attachments = attachmentsRes.data.data;

            // Map attachments by checklist_entry_id
            // const attachmentsMap = attachments.reduce((acc, file) => {
            //     if (!acc[file.checklist_entry_id]) {
            //         acc[file.checklist_entry_id] = [];
            //     }
            //     acc[file.checklist_entry_id].push(file);
            //     return acc;
            // }, {});

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

const loadBrands = async () => {
    const applicationId = page.props.application.id;
    if (!applicationId) {
        errorMessage.value = 'No application ID found in the query.';
        isLoading.value = false;
        return;
    }

    const res = await axios.get(
        // `http://cps.denrcalabarzon.com/api/chainsaw/${applicationId}/brands`
        `http://cps.denrcalabarzon.com/api/chainsaw/${applicationId}/supplier`

    )

    // If data exists, overwrite
    if (res.data.length) {
        suppliers.value = res.data;
    }
}

const getDocumentaryRequirements = async () => {
    const applicationId = page.props.application.id;
    if (!applicationId) return;

    try {
        const response = await axios.get(`http://cps.denrcalabarzon.com/api/getApplicantFile/${applicationId}`);
        if (response.data.status && Array.isArray(response.data.data)) {
            files.value = response.data.data.map((file: any) => ({
                name: file.file_name,
                size: 'Unknown',
                dateUploaded: new Date(file.created_at).toLocaleDateString(),
                dateOpened: new Date().toLocaleDateString(),
                icon: 'png',
                thumbnail: null,
                url: file.file_url,
                isPassed: null, // null = not selected, true = pass, false = fail

            }));
        } else {
            console.log('No files');
        }
    } catch (error) {
        console.error('Failed to fetch files:', error);
    }
};

// const getApplicantFile = async () => {
//     const applicationId = page.props.application.id;
//     if (!applicationId) return;

//     try {
//         const response = await axios.get(`http://cps.denrcalabarzon.com/api/getApplicantFile/${applicationId}`);
//         if (response.data.status && Array.isArray(response.data.data)) {
//             files.value = response.data.data.map((file) => ({
//                 name: file.file_name,
//                 size: 'Unknown',
//                 dateUploaded: new Date(file.created_at).toLocaleDateString(),
//                 dateOpened: new Date().toLocaleDateString(),
//                 icon: 'png',
//                 thumbnail: null,
//                 url: file.file_url,
//             }));
//         }
//     } catch (error) {
//         console.error('Failed to fetch files:', error);
//     }
// };


const openFile = (file) => {
    selectedFile.value = file;
    showFileModal.value = true;

};

const triggerUpdateFile = (file) => {
    selectedFileToUpdate.value = file
    updateFileInput.value.click();
}

const handleFileUpdate = async (event) => {
    const newFile = event.target.files[0]
    if (!newFile || !selectedFileToUpdate.value) return

    try {
        const formData = new FormData()
        formData.append('application_id', selectedFileToUpdate.value.application_id)
        formData.append('file', newFile)
        formData.append('attachment_id', selectedFileToUpdate.value.attachment_id)
        formData.append('name', selectedFileToUpdate.value.name)

        const response = await axios.post('http://cps.denrcalabarzon.com/api/files/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })

        // Update file list
        const updatedIndex = files.value.findIndex(f => f.id === selectedFileToUpdate.value.id)
        if (updatedIndex !== -1) {
            files.value[updatedIndex] = response.data.updatedFile
        }

        toast.add({ severity: 'success', summary: 'Successful', detail: 'File updated successfully', life: 3000 });
        location.reload();

    } catch (error) {
        console.error(error)
        toast.add({ severity: 'error', summary: 'Failed', detail: 'Failed to update the file.', life: 3000 });
    } finally {
        updateFileInput.value.value = '' // reset file input
        selectedFileToUpdate.value = null
    }
}



const companyRequirements = computed(() =>
    assessmentRows.value.filter(r => r.applicant_type === 'company')
);

const getEmbedUrl = (url) => {
    const match = url.match(/[-\w]{25,}/);
    const fileId = match ? match[0] : null;
    return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};
const application_id = getApplicationIdFromUrl();

const handleSupplierSave = async (data) => {
    try {

        const response = await axios.post(
            'http://cps.denrcalabarzon.com/api/chainsaw-permit/store',
            {
                suppliers: data,
                application_id: application_id

            }
        )
        defaultSupplierDialog.value = false



    } catch (error) {


    }

}
const handleApplicationFileUpload = (event: Event, field: 'mayorDTI' | 'affidavit' | 'otherDocs' | 'permit') => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const maxSize = 5 * 1024 * 1024;
    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
        toast.add({ severity: 'warn', summary: 'Invalid File Format', detail: 'Only PDF files allowed' });
        target.value = '';
        return;
    }
    if (file.size > maxSize) {
        toast.add({ severity: 'warn', summary: 'File Too Large', detail: 'Max 5MB' });
        target.value = '';
        return;
    }
    applicationData[field] = file;
};
onMounted(() => {
    if (props.mode === 'view') {
        currentStep.value = 4; // Jump to last step
    }
    // getProvinceCode();
    fetchRoutingHistory();
    getApplicantFile(page.props.application.id);
    loadBrands();
    // getDocumentaryRequirements()
});
</script>
<template>
    <div class="space-y-6">
        <Toast />
        <ReusableConfirmDialog ref="confirmDialogRef" />

        <!-- <LoadingSpinner :loading="isloadingSpinner" /> -->
        <!-- Stepper -->
        <div class="mb-6 flex items-center justify-between" v-if="[1,25].includes(company_form.application_status)">
            <div v-for="step in steps" :key="step.id" class="flex-1 cursor-pointer text-center"
                @click="handleStepClick(step.id)">
                <div :class="[
                    'mx-auto flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white',
                    currentStep === step.id ? 'bg-green-900' : 'bg-gray-300',
                ]">
                    {{ step.id }}
                </div>
                <div class="mt-2 text-sm font-medium"
                    :class="currentStep === step.id ? 'text-green-600' : 'text-gray-500'">
                    {{ step.label }}
                </div>
            </div>
        </div>
        <div v-if="currentStep === 1" class="space-y-6">

            <Chainsaw_applicationField :form="company_form" :insertFormData="insertFormData"
                :app_data="applicationData" />
            <Chainsaw_companyField :form="company_form" :app_data="applicationData" />

            <!-- <Chainsaw_operationField :form="company_form" /> -->
        </div>

        <div v-if="currentStep === 2" class="space-y-6">
            <Fieldset legend="Chainsaw Information" :toggleable="false">


                <Dialog v-model:visible="defaultSupplierDialog" modal header="Default Supplier Form"
                    :style="{ width: '90vw', maxWidth: '1200px' }">


                    <ChainsawSupplierForm @cancel="defaultSupplierDialog = false" @save="handleSupplierSave" />

                </Dialog>

                <div class="relative space-y-6">

                    <!-- <div v-if="isLoading"
                                class="absolute inset-0 bg-white/70 backdrop-blur-sm flex items-center justify-center z-50 rounded-lg">
                                <div class="flex flex-col items-center gap-3">
                                    <LoaderCircle class="h-10 w-10 animate-spin text-green-900" />
                                    <span class="text-green-900 font-semibold text-sm">Saving, please wait...</span>
                                </div>
                            </div> -->
                    <!-- ALERT -->

                    <div :class="{ 'pointer-events-none opacity-60': isLoading }">
                        <div class="pt-6">
                            <div class="gap-6">
                                <div class="flex-1">
                                    <!-- Purpose -->
                                    <Button class="bg-blue-900 hover:bg-blue-700 w-full"
                                        @click="openDefaultSupplierDialog">Chainsaw Supplier Form </Button>

                                    <div class="mt-6">
                                        <FloatLabel>
                                            <Select v-model="applicationData.purpose" :options="purposeOptions"
                                                class="w-full" />
                                            <label>Purpose of Purchase {{ applicationData }}</label>
                                        </FloatLabel>
                                    </div>

                                    <div
                                        v-if="[
                                            'For selling / re-selling',
                                            'Forestry/landscaping service provider'].includes(applicationData.purpose)">
                                        <label class="text-sm font-medium text-gray-700">Upload Mayor's Permit &
                                            DTI
                                            Registration</label>
                                        <div
                                            class="mt-1 w-full h-[330px] border-4 border-dashed border-blue-400 rounded-xl bg-white flex flex-col items-center justify-center cursor-pointer hover:bg-blue-50 transition relative">
                                            <MonitorUp :size="64" class="h-12 w-12 text-blue-400 mb-4" />

                                            <p class="text-center text-gray-700 text-sm mb-2">
                                                Drag & drop files here or click to upload
                                            </p>
                                            <p class="text-center text-gray-400 text-xs">
                                                Allowed: PDF only, max 5 MB
                                            </p>
                                            <input type="file" accept="application/pdf"
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                @change="(e) => handleApplicationFileUpload(e, 'mayorDTI')" />

                                        </div>
                                    </div>


                                    <div v-else-if="['Other legal purpose(s)'].includes(applicationData.purpose)">
                                        <label class="text-sm font-medium text-gray-700">Upload Notarized
                                            Affidavit</label>
                                        <div
                                            class="mt-1 w-full h-[330px] border-4 border-dashed border-blue-400  rounded-xl bg-white flex flex-col items-center justify-center cursor-pointer hover:bg-blue-50 transition relative">
                                            <!-- Upload Icon -->
                                            <MonitorUp :size="64" class="h-12 w-12 text-blue-400 mb-4" />

                                            <!-- Instructions -->
                                            <p class="text-center text-gray-700 text-sm mb-2">
                                                Drag & drop notarized affidavit here or click to upload
                                            </p>
                                            <p class="text-center text-gray-400 text-xs">
                                                Allowed: PDF only, max 10 MB

                                            </p>

                                            <!-- Hidden Input -->

                                            <input type="file" accept="application/pdf"
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                @change="(e) => handleApplicationFileUpload(e, 'affidavit')" />


                                        </div>

                                    </div>

                                    <div v-else-if="['Other Supporting Documents'].includes(applicationData.purpose)">
                                        <label class="text-sm font-medium text-gray-700">Upload Supporting
                                            Documents</label>

                                        <div
                                            class="mt-1 w-full h-[330px] border-4 border-dashed border-blue-400  rounded-xl bg-white flex flex-col items-center justify-center cursor-pointer hover:bg-blue-50 transition relative">
                                            <!-- Upload Icon -->
                                            <MonitorUp :size="64" class="h-12 w-12 text-blue-400 mb-4" />


                                            <!-- Instructions -->
                                            <p class="text-center text-gray-700 text-sm mb-2">
                                                Drag & drop supporting document here or click to upload
                                            </p>
                                            <p class="text-center text-gray-400 text-xs">
                                                Allowed: PDF only, max 5 MB

                                            </p>

                                            <!-- Hidden File Input -->


                                            <input type="file" accept="application/pdf"
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                @change="(e) => handleApplicationFileUpload(e, 'permit')" />


                                        </div>

                                    </div>

                                    <div v-else
                                        class=" w-full mt-4 flex items-center justify-center p-4 border-2 border-gray-300 rounded-xl bg-gray-50 text-gray-600 h-[380px] space-x-2">
                                        <!-- Info Icon -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                        </svg>

                                        <!-- Message -->
                                        <span class="text-sm font-medium">
                                            No additional documents are required for this purpose.
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Fieldset>
        </div>

        <div v-if="currentStep === 3" class="space-y-6">
            <Fieldset legend="Payment of Application Fee" :toggleable="false">
                <!-- <div v-if="isLoading"
                    class="absolute inset-0 bg-white/70 backdrop-blur-sm flex items-center justify-center z-50 rounded-lg">
                    <div class="flex flex-col items-center gap-3">
                        <LoaderCircle class="h-10 w-10 animate-spin text-green-900" />
                        <span class="text-green-900 font-semibold text-sm">Saving, please wait...</span>
                    </div>
                </div> -->
                <div :class="{ 'pointer-events-none opacity-60': isLoading }">

                    <div class="relative">



                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div :hidden="false">
                                <FloatLabel>
                                    <InputText v-model="payment_form.application_no" :disabled=true class="w-full"
                                        style="font-weight: bolder;" />
                                    <label>Application No.</label>
                                </FloatLabel>
                            </div>
                            <div>
                                <FloatLabel>
                                    <InputText class="w-full" v-model="payment_form.official_receipt" />
                                    <label>O.R No.</label>
                                </FloatLabel>
                            </div>
                            <div>
                                <FloatLabel>
                                    <InputNumber class="w-full" v-model="payment_form.permit_fee" />
                                    <label>Permit Fee</label>
                                </FloatLabel>
                            </div>
                            <div class="md:col-span-3">
                                <label class="text-sm font-medium text-gray-700">Upload Scanned copy of Official
                                    Receipt</label>
                                <input type="file" accept=".jpg,.jpeg,.pdf"
                                    @change="(e) => handleORFileUpload(e, 'or_copy')"
                                    class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                            </div>
                        </div>
                    </div>
                </div>
            </Fieldset>
        </div>


        <div v-if="currentStep === 4" class="">
            <div class="relative">
                <!-- YOUR TABLE / CONTENT -->
                <!-- <div v-if="isLoading"
                    class="absolute inset-0 bg-white/70 backdrop-blur-sm flex items-center justify-center z-50 rounded-lg">
                    <div class="flex flex-col items-center gap-3">
                        <LoaderCircle class="h-10 w-10 animate-spin text-green-900" />
                        <span class="text-green-900 font-semibold text-sm">
                            Uploading, please wait...
                        </span>
                    </div>
                </div> -->

                <Fieldset legend="Applicant Details" :toggleable="true">
                    <!-- Applicant Info (non-file fields) -->
                    <!-- <h1 class="font-xl">Below is the checklist of requirements currently pending approval.</h1> -->

                    <div class="relative">

                        <div class="grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                            <div class="flex">
                                <span class="w-48 font-semibold">Application No:</span>
                                <Tag :value="company_form.application_no" severity="success" class="text-center" />
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Application Type:</span>
                                <Tag :value="company_form.application_type" severity="success" class="text-center" />
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Date Applied:</span>
                                <span>{{ company_form.date_applied }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Type of Transaction:</span>
                                <span>{{ company_form.type_of_transaction }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Classification:</span>
                                <span>{{ company_form.classification }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Company Name:</span>
                                <span>{{ company_form.company_name }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Authorized Representative:</span>
                                <span>{{ company_form.authorized_representative }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Region:</span>
                                <span>REGION IV-A (CALABARZON)</span>
                            </div>
                            <!-- <div class="flex">
                        <span class="w-48 font-semibold">Province:</span>
                        <span>{{ company_form.prov_name }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Municipality:</span>
                        <span>Lipa City</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Barangay:</span>
                        <span>Barangay 1</span>
                    </div> -->
                            <div class="flex">
                                <span class="w-48 font-semibold">Complete Address:</span>
                                <span>{{ company_form.company_address }}</span>
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

                                    <Tag v-else-if="item.action == 'Returned to Technical Staff' || item.action == 'Returned to PENRO Technical Staff'"
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
                                        <td class="font-semibold p-2 bg-gray-50">Purpose of Purchase</td>
                                        <td class="p-2">
                                            <ul class="list-disc ml-4">
                                                <li v-for="(supplier, i) in suppliers" :key="i">
                                                    {{ supplier.purpose }}
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>

                                    <tr class="border-b">
                                        <td class="font-semibold p-2 bg-gray-50">Other Details</td>
                                        <td class="p-2">
                                            <ul class="list-disc ml-4">
                                                <li v-for="(supplier, i) in suppliers" :key="i" class="mb-2">
                                                    Covered by Permit to Sell
                                                    <b>{{ supplier.permit_to_sell_no }}</b>
                                                    issued on {{ formatDate(supplier.issued_date) }},
                                                    valid until {{ formatDate(supplier.valid_until) }}
                                                    approved/issued by {{ supplier.issued_by }}
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>

                                    <tr class="border-b">
                                        <td class="font-semibold p-2 bg-gray-50">Official Receipt</td>
                                        <td class="p-2">
                                            <Tag :value="company_form.official_receipt" severity="success" />
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="font-semibold p-2 bg-gray-50">Permit Fee</td>
                                        <td class="p-2">
                                            ₱ {{ company_form.permit_fee }}.00
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ✅ Brands & Models -->
                        <div class="md:col-span-2">
                            <span class="block mb-2 font-semibold">Chainsaw Details:</span>

                            <!-- SUPPLIERS -->
                            <div v-for="(supplier, sIndex) in suppliers" :key="sIndex"
                                class="mb-6 rounded-lg border bg-gray-100 p-4">

                                <!-- Supplier Info -->
                                <div class="mb-3 text-sm">
                                    <div><span class="font-semibold">Supplier:</span> {{ supplier.supplier_name }}
                                    </div>
                                    <div><span class="font-semibold">Permit To Sell:</span> {{
                                        supplier.permit_to_sell_no }}</div>
                                </div>

                                <!-- BRANDS -->
                                <div v-for="(brand, bIndex) in supplier.brands" :key="bIndex"
                                    class="mb-4 rounded-lg border bg-gray-50 p-4">

                                    <div class="mb-2">
                                        <span class="font-semibold">Brand:</span>
                                        <span class="ml-2">{{ brand.name }}</span>
                                    </div>

                                    <!-- MODELS TABLE -->
                                    <table class="w-full text-sm border">
                                        <thead class="bg-blue-900 text-white">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Model</th>
                                                <th class="px-3 py-2 text-left">Serial No</th>
                                                <th class="px-3 py-2 text-center w-32">No. of Units</th>
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

                <AssessmentTable v-if="company_form.application_type === 'Company'"
                    title="Company Applicant Requirements" :collapsed="isCollapsed.value"
                    :application_status="company_form.status_title" :roleId="roleId" :rows="companyRequirements"
                    :onsite="onsite" :company_form="company_form" @view-file="openFileModal"
                    @update-assessment="updateAssessment" @update-remarks="updateRemarks" @update-onsite="updateOnsite"
                    @upload-resubmission="handleResubmissionUpload" @remove-resubmission="handleRemoveResubmission" />

                <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
                    <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.file_url)" width="100%" height="500"
                        allow="autoplay"></iframe>
                </Dialog>
            </div>
        </div>

        <div :class="[
            'pt-6 w-full',
            currentStep > 1
                ? 'grid grid-cols-1 gap-4'
                : 'flex justify-end'
        ]">

            <!-- SHOW RETURN BUTTON IF MAY FAILED -->
            <!-- @click="returnApplication"  -->

            <Button v-if="hasFailed && company_form.status_title !== 'Draft' && currentStep === 4"
                class="w-full h-10 ml-auto px-4 py-2 flex items-center gap-2 rounded-md bg-red-700 text-white hover:bg-red-800"
                @click="() => openReturnDialog(company_form.id)">
                <Undo2 />
                Return Application
            </Button>
            <Button v-else-if="company_form.status_id === 1 || [1, 2, 3].includes(currentStep)"
                class="ml-auto bg-blue-900 w-full" @click="nextStep">
                <Send />
                Save as Draft
            </Button>
            <!-- <Button
                v-else-if="company_form.status_title == 'Draft' || currentStep == 1 || currentStep === 2 || currentStep === 3"
                class="ml-auto bg-green-900 w-full" @click="submitApplication">
                <Send />
                Submit Application
            </Button> -->

            <!-- OTHERWISE SHOW ASSESSMENT MODAL -->
            <!-- ="currentStep === 4" -->
            <AssessmentModal :status_id="company_form.application_status" class="w-full"
                :applicationId="Number(page.props.application.id)" @submit-assessments="submitAllAssessments" />


            <!-- DRAFT SUBMIT -->


        </div>

        <Dialog v-model:visible="isLoading" modal :closable="false" :draggable="false" :style="{ width: '300px' }">
            <div class="flex flex-col items-center gap-4 py-4">
                <span>Saving, please wait...</span>
                <ProgressBar mode="indeterminate" style="width: 100%; height: 6px" />
            </div>
        </Dialog>
    </div>
</template>

<style>
/* HTML:  */
.ribbon {
    font-weight: bold;
    color: #fff;
}

.ribbon {
    --f: 0.5em;
    /* control the folded part */
    z-index: 10;
    /* ensure it's on top */
    font-size: 16px;
    /* or adjust as needed */
    position: absolute;
    top: 0;
    right: 0;
    line-height: 1.8;
    padding-inline: 1lh;
    padding-bottom: var(--f);
    border-image: conic-gradient(#0008 0 0) 51% / var(--f);
    clip-path: polygon(100% calc(100% - var(--f)),
            100% 100%,
            calc(100% - var(--f)) calc(100% - var(--f)),
            var(--f) calc(100% - var(--f)),
            0 100%,
            0 calc(100% - var(--f)),
            999px calc(100% - var(--f) - 999px),
            calc(100% - 999px) calc(100% - var(--f) - 999px));
    transform: translate(calc((1 - cos(45deg)) * 100%), -100%) rotate(45deg);
    transform-origin: 0% 100%;
    background-color: #bd1550;
    /* the main color  */
}

.file-preview {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #2563eb;
    /* blue */
    font-weight: 500;
    text-decoration: none;
    transition: color 0.3s ease;
}

.file-preview:hover {
    color: #1e40af;
    /* darker blue */
    text-decoration: underline;
}

.file-icon {
    width: 30px;
    height: 40px;
    object-fit: contain;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #f9f9f9;
    padding: 4px;
}

.file-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}

.container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.file-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    width: 100%;
}
</style>