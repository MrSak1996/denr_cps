<script setup lang="ts">

/* -------------------------------------------------------
| 1. IMPORTS
------------------------------------------------------- */
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

import Dialog from 'primevue/dialog';
import Fieldset from 'primevue/fieldset';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';

import { LoaderCircle,Send, ShieldAlert, Trash2, CirclePlus, MonitorUp, Info } from 'lucide-vue-next';

/* Custom */
import { useApi } from '@/composables/useApi';
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';
import { submitChainsawForm } from '@/lib/chainsaw';

import ConfirmModal from '../modal/confirmation_modal.vue';
import ChainsawSupplierForm from '@/components/ChainsawSupplierForm.vue';
import ChainsawMMSSForm from '@/components/ChainsawMMSSForm.vue';
import ChainsawMMMSForm from '@/components/ChainsawMMMSForm.vue';

import Chainsaw_applicationField from './chainsaw_applicationField.vue';
import Chainsaw_companyField from './chainsaw_companyField.vue';
import Chainsaw_operationField from './chainsaw_operationField.vue';
import FileCard from './file_card.vue';

import Toast from 'primevue/toast';
import FloatLabel from 'primevue/floatlabel';
import Tag from 'primevue/tag';
import Checkbox from '../../../components/ui/checkbox/Checkbox.vue';
import ProgressBar from 'primevue/progressbar';

import { Button } from '@/components/ui/button';

/* -------------------------------------------------------
| 2. PROPS
------------------------------------------------------- */
const props = defineProps({
    application: Object,
    mode: String
});


/* -------------------------------------------------------
| 3. COMPOSABLES
------------------------------------------------------- */
const { company_form, chainsaw_form, payment_form } = useAppForm();
const { insertFormData } = useFormHandler();
const { getApplicationNumber } = useApi();

const toast = useToast();
const page = usePage();

const userId = page.props.auth?.user?.id;
const roleId = page.props.auth?.user?.role_id;

/* -------------------------------------------------------
| 4. STATE
------------------------------------------------------- */
const currentStep = ref(1);
const completedSteps = ref<number[]>([]);

const isLoading = ref(false);
const progress = ref(0);
const isloadingSpinner = ref(false);

const showPrivacyDialog = ref(false);
const hasAgreedPrivacy = ref(false);

const selectedFile = ref(null);
const selectedFileToUpdate = ref(null)
const updateFileInput = ref(null)

const showModal = ref(false);

const files = ref([]);
const applicationData = ref([]);
const errorMessage = ref('');

const chainsawCollapsed = ref(true);

const chainsaws = reactive([{ ...JSON.parse(JSON.stringify(chainsaw_form)) }]);

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
]);

/* Dialogs */
const defaultSupplierDialog = ref(false);
const singleSupplierDialog = ref(false);
const multiSupplierDialog = ref(false);

/* Chainsaw Info */
const ChainsawInfo = ref({
    permit_validity: null
});

/* -------------------------------------------------------
| 5. CONSTANTS / OPTIONS
------------------------------------------------------- */
const purposeOptions = ref([
    'For cutting of trees with legal permit',
    'For post-calamity clearing operations',
    'For farm lot/tree orchard maintenance',
    'For maintenance of trees/vegetation within private property',
    'For cutting/trimming of trees posing danger within a private property',
    'For selling / re-selling',
    'For cutting of trees to be used for house repair/perimeter fencing/residential area development',
    'For commercial use',
    'Forestry/landscaping service provider',
    'Other legal purpose(s)',
    'Other Supporting Documents'
]);

/* Form Validation Rules */
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
            'brand',
            'model',
            'quantity',
            'supplier_name',
            'supplier_address',
            'purpose'
        ],
        labels: {
            permit_validity: 'Permit Validity',
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

/* Stepper */
const steps = ref([
    { label: 'Applicant Details', id: 1 },
    { label: 'Permit to Sell Chainsaw', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 }
]);

/* -------------------------------------------------------
| 6. UTILITY FUNCTIONS
------------------------------------------------------- */
const getApplicationIdFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('application_id') || urlParams.get('id');
};

