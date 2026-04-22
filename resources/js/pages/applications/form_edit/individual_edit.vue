<script setup lang="ts">
import { ref, watch, reactive, onMounted, toRaw, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AssessmentTable from './assessment_tbl.vue';
// UI & Icons

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import FloatLabel from 'primevue/floatlabel';
import DatePicker from 'primevue/datepicker';
import Fieldset from 'primevue/fieldset';
import { LoaderCircle, Send, Undo2, Trash2, CirclePlus, MonitorUp } from 'lucide-vue-next';
import Chainsaw_operationField from './chainsaw_operationField.vue';
import chainsaw_individualInfoField from '../forms/chainsaw_individualInfoField.vue';
import FileCard from '../forms/file_card.vue';
import ConfirmModal from '../modal/confirmation_modal.vue';
import AssessmentModal from '../modal/assessment_modal.vue';
import ReusableConfirmDialog from '../modal/endorsed_modal.vue';
import ChainsawSupplierForm from '@/components/ChainsawSupplierForm.vue';

// Composables
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';
import { useApi } from '@/composables/useApi';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Tag from 'primevue/tag';

const defaultSupplierDialog = ref(false);



// State
const props = defineProps({
    application: Object,
    mode: String,
});

const isCollapsed = ref(true)
const isRoutingCollapsed = ref(true)
const isChainsawInfoCollapsed = ref(true)
const toast = useToast();
const { createChainsaw, individual_form, chainsaw_form, payment_form } = useAppForm();
const page = usePage();

// Merge incoming application props into individual_form (if you want to prefill)
Object.assign(individual_form, page.props.application || {});
Object.assign(chainsaw_form, page.props.application || {});
Object.assign(payment_form, page.props.application || {});
const confirmDialogRef = ref<any>(null);

const { insertFormData, updateFormData } = useFormHandler();
const { getProvinceCode, getApplicationNumber, prov_name } = useApi();
const isLoading = ref(false);
const isloadingSpinner = ref(false);

const applicationData = ref<any>({});
const files = ref<any[]>([]);
const assessmentRows = ref([])
const routingHistory = ref([]);

const i_city_mun = ref<number | string>(0);
const errorMessage = ref('');
const currentStep = ref(4);

// IMPORTANT: initialize chainsaws correctly using createChainsaw()
// Use ref (so handlers calling chainsaws.value.push(...) work)
const chainsaws = ref<ReturnType<typeof createChainsaw>[]>([createChainsaw()]);
const is_not_compliance = ref();
const is_compliance = ref();
const userId = page.props.auth?.user?.id ?? null;
const roleId = page.props.auth?.user?.role_id;
const officeId = page.props.auth?.user?.office_id;

const selectedFile = ref(null);
const showModal = ref(false);
const selectedFileToUpdate = ref(null)
const updateFileInput = ref(null)

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


const updateAssessment = (checklist_entry_id, assessment) => {
    const row = individualRequirements.value.find(
        r => r.checklist_entry_id === checklist_entry_id
    );
    if (row) {
        row.assessment = assessment;
        row.is_saved = false; // unlock save again if changed
    }
};

const updateRemarks = (checklist_entry_id, remarks) => {
    const row = individualRequirements.value.find(
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

const sendEmail = async () => {
    try {
        const response = await axios.post('/api/send-email', {
            email: 'kimsacluti10101996@gmail.com',
            applicant_name: individual_form.first_name + "" + individual_form.middle_name + "" + individual_form.last_name,
            address: individual_form.i_complete_address,
            application_no: individual_form.application_no
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
    if (roleId !== 1 && roleId !== 4) {
        const incomplete = individualRequirements.value.some(row => !row.assessment);

        if (incomplete) {
            alert('Please complete all assessments before submitting.');
            return;
        }
    }

    const workflowType = roleId === 4 ? 'implementing_agency' : 'smooth';

    // ✅ SAME LOGIC ADDED
    const isARDTSD = roleId === 11;
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
            assessments: individualRequirements.value.map(row => ({
                permit_checklist_id: row.permit_checklist_id,
                assessment: row.assessment,
                remarks: row.remarks
            })),
            onsite: {
                findings: onsite.value.findings,
                recommendations: onsite.value.recommendations
            }
        });

        // ✅ EMAIL TRIGGER (copied logic)
        if (isARDTSD && isEndorsingToRD) {
            await sendEmail();
        }
        const redirectMap = {
            2: '/rps-chief-dashboard',
            3: '/cenro-dashboard',
            4: '/penro-technical-dashboard',
            5: '/penro-rps-chief-dashboard',
            6: '/penro-tsd-chief-dashboard',
            7: '/penro-dashboard',
            8: '/rts-dashboard',
            9: '/fus-dashboard',
            10: '/lpdd-chief-dashboard',
            11: '/ardts-dashboard',
            12: '/fus-dashboard',
        };

        const redirectPath = redirectMap[roleId];

        if (redirectPath) {
            setTimeout(() => {
                router.visit(redirectPath);
            }, 1000);
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

// Laravel-ready payload
const getPayload = () => {
    return {
        brand: brand.value,
        models: models.value.map(m => m.model)
    }
}
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
const hasFailed = computed(() => {
    return individualRequirements.value.some(r => r.assessment === 'failed')
})

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: '2-digit'
    });
};
// ─────────────────────────────────────────────────────────────
// STEPPER
// ─────────────────────────────────────────────────────────────
const steps = ref([
    { label: 'Applicant Form', id: 1 },
    { label: 'Permit to Sell Chainsaw', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 },
]);
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

        const response = await axios.post('https://cps.denrcalabarzon.com/api/files/update', formData, {
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

const handleResubmissionUpload = async (checklistId: number, files: File[]) => {
    try {
        isLoading.value = true; // ✅ SHOW LOADING OVERLAY

        const formData = new FormData();
        files.forEach(file => formData.append('files[]', file));
        formData.append('uploaded_by', userId);
        formData.append('checklist_entry_id', checklistId.toString());
        formData.append('application_no', individual_form.application_no);
        formData.append('application_id', page.props.application.id);

        // Example API endpoint
        const response = await axios.post('/api/resubmit-files', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        // Assume response returns the uploaded files with timestamps
        const uploadedFiles = response.data.files; // [{file_name, uploaded_at}, ...]

        // Find the row and push new resubmissions
        const row = individualRequirements.value.find(r => r.checklist_entry_id === checklistId);
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
    const row = individualRequirements.find(r => r.checklist_entry_id === checklistId);
    if (!row || !row.resubmissions[index]) return;

    // Optional: call API to remove file from server
    // await axios.delete(`/api/remove-resubmission/${row.resubmissions[index].id}`);

    // Remove from array
    row.resubmissions.splice(index, 1);
};
const formValidationRules = {
    1: {
        form: 'individual_form',
        fields: [
            'date_applied',
            'application_type',
            'type_of_transaction',
            'geo_code',
            'last_name',
            'first_name',
            'sex',
        ],
        labels: {
            date_applied: 'Date Applied',
            application_type: 'Application Type',
            type_of_transaction: 'Type of Transaction',
            geo_code: 'Geo Code',
            last_name: 'Last Name',
            first_name: 'First Name',
            sex: 'Sex',
        },
    },

    2: {
        form: 'chainsaw_form',
        fields: [
            'permit_validity',
            'permit_chainsaw_no',
            'brand',
            'model',
            'quantity',
            'supplier_name',
            'supplier_address',
            'purpose',
        ],
        labels: {
            permit_validity: 'Permit Validity',
            permit_chainsaw_no: 'Permit Chainsaw No',
            brand: 'Brand',
            model: 'Model',
            quantity: 'Quantity',
            supplier_name: 'Supplier Name',
            supplier_address: 'Supplier Address',
            purpose: 'Purpose',
        },
    },
    3: {
        form: 'payment_form',
        fields: ['official_receipt', 'permit_fee', 'date_of_payment'],
        labels: {
            official_receipt: 'Official Receipt',
            permit_fee: 'Permit Fee',
            date_of_payment: 'Date of Payment',
        },
    },
};
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
                    assessments: individualRequirements.value.map(row => ({
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
// -------------------------
// Individual Form Validation
// -------------------------
const validateForm = () => {
    const stepRules = formValidationRules[currentStep.value];

    if (!stepRules || !stepRules.fields || stepRules.fields.length === 0) return true;

    let formToCheck: any[] = [];

    // Determine which form to validate
    if (stepRules.form === 'individual_form') {
        formToCheck = [individual_form];
    } else if (stepRules.form === 'chainsaw_form') {
        formToCheck = chainsaws.value;
    } else if (stepRules.form === 'payment_form') {
        formToCheck = [payment_form];
    }

    const missingFields: string[] = [];

    formToCheck.forEach((form, index) => {
        stepRules.fields.forEach((field) => {
            if (form[field] === '' || form[field] === null || form[field] === undefined) {
                const label = stepRules.labels[field] || field;
                if (formToCheck.length > 1) {
                    missingFields.push(`${label} (Chainsaw ${index + 1})`);
                } else {
                    missingFields.push(label);
                }
            }
        });
    });

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

const openFileModal = (file: any) => {
    selectedFile.value = file;
    showModal.value = true;
};

const handleORFileUpload = (event: Event, field: string) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        (payment_form as any)[field] = target.files[0];
    }
};

// -------------------------
// Next Step Logic
// -------------------------
const saveCompanyApplication = async () => {
    isLoading.value = true;
    isloadingSpinner.value = false;

    const formData = new FormData();


    try {
        const response = await insertFormData(
            'https://cps.denrcalabarzon.com/api/chainsaw/company_apply',
            {
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

    const applicationId = getApplicationIdFromUrl();

    try {
        const formData = new FormData();

        formData.append('id', page.props.application.id);
        formData.append('applicant_type', applicationData.value.application_type);
        formData.append('supplier_name', applicationData.value.supplier_name);
        formData.append('supplier_address', applicationData.value.supplier_address);
        formData.append('purpose', applicationData.value.purpose);
        formData.append('permit_validity', formatDate(applicationData.value.validity_date));
        formData.append('permit_chainsaw_no', applicationData.value.permit_chainsaw_no);
        // formData.append('other_details', applicationData.value.other_details);
        formData.append('uploaded_by', userId);
        formData.append('issued_date', formatDate(applicationData.value.issued_date));
        // formData.append('validity_date', formatDate(applicationData.value.validity_date));
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
            'https://cps.denrcalabarzon.com/api/chainsaw/insertChainsawInfo',
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
const nextStep = async () => {
    // Prevent advancing past the last step
    if (currentStep.value > steps.value.length) return;

    // Show loading state
    isLoading.value = true;

    // Handlers for each step
    const handlers: Record<number, Function> = {
        1: saveCompanyApplication,
        2: submitChainsawForm,
        3: submitORPayment,
        4: saveAsDraft
    };


    const handler = handlers[currentStep.value];

    if (handler) {
        const isSaved = await handler();

        if (!isSaved) {
            isLoading.value = false;
            return;
        }

        // Refresh application details after saving
        await getApplicationDetails();

        if (!applicationData.value || !applicationData.value.application_no) {
            console.error('Application details missing after save. Step will not advance.');
            isLoading.value = false;
            return;
        }
    }

    // Mark step as completed
    if (!completedSteps.value.includes(currentStep.value)) {
        completedSteps.value = [...completedSteps.value, currentStep.value]
    }

    // Move to next step
    currentStep.value++;
    isLoading.value = false;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

// ─────────────────────────────────────────────────────────────
// FORM SUBMISSION
// ─────────────────────────────────────────────────────────────

const saveIndividualApplication = async () => {
    isLoading.value = true;
    const applicationId = page.props.application.id;

    try {
        const response = await axios.put(`/applications/${applicationId}/update-applicant-data`, {
            application_type: 'Individual',
            last_name: individual_form.last_name,
            first_name: individual_form.first_name,
            middle_name: individual_form.middle_name,
            type_of_transaction: individual_form.type_of_transaction,
            date_applied: individual_form.date_applied,
            gov_id_number: individual_form.gov_id_number,
            government_id: individual_form.gov_id_type,
            sex: individual_form.sex,
            applicant_contact_details: individual_form.mobile_no,
            applicant_telephone_no: individual_form.telephone_no,
            applicant_email_address: individual_form.email_address,
            applicant_province_c: individual_form.i_province,
            applicant_city_mun_c: individual_form.i_city_mun,
            applicant_brgy_c: individual_form.i_barangay,
            applicant_complete_address: individual_form.i_complete_address,
            encoded_by: userId,
        });

        if (response.data.status === 'success') {
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Individual application updated successfully.', life: 3000 });
            return true;
        } else {
            toast.add({ severity: 'warn', summary: 'No Changes', detail: response.data.message, life: 3000 });
            return false;
        }
    } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Failed', detail: error.message || 'Error saving the application.', life: 3000 });
        return false;
    } finally {
        isLoading.value = false;
    }
};

const updateChainsawInfo = async (chainsawForm) => {
    isLoading.value = true;
    const applicationId = page.props.application.id;
    try {

        const response = await axios.put(
            `/applications/${applicationId}/update-chainsaw-info`, {
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

        if (response.data.status === 'success') {
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Chainsaw Information updated successfully.', life: 3000 });
            return true;
        } else {
            toast.add({ severity: 'warn', summary: 'No Changes', detail: response.data.message, life: 3000 });
            return false;
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to update chainsaw info',
            life: 4000,
        });
        return null;
    }
};


const submitORPayment = async () => {
    isLoading.value = true;
    const applicationId = page.props.application.id;
    try {
        const response = await axios.put(
            `/applications/${applicationId}/update-payment-info`, {
            official_receipt: payment_form.official_receipt,
            permit_fee: payment_form.permit_fee,
            or_copy: payment_form.or_ccopy,
            application_id: applicationId,
            application_no: payment_form.application_no,
        });

        if (response.data.status === 'success') {
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Payment Information updated successfully.', life: 3000 });
            return true;
        } else {
            toast.add({ severity: 'warn', summary: 'No Changes', detail: response.data.message, life: 3000 });
            return false;
        }

        return true;
    } catch (error) {
        console.error('Failed to save payment details:', error);
        toast.add({ severity: 'error', summary: 'Failed', detail: 'There was an error saving the application.', life: 3000 });
        return false;
    } finally {
        isLoading.value = false;
    }
};

const getApplicationIdFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('application_id') || urlParams.get('id');
};

const application_id = getApplicationIdFromUrl();


const getApplicationDetails = async () => {
    const applicationId = getApplicationIdFromUrl();
    if (!applicationId) {
        errorMessage.value = 'No application ID found in the query.';
        isLoading.value = false;
        return;
    }

    try {
        const response = await axios.get(`https://cps.denrcalabarzon.com/api/getApplicationDetails/${applicationId}`);
        applicationData.value = response.data.data ?? {};
        i_city_mun.value = response.data.data?.i_city_mun ?? i_city_mun.value;
    } catch (error: any) {
        errorMessage.value = error.message || 'Error fetching application data.';
    } finally {
        isLoading.value = false;
    }
};

const getDocumentaryRequirements = async () => {
    const applicationId = page.props.application.id;
    if (!applicationId) return;

    try {
        const response = await axios.get(`https://cps.denrcalabarzon.com/api/getApplicantFile/${applicationId}`);
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
const loadBrands = async () => {
    const applicationId = page.props.application.id;
    if (!applicationId) {
        errorMessage.value = 'No application ID found in the query.';
        isLoading.value = false;
        return;
    }

    const res = await axios.get(
        `https://cps.denrcalabarzon.com/api/chainsaw/${applicationId}/supplier`
    )

    // If data exists, overwrite
    if (res.data.length) {
        suppliers.value = res.data;
    }
}

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



const individualRequirements = computed(() =>
    assessmentRows.value.filter(
        r => r.application_type === 'Individual'
    )
)




const getEmbedUrl = (url: string) => {
    const match = url.match(/[-\w]{25,}/);
    const fileId = match ? match[0] : null;
    return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};

// ─────────────────────────────────────────────────────────────
// CHAINSaw Section
// ─────────────────────────────────────────────────────────────

const addChainsaw = () => {
    chainsaws.value.push(createChainsaw());
};

const removeChainsaw = (index: number) => {
    if (chainsaws.value.length > 1) chainsaws.value.splice(index, 1);
};

const copyAllFields = (index: number) => {
    if (chainsaws.value[index].copyAll && index > 0) {
        chainsaws.value[index] = {
            ...chainsaws.value[0],
            copyAll: true,
            letterRequest: null,
        };
    }
};

const handleFileUpload = (event: Event, index: number, field = 'letterRequest') => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    (chainsaws.value[index] as any)[field] = file;
};

// ─────────────────────────────────────────────────────────────
// PURPOSE Section
// ─────────────────────────────────────────────────────────────
const purpose = ref({
    purpose: '',
    purposeFiles: {
        mayorDTI: null,
        affidavit: null,
        otherDocs: null,
    },
});


const handlePurposeFileUpload = (event: Event, field: string) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    (purpose.value.purposeFiles as any)[field] = file;
};

const isStepValid = (stepId: number) => true;

const handleStepClick = (targetStep: number) => {
    if (targetStep <= currentStep.value || isStepValid(currentStep.value)) {
        currentStep.value = targetStep;
    } else {
        showError();
    }
};

const showError = () => {
    toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please complete all required fields before proceeding.', life: 3000 });
};

const purposeOptions = [
    'For cutting of trees with legal permit',
    'For Post-calamity cleaning operations',
    'For farm lot/tree orchard maintenance',
    'For cutting-trimming of trees posing danger within a private property',
    'For selling / re-selling',
    'For cutting of trees to be used for house repair or perimeter fencing',
    'Forestry/landscaping service provider',
    'Other Legal Purpose',
    'Other Supporting Documents',
];

const getDocumentTitle = (fileName?: string) => {
    if (!fileName) return '';
    const name = fileName.toLowerCase();
    if (name.startsWith('permit_')) return 'Permit to Purchase / Chainsaw Permit';
    if (name.startsWith('mayors_')) return 'Mayor’s Permit';
    if (name.startsWith('notarized_')) return 'Notarized Application Form';
    if (name.startsWith('official_')) return 'Official Receipt';
    if (name.startsWith('request_')) return 'Request Letter';
    if (name.startsWith('secretary_')) return 'Secretary’s Certificate';
    return 'Supporting Document';
};

const getFileType = (fileName?: string) => {
    if (!fileName) return '';
    return fileName.split('.').pop()?.toLowerCase() ?? '';
};
const isPassed = ref(null); // null = not selected, true = pass, false = fail

const setStatus = (status) => {
    if (status === 'pass') {
        isPassed.value = true;
    } else if (status === 'fail') {
        isPassed.value = false;
    }
    console.log('Selected status:', isPassed.value ? 'Pass' : 'Failed');
};
const completedSteps = ref<number[]>([]);
const stepClass = (stepId: number) => {
    if (stepId > currentStep.value && !completedSteps.value.includes(stepId - 1)) {
        // Locked future step
        return 'cursor-not-allowed opacity-40';
    }
    // Current step or previous steps
    return 'cursor-pointer';
};
const stepCircleClass = (stepId: number) => {
    if (completedSteps.value.includes(stepId)) return 'bg-green-600';
    if (currentStep.value === stepId) return 'bg-green-900';
    return 'bg-gray-300';
};
const handleSupplierSave = async (data) => {

    try {

        const response = await axios.post(
            'https://cps.denrcalabarzon.com/api/chainsaw-permit/store',
            {
                suppliers: data,
                application_id: page.props.application.id
            }
        )

    } catch (error) {
        console.log(error)

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
function openDefaultSupplierDialog() { defaultSupplierDialog.value = true; }


onMounted(() => {
    console.log(individual_form.status_title);
    if (props.mode === 'view') {
        currentStep.value = 4;
    }
    fetchRoutingHistory()
    // getProvinceCode();
    getApplicantFile(page.props.application.id);
    loadBrands();
    // getDocumentaryRequirements()
});
</script>



<template>
    <div class="space-y-6">
        <ReusableConfirmDialog ref="confirmDialogRef" />

        <Toast />
        <!-- Stepper -->
        <div class="mb-6 flex items-center justify-between" v-if="individual_form.status_title === 'Draft'">
            <div v-for="step in steps" :key="step.id" class="flex-1 text-center" :class="stepClass(step.id)"
                @click="handleStepClick(step.id)">
                <!-- Step circle -->
                <div :class="[
                    'mx-auto flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white',
                    stepCircleClass(step.id)
                ]">
                    {{ step.id }}
                </div>

                <!-- Step label -->
                <div class="mt-2 text-sm font-medium"
                    :class="currentStep === step.id ? 'text-green-600' : 'text-gray-500'">
                    {{ step.label }}
                </div>
            </div>
        </div>



        <div v-if="currentStep === 1" class="space-y-4">

            <chainsaw_individualInfoField :form="individual_form" :insertFormData="insertFormData"
                :getProvinceCode="getProvinceCode" :city_mun="i_city_mun" :prov_name="prov_name" />
        </div>

        <div v-if="currentStep === 2" class="space-y-4">
            <Fieldset legend="Chainsaw Information" :toggleable="false">


                <Dialog v-model:visible="defaultSupplierDialog" modal header="Default Supplier Form"
                    :style="{ width: '90vw', maxWidth: '1200px' }">


                    <ChainsawSupplierForm @cancel="defaultSupplierDialog = false" @save="handleSupplierSave" />

                </Dialog>

                <div class="relative space-y-6">

                    <div v-if="isLoading"
                        class="absolute inset-0 bg-white/70 backdrop-blur-sm flex items-center justify-center z-50 rounded-lg">
                        <div class="flex flex-col items-center gap-3">
                            <LoaderCircle class="h-10 w-10 animate-spin text-green-900" />
                            <span class="text-green-900 font-semibold text-sm">Saving, please wait...</span>
                        </div>
                    </div>
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
                                            <label>Purpose of Purchase</label>
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
                <div v-if="isLoading"
                    class="absolute inset-0 bg-white/70 backdrop-blur-sm flex items-center justify-center z-50 rounded-lg">
                    <div class="flex flex-col items-center gap-3">
                        <LoaderCircle class="h-10 w-10 animate-spin text-green-900" />
                        <span class="text-green-900 font-semibold text-sm">Saving, please wait...</span>
                    </div>
                </div>
                <div :class="{ 'pointer-events-none opacity-60': isLoading }">

                    <div class="relative">



                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div :hidden="false">
                                <FloatLabel>
                                    <InputText v-model="individual_form.application_no" :disabled=true class="w-full"
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

        <div v-if="currentStep === 4" class="space-y-6">
            <Fieldset legend="Applicant Details" :toggleable="true">
                <!-- Applicant Info (non-file fields) -->
                <!-- <h1 class="font-xl">Below is the checklist of requirements currently pending approval.</h1> -->
                <div class="relative">

                    <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="flex">
                            <span class="w-48 font-semibold">Application No:</span>
                            <Tag :value="individual_form.application_no" severity="success" class="text-center" />
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Application Type:</span>
                            <Tag :value="individual_form.application_type" severity="success" class="text-center" />
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Date Applied:</span>
                            <span>{{ individual_form.date_applied }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Type of Transaction:</span>
                            <span>{{ individual_form.type_of_transaction }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Classification:</span>
                            <span>{{ individual_form.classification }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Contact Details:</span>
                            <span>{{ individual_form.mobile_no }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Applicant Name:</span>
                            <span>{{ individual_form.first_name }} {{ individual_form.middle_name }} {{
                                individual_form.last_name }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Email Address:</span>
                            <span>{{ individual_form.email_address }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Region:</span>
                            <span>REGION IV-A (CALABARZON)</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Complete Address:</span>
                            <span>{{ individual_form.i_complete_address }}</span>
                        </div>
                    </div>
                </div>
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
                                 <span v-if="[1,3,5,7, 9, 11, 13, 15, 17, 19, 21,23,25,27,29,31].includes(item.route_order)">
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
                                
                                <span v-if="[2,4,6,8,10,12,14,16,18,20,22,24,26,28,30].includes(item.route_order)">
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
                                <tr class="border-b">
                                    <td class="w-56 font-semibold p-2 bg-gray-50">Supplier Name</td>
                                    <td class="p-2">
                                        <ul class="list-disc ml-4">
                                            <li v-for="(supplier, i) in suppliers" :key="i">
                                                {{ supplier.supplier_name }}
                                            </li>
                                        </ul>
                                    </td>
                                </tr>

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
                                                <b>{{ supplier.permit_chainsaw_no }}</b>
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
                                        <Tag :value="individual_form.official_receipt" severity="success" />
                                    </td>
                                </tr>

                                <tr>
                                    <td class="font-semibold p-2 bg-gray-50">Permit Fee</td>
                                    <td class="p-2">
                                        ₱ {{ individual_form.permit_fee }}.00
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
                                            <td>{{ supplier.issued_date }}</td>
                                            <td>{{ supplier.valid_until }}</td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>

                        </div>
                    </div>

                </div>
            </Fieldset>


            <AssessmentTable v-if="individual_form.application_type === 'Individual'"
                title="Company Applicant Requirements" :collapsed="isCollapsed.value"
                :application_status="individual_form.status_title" :roleId="roleId" :rows="individualRequirements"
                :onsite="onsite" :company_form="individual_form" @view-file="openFileModal"
                @update-assessment="updateAssessment" @update-remarks="updateRemarks" @update-onsite="updateOnsite"
                @upload-resubmission="handleResubmissionUpload" @remove-resubmission="handleRemoveResubmission" />







            <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
                <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.file_url)" width="100%" height="500"
                    allow="autoplay"></iframe>
            </Dialog>

        </div>


        <div :class="[
            'pt-6 w-full',
            currentStep > 1
                ? 'grid grid-cols-1 gap-4'
                : 'flex justify-end'
        ]">

            <!-- SHOW RETURN BUTTON IF MAY FAILED -->
            <!-- @click="returnApplication"  -->

            <Button v-if="hasFailed && individual_form.status_title !== 'Draft' && currentStep === 4"
                class="w-full h-10 ml-auto px-4 py-2 flex items-center gap-2 rounded-md bg-red-700 text-white hover:bg-red-800"
                @click="() => openReturnDialog(individual_form.id)">
                <Undo2 />
                Return Application
            </Button>
            <Button v-else-if="individual_form.status_id === 1 || [1, 2, 3].includes(currentStep)"
                class="ml-auto bg-blue-900 w-full h-[250]" @click="nextStep">
                <Send />
                Save as Draft
            </Button>

            <!-- OTHERWISE SHOW ASSESSMENT MODAL -->
            <!-- ="currentStep === 4" -->
            <AssessmentModal v-if="individual_form.status_title != 'Draft' || currentStep === 4"
                :status_id="individual_form.status_id" class="w-full" :applicationId="Number(page.props.application.id)"
                @submit-assessments="submitAllAssessments" />


            <!-- DRAFT SUBMIT -->


        </div>
    </div>
</template>

<style>
/* HTML: <div class="ribbon">Your text content</div> */
.ribbon {
    font-size: 19px;
    font-weight: bold;
    color: #fff;
}

.ribbon {
    --r: .8em;
    /* control the cutout */
    margin-left: 934px;
    margin-top: -20px;
    position: relative;
    border-block: .5em solid #0000;
    padding-inline: calc(var(--r) + .25em) .5em;
    line-height: 1.8;
    clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, var(--r) calc(100% - .25em), 0 50%, var(--r) .25em);
    background:
        radial-gradient(.2em 50% at right, #000a, #0000) border-box,
        #BD1550 padding-box;
    /* the color  */
    width: fit-content;
}


/* Optional: smooth transition when switching colors */
button {
    transition: background-color 0.2s ease;
}

.table-container {
    max-width: 800px;
    margin: 20px auto;
}

.chainsaw-table {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
}

.chainsaw-table th,
.chainsaw-table td {
    border: 1px solid #ddd;
    padding: 10px;
}

.chainsaw-table th {
    background-color: #f3f4f6;
    font-weight: bold;
}

.input {
    width: 100%;
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.actions {
    text-align: center;
}

.btn {
    padding: 6px 10px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.btn.add {
    background-color: #16a34a;
    color: #fff;
}

.btn.remove {
    background-color: #dc2626;
    color: #fff;
    margin-left: 5px;
}

.btn:hover {
    opacity: 0.85;
}
</style>