const application_id = getApplicationIdFromUrl();

const saveCompanyApplication = async () => {
    isLoading.value = true;
    isloadingSpinner.value = false;

    const formData = new FormData();
    formData.append('request_letter', company_form.request_letter);
    formData.append('soc_certificate', company_form.soc_certificate);

    try {
        const response = await insertFormData(
            'http://localhost:8000/api/chainsaw/company_apply',
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

    const applicationId = getApplicationIdFromUrl();

    try {
        const formData = new FormData();

        formData.append('id', applicationId);
        formData.append('applicant_type', applicationData.value.application_type);
        formData.append('supplier_name', applicationData.value.supplier_name);
        formData.append('supplier_address', applicationData.value.supplier_address);
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
            'http://localhost:8000/api/chainsaw/insertChainsawInfo',
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


const submitORPayment = async () => {
    isLoading.value = true;
    isloadingSpinner.value = true;

    const applicationId = getApplicationIdFromUrl();
    const urlParams = new URLSearchParams(window.location.search);
    const application_type = urlParams.get('type');
    const formData = new FormData();
    formData.append('id', applicationId);
    formData.append('applicant_type', application_type);
    formData.append('official_receipt', payment_form.official_receipt);
    formData.append('permit_fee', payment_form.permit_fee);
    formData.append('application_no', applicationData.value.application_no);
    formData.append('or_copy', payment_form.or_copy);
    formData.append('uploaded_by', userId);

    try {
        const response = await axios.post(
            'http://localhost:8000/api/chainsaw/insert_payment',
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

        const newApplicationId = response.data.application_id;

        router.get(
            route('applications.index', {
                application_id: newApplicationId,
                type: 'company',
                step: 4
            })
        );

        return true;
    } catch (error) {
        console.error('Failed to save payment details:', error);

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
const formatDate = (date: Date | string | null) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toISOString().split('T')[0]; // YYYY-MM-DD
};

const getEmbedUrl = (url: string) => {
    const match = url.match(/[-\w]{25,}/);
    const fileId = match ? match[0] : null;
    return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};

/* -------------------------------------------------------
| 7. STEP NAVIGATION
------------------------------------------------------- */
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

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}
/* -------------------------------------------------------
| 8. FORM VALIDATION
------------------------------------------------------- */
const validateForm = () => {
    const stepRules = formValidationRules[currentStep.value];
    if (!stepRules || !stepRules.fields?.length) return true;

    let formToCheck = [];
    if (stepRules.form === 'company_form') formToCheck = [company_form];
    if (stepRules.form === 'chainsaw_form') formToCheck = chainsaws;
    if (stepRules.form === 'payment_form') formToCheck = [payment_form];

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
        toast.add({ severity: 'warn', summary: 'Incomplete Fields', detail: `Please fill out the following fields: ${missingFields.join(', ')}`, life: 5000 });
        return false;
    }
    return true;
};

/* -------------------------------------------------------
| 9. FILE HANDLERS
------------------------------------------------------- */
/* -------------------------------------------------------
| STEP NAVIGATION
------------------------------------------------------- */
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

const handleStepClick = (targetStep: number) => {

    // ✅ Always allow going back
    if (targetStep < currentStep.value) {
        currentStep.value = targetStep
        return
    }

    // ✅ Allow staying on same step
    if (targetStep === currentStep.value) return

    // ❌ Prevent skipping steps
    if (targetStep > currentStep.value + 1) {
        toast.add({
            severity: 'warn',
            summary: 'Step Locked',
            detail: 'Please complete the current step first.',
            life: 3000
        })
        return
    }

    // ✅ Allow moving forward only if current step is completed
    if (completedSteps.value.includes(currentStep.value)) {
        currentStep.value = targetStep
    } else {
        toast.add({
            severity: 'warn',
            summary: 'Incomplete Step',
            detail: 'Please save this step first.',
            life: 3000
        })
    }
}


const showError = () => { toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please complete all required fields before proceeding.', life: 3000, }); };
const isStepValid = (stepId) => {
    return true;
};
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

const openFileModal = (file: any) => {
    selectedFile.value = file;
    showModal.value = true;
};
const triggerUpdateFile = (file) => {
    selectedFileToUpdate.value = file
    updateFileInput.value.click()
}
const handleFileUpdate = async (event) => {
    const newFile = event.target.files[0]
    if (!newFile || !selectedFileToUpdate.value) return

    try {
        const formData = new FormData()
    const applicationId = getApplicationIdFromUrl();

        formData.append('application_id', applicationId)
        formData.append('file', newFile)
        formData.append('attachment_id', selectedFileToUpdate.value.attachment_id)
        formData.append('name', selectedFileToUpdate.value.name)

        const response = await axios.post('http://localhost:8000/api/files/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })

        // Update file list
        const updatedIndex = files.value.findIndex(f => f.id === selectedFileToUpdate.value.id)
        if (updatedIndex !== -1) {
            files.value[updatedIndex] = response.data.updatedFile
        }

        toast.add({ severity: 'success', summary: 'Successful', detail: 'File updated successfully', life: 3000 });

    } catch (error) {
        console.error(error)
        toast.add({ severity: 'error', summary: 'Successful', detail: 'Failed to update the file.', life: 3000 });

    } finally {
        updateFileInput.value.value = '' // reset file input
        selectedFileToUpdate.value = null
    }
}

/* -------------------------------------------------------
| 10. API CALLS / INITIALIZATION
------------------------------------------------------- */
const getApplicationDetails = async () => {
    const applicationId = getApplicationIdFromUrl();
    try {
        const response = await axios.get(`http://localhost:8000/api/getApplicationDetails/${applicationId}`);
        applicationData.value = response.data.data || [];
    } catch (error: any) {
        errorMessage.value = error.message || 'Error fetching application data.';
    } finally {
        isLoading.value = false;
    }
};

const getApplicantFile = async () => {
    const applicationId = getApplicationIdFromUrl();
    if (!applicationId) return;

    try {
        const response = await axios.get(`http://localhost:8000/api/getApplicantFile/${applicationId}`);
        if (response.data.status && Array.isArray(response.data.data)) {
            files.value = response.data.data.map((file) => ({
                name: file.file_name,
                size: 'Unknown',
                dateUploaded: new Date(file.created_at).toLocaleDateString(),
                dateOpened: new Date().toLocaleDateString(),
                icon: 'png',
                thumbnail: null,
                url: file.file_url,
                attachment_id: file.attachment_id
            }));
        }
    } catch (error) {
        console.error('Failed to fetch files:', error);
    }
};

const handleSupplierSave = async (data) => {
    try {

        const response = await axios.post(
            'http://localhost:8000/api/chainsaw-permit/store',
            {
                suppliers: data,
                application_id: application_id

            }
        )
        defaultSupplierDialog.value = false



    } catch (error) {


    }

}

/* -------------------------------------------------------
| 11. PRIVACY CONSENT
------------------------------------------------------- */
const acceptPrivacy = async () => {
    hasAgreedPrivacy.value = true;
    showPrivacyDialog.value = false;
    sessionStorage.setItem('chainsaw_privacy', 'true');

    if (!company_form.application_no) {
        await getApplicationNumber(company_form, chainsaw_form);
    }
    await initializeApplication();
};

const initializeApplication = async () => {
    const today = new Date();
    const nextYear = new Date(today.setFullYear(today.getFullYear() + 1));
    ChainsawInfo.value.permit_validity = nextYear.toISOString().substring(0, 10);

    const urlParams = new URLSearchParams(window.location.search);
    currentStep.value = Number(urlParams.get('step')) || 1;

    await getApplicationDetails();
    await loadBrands();
    await getApplicantFile();
};

/* -------------------------------------------------------
| 12. CHAINSWAW / SUPPLIER HANDLERS
------------------------------------------------------- */
const loadBrands = async () => {
    const applicationId = getApplicationIdFromUrl();
    if (!applicationId) return;

    const res = await axios.get(`http://localhost:8000/api/chainsaw/${applicationId}/supplier`);
    if (res.data.length) suppliers.value = res.data;
};

const addChainsaw = () => {
    chainsaws.push(JSON.parse(JSON.stringify(chainsaw_form)));
};

const removeChainsaw = (index: number) => {
    if (chainsaws.length > 1) chainsaws.splice(index, 1);
};

const copyAllFields = (index: number) => {
    if (chainsaws[index].copyAll && index > 0) {
        const first = chainsaws[0];
        chainsaws[index] = { ...first, copyAll: true, letterRequest: null };
    }
};

const handlePurposeFileUpload = (event: any, fieldName: string, index: number) => {
    chainsaws[index][fieldName] = event.target.files[0];
};

const handleORFileUpload = (event: any, field: string) => {
    payment_form[field] = event.target.files[0];
};

/* -------------------------------------------------------
| 13. DIALOG HELPERS
------------------------------------------------------- */
function openDefaultSupplierDialog() { defaultSupplierDialog.value = true; }
function openSingleSupplierDialog() { singleSupplierDialog.value = true; }
function openMultiSupplierDialog() { multiSupplierDialog.value = true; }

/* -------------------------------------------------------
| 14. LIFECYCLE
------------------------------------------------------- */
onMounted(async () => {
    if (props.mode === 'view') {
        currentStep.value = 4;
        return;
    }

    const applicationId = getApplicationIdFromUrl();
    const consent = sessionStorage.getItem('chainsaw_privacy');

    if (applicationId || consent === 'true') {
        hasAgreedPrivacy.value = true;
        await initializeApplication();
    } else {
        showPrivacyDialog.value = true;
    }
});

</script>
<template>
    <div class="space-y-6">
        <div :class="{ 'pointer-events-none blur-sm': showPrivacyDialog }">
            <Toast />
            <!-- Stepper -->
            <div class="mb-6 flex items-center justify-between">
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

            <div v-if="currentStep === 1" class="space-y-6">
                <!-- <div v-if="isLoading"
                        class="absolute inset-0 bg-white/70 backdrop-blur-sm flex items-center justify-center z-50 rounded-lg">
                        <div class="flex flex-col items-center gap-3">
                            <LoaderCircle class="h-10 w-10 animate-spin text-green-900" />
                            <span class="text-green-900 font-semibold text-sm">Saving, please wait...</span>
                        </div>
                    </div> -->

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

                                        <div
                                            v-else-if="['Other Supporting Documents'].includes(applicationData.purpose)">
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
                                        <InputText v-model="applicationData.application_no" :disabled=true
                                            class="w-full" style="font-weight: bolder;" />
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

                    <div class="relative">


                        <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                            <div class="flex">
                                <span class="w-48 font-semibold">Application No:</span>
                                <Tag :value="applicationData.application_no" severity="success" class="text-center" />
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Application Type:</span>
                                <Tag :value="applicationData.application_type" severity="success" class="text-center" />
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Date Applied:</span>
                                <span>{{ applicationData.date_applied }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Type of Transaction:</span>
                                <span>{{ applicationData.type_of_transaction }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Classification:</span>
                                <span>{{ applicationData.classification }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Company Name:</span>
                                <span>{{ applicationData.company_name }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Authorized Representative:</span>
                                <span>{{ applicationData.authorized_representative }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Region:</span>
                                <span>REGION IV-A (CALABARZON)</span>
                            </div>

                            <div class="flex">
                                <span class="w-48 font-semibold">Complete Address:</span>
                                <span>{{ applicationData.company_address }}</span>
                            </div>
                            <!-- <div class="flex">
                            <span class="w-48 font-semibold">Place of Operation Address:</span>
                            <span>{{ applicationData.operation_complete_address }}</span>
                        </div> -->
                        </div>
                    </div>
                </Fieldset>

                <Fieldset legend="Chainsaw Information" :toggleable="true">
                    <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="md:col-span-2">

                            <table class="w-full border border-gray-300 text-sm">
                                <tbody>

                                    <tr class="border-b">
                                        <td class="font-semibold p-2 bg-gray-50">Purpose of Purchase</td>
                                        <td class="p-2">
                                            <span>{{ applicationData.purpose }}</span>
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
                                            <Tag :value="applicationData.official_receipt" severity="success" />
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="font-semibold p-2 bg-gray-50">Permit Fee</td>
                                        <td class="p-2">
                                            ₱ {{ applicationData.permit_fee }}.00
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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
                                                <td>{{ formatDate(supplier.issued_date)}}</td>
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
                            <FileCard v-for="(file, index) in files" :key="index" :file="file"
                                @openPreview="openFileModal" @updateFile="triggerUpdateFile"/>

                        </div>
                    </div>
            <input type="file" ref="updateFileInput" class="hidden" @change="handleFileUpdate" />


                    <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
                        <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500"
                            allow="autoplay"></iframe>
                    </Dialog>
                </Fieldset>
            </div>

            <div :class="[
                'pt-6 w-full',
                currentStep > 1
                    ? 'grid grid-cols-2 gap-4'
                    : 'flex justify-end'
            ]">
                <!-- Back Button -->
                <Button v-if="currentStep > 1 && currentStep !== 4" @click="prevStep" variant="outline"
                    class="w-full bg-gray-300 hover:bg-gray-400 transition-colors">
                    Back
                </Button>

                <!-- Save as Draft -->
                <Button v-if="currentStep <= 4" :disabled="isLoading" @click="nextStep"
                    class="w-full ml-auto bg-blue-900 w-full h-[250]">
                    <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin mr-2" />
                    <Send />

                    Save as Draft
                </Button>

                <!-- Step 4 Submit -->

                <ConfirmModal v-if="currentStep === 4" :currentStep="currentStep" class="w-full"
                    :applicationId="Number(application_id)" :role_id="roleId" />
            </div>

        </div>
    </div>
    <Dialog header="Privacy Consent" v-model:visible="showPrivacyDialog" modal :closable="false" :draggable="false"
        :style="{ width: '500px' }">
        <div class="space-y-4 text-sm text-gray-700">
            <p>
                In compliance with the <b>Data Privacy Act of 2012 (RA 10173)</b>,
                we collect and process your personal information solely for the purpose
                of processing your Chainsaw Purchase System.
            </p>

            <p>
                Your data will be treated confidentially and will not be shared
                without your consent unless required by law.
            </p>

            <div class="flex items-start gap-2 mt-4">
                <Checkbox v-model="hasAgreedPrivacy" binary />
                <label class="text-sm">
                    I have read and agree to the Data Privacy Policy.
                </label>
            </div>
        </div>

        <template #footer>
            <Button label="Decline" class="p-button-text"
                @click="router.get(route('applications.index'))">Decline</Button>

            <Button label="Agree & Continue" :disabled="!hasAgreedPrivacy" class="bg-green-900 text-white"
                @click="acceptPrivacy">Agree & Continue</Button>

        </template>
    </Dialog>
    <Dialog v-model:visible="isLoading" modal :closable="false" :draggable="false" :style="{ width: '300px' }">
        <div class="flex flex-col items-center gap-4 py-4">
            <span>Saving, please wait...</span>
            <ProgressBar mode="indeterminate" style="width: 100%; height: 6px" />
        </div>
    </Dialog>


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
