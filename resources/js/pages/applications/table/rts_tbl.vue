<script setup lang="ts">
import { router, usePage, Link } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';
import { SaveAll, SquarePen, View, PrinterCheck, Eye, BadgeCheck, SendIcon, History, Import, Undo2, CirclePlus } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref, computed } from 'vue';
import FileCard from '../forms/file_card.vue';
import Badge from 'primevue/badge';
import { Text } from 'vue';
import Timeline from 'primevue/timeline';
import { ProductService } from '../service/ProductService';
import { savePurpose } from '../service/applicationApi.js'

import OverlayBadge from 'primevue/overlaybadge';
import ReusableConfirmDialog from '../modal/endorsed_modal.vue';
import Toast from 'primevue/toast';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import Button from 'primevue/button';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Row from 'primevue/row';
const page = usePage();
onMounted(() => {
    applicantsTable();
    approvedApplicants();

});

const STATUS_DRAFT = 1;
const STATUS_FOR_REVIEW_EVALUATION = 2;
const STATUS_ENDORSED_CENRO_RPS_CHIEF = 3;
const STATUS_ENDORSED_CENRO_OFFICER = 4;
const STATUS_ENDORSED_PENRO_TECHNICAL = 5;
const STATUS_ENDORSED_PENRO_CHIEF_RPS = 6;
const STATUS_ENDORSED_PENRO_CHIEF_TSD = 7;
const STATUS_ENDORSED_PENRO_OFFICER = 8;
const STATUS_ENDORSED_REGIONAL_TECHNICAL_STAFF = 9;
const STATUS_ENDORSED_FUS_CHIEF = 10;
const STATUS_ENDORSED_LPDD_CHIEF = 11;
const STATUS_ENDORSED_ARDTS = 12;
const STATUS_ENDORSED_RED = 13;
const STATUS_RECEIVED_CENRO_RPS_CHIEF = 14;
const STATUS_RECEIVED_CENRO_OFFICER = 15;
const STATUS_RECEIVED_PENRO_TECHNICAL = 16;
const STATUS_RECEIVED_PENRO_CHIEF_RPS = 17;
const STATUS_RECEIVED_PENRO_CHIEF_TSD = 18;
const STATUS_RECEIVED_PENRO_OFFICER = 19;
const STATUS_RECEIVED_REGIONAL_TECHNICAL_STAFF = 20;
const STATUS_RECEIVED_FUS_CHIEF = 21;
const STATUS_RECEIVED_LPDD_CHIEF = 22;
const STATUS_RECEIVED_ARDTS = 23;
const STATUS_RECEIVED_RED = 24;
const STATUS_RETURNED_TO_CENRO_TECHNICAL = 25;
const STATUS_RETURNED_TO_PENRO_TECHNICAL = 26;
const STATUS_RETURNED_TO_REGIONAL_TECHNICAL = 27;
const STATUS_APPROVED_BY_RED = 28;


const auth = computed(() => page.props.auth);
const roleId = auth.value.user?.role_id;


const toast = useToast();
const dt = ref();
const totalCount = ref(0);
const returnedTotalCount = ref(0);
const endorsedTotalCount = ref(0);
const approvedTotalCount = ref(0);

const displayedCount = ref(totalCount.value);
const displayApprovedCount = ref(approvedTotalCount.value);

const products = ref();
const returned_application = ref();
const approved_application = ref();
const endorsed_application = ref();
const productDialog = ref(false);
const deleteProductDialog = ref(false);
const deleteProductsDialog = ref(false);
const isloadingSpinner = ref(false);
const showModal = ref(false);
const showProgressModal = ref(false)

const showCommentsModal = ref(false);
const commentsHistory = ref(false);
const routingHistory = ref([]);
const loadingRouting = ref(false);
const loadingComment = ref(false);
const showFileModal = ref(false);
const selectedFile = ref(null);
const selectedFileToUpdate = ref(null);
const updateFileInput = ref(null);
const showReturnFieldset = ref(false);
const returnReason = ref(''); // Stores the user's input for the return reason
const isSubmitting = ref(false);
const product = ref({});
const selectedProducts = ref();
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    office_id: { value: null, matchMode: FilterMatchMode.IN },
   applicant_name: {
        value: null,
        matchMode: FilterMatchMode.CONTAINS
    },
    application_type: {
        value: null,
        matchMode: FilterMatchMode.CONTAINS
    },

    application_status: {
        value: null,
        matchMode: FilterMatchMode.CONTAINS
    }
});


const officeGroups = {
    // PENRO
    1: [1],                   // PENRO Cavite
    2: [2, 6],                // PENRO Laguna + CENRO Sta. Cruz
    3: [3, 7, 8],             // PENRO Batangas + CENROs
    4: [4],                   // PENRO Rizal
    5: [5, 9, 10, 11, 12],    // PENRO Quezon + CENROs

    // CENRO
    6: [6],
    7: [7],
    8: [8],
    9: [9],
    10: [10],
    11: [11],
    12: [12],

    // Regional Office
    13: [13]
};

const selectedOffice = ref(null);
const onOfficeChange = ({ value }) => {
    filters.value.office_id.value =
        value == null ? null : (officeGroups[value] ?? [value]);
};

const submitted = ref(false);
const statuses = ref([
    { label: 'INSTOCK', value: 'instock' },
    { label: 'LOWSTOCK', value: 'lowstock' },
    { label: 'OUTOFSTOCK', value: 'outofstock' },
]);

const officeOptions = [
    { label: 'PENRO CAVITE', value: 1 },
    { label: 'PENRO LAGUNA', value: 2 },
    { label: 'PENRO BATANGAS', value: 3 },
    { label: 'PENRO RIZAL', value: 4 },
    { label: 'PENRO QUEZON', value: 5 },

    { label: 'CENRO Sta. Cruz', value: 6 },
    { label: 'CENRO Lipa City', value: 7 },
    { label: 'CENRO Calaca', value: 8 },
    { label: 'CENRO Calauag', value: 9 },
    { label: 'CENRO Catanauan', value: 10 },
    { label: 'CENRO Tayabas', value: 11 },
    { label: 'CENRO Real', value: 12 },

    { label: 'Regional Office', value: 13 },
]

const applicationTypeOptions = [
    { label: 'Individual', value: 'Individual' },
    { label: 'Company', value: 'Company' },
    { label: 'Government', value: 'Government' },
]

const statusOptions = [
    { label: 'Draft', value: 1 },
    { label: 'For Review / Evaluation', value: 2 },
    { label: 'Endorsed to CENRO RPS Chief', value: 3 },
    { label: 'Endorsed to CENRO Officer', value: 4 },
    { label: 'Endorsed to PENRO Technical', value: 5 },
    { label: 'Endorsed to PENRO Chief RPS', value: 6 },
    { label: 'Endorsed to PENRO Chief TSD', value: 7 },
    { label: 'Endorsed to PENRO Officer', value: 8 },
    { label: 'Endorsed to Regional Technical Staff', value: 9 },
    { label: 'Endorsed to FUS Chief', value: 10 },
    { label: 'Endorsed to LPDD Chief', value: 11 },
    { label: 'Endorsed to ARDTS', value: 12 },
    { label: 'Endorsed to Regional Executive Director', value: 13 },

    { label: 'Received by CENRO RPS Chief', value: 14 },
    { label: 'Received by CENRO Officer', value: 15 },
    { label: 'Received by PENRO Technical', value: 16 },
    { label: 'Received by PENRO Chief RPS', value: 17 },
    { label: 'Received by PENRO Chief TSD', value: 18 },
    { label: 'Received by PENRO Officer', value: 19 },
    { label: 'Received by Regional Technical Staff', value: 20 },
    { label: 'Received by FUS Chief', value: 21 },
    { label: 'Received by LPDD Chief', value: 22 },
    { label: 'Received by ARDTS', value: 23 },
    { label: 'Received by Regional Executive Director', value: 24 },

    { label: 'Returned to CENRO Technical Staff', value: 25 },
    { label: 'Returned to PENRO Technical Staff', value: 26 },
    { label: 'Returned to Regional Technical Staff', value: 27 },

    { label: 'Approved by Regional Executive Director', value: 28 },
]

// Define steps
const events = [
    'Return for Compliance',
    'For Review / Evaluation',
    'Endorsed to CENRO',
    'Endorsed to PENRO',
    'Endorsed to R.O',
    'Approved'

];

const timelineItems = ref(events);
const eventsToDisplay = ref([]);
const confirmDialogRef = ref<any>(null);

// Example: Current step (0-based index)
const currentStep = ref(0); // "Endorsed to PENRO"
const openProgressTracker = async (data) => {
    showProgressModal.value = true;
    loadingRouting.value = true;
    routingHistory.value = [];

    try {
        const res = await axios.get(`/api/application-routing/${data.id}`);
        routingHistory.value = res.data;
    } catch (error) {
        console.error(error);
    } finally {
        loadingRouting.value = false;
    }

    if (data.application_status === 0) {
        eventsToDisplay.value = [
            'Return for Compliance',
            'For Review / Evaluation',
            'Endorsed to CENRO',
            'Endorsed to PENRO',
            'Endorsed to R.O',
            'Approved'
        ];

        // Status 0 matches perfectly, no offset needed
        currentStep.value = data.application_status;
    } else {
        // Status > 0 → exclude "Return for Compliance"
        eventsToDisplay.value = [
            'For Review / Evaluation',
            'Endorsed to CENRO',
            'Endorsed to PENRO',
            'Endorsed to R.O',
            'Approved'
        ];

        // Adjust the index by subtracting 1 because we removed "Return for Compliance"
        currentStep.value = data.application_status - 1;
    }
};

const receiveApplication = (id: number) => {
    confirmDialogRef.value?.open({
        header: "Receive Application?",
        message: "Please confirm that you want to receive this application.",
        onConfirm: async () => {
            try {
                await axios.post(route("applications.penro.receive"), { id });
                toast.add({ severity: "success", summary: "Received", detail: "Application received" });
            } catch {
                toast.add({ severity: "error", summary: "Error", detail: "Failed to receive" });
            }
        }
    });
};

const endorseApplication = (id: number) => {
    confirmDialogRef.value?.open({
        header: "Endorse Application?",
        message: "Please confirm that you want to endorse this application.",
        onConfirm: async () => {
            try {
                await axios.post(route("applications.endorseToFUS"), { id, status: 4 });
                toast.add({ severity: "success", summary: "Endorsed", detail: "Application endorsed" });
            } catch {
                toast.add({ severity: "error", summary: "Error", detail: "Failed to endorse" });
            }
        }
    });
};


// Computed properties
const currentStepLabel = computed(() => events[currentStep.value]);
const progressPercentage = computed(() => Math.round(((currentStep.value + 1) / events.length) * 100));

const badgeSeverity = computed(() => {
    switch (currentStepLabel.value) {
        case 'Approved':
            return 'success';
        case 'Return for Compliance':
            return 'danger';
        default:
            return 'info';
    }
});

const activeTab = ref<'re' | 'rc' | 'cpr' | 'aa'>('re');

const applicationDetails = ref(null);
const files = ref([]);

const formatCurrency = (value) => {
    if (value) return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    return;
};

const applicantsTable = async () => {
    try {
        const officeId = page.props.auth.user.office_id;
        const { applications: endorsedApplications, count: endorsedCount } = await ProductService.getApplicationsByStatus(STATUS_ENDORSED_REGIONAL_TECHNICAL_STAFF, officeId);

        endorsed_application.value = endorsedApplications;
        totalCount.value = endorsedCount;
        displayedCount.value = totalCount.value;


    } catch (error) {
        console.error('Error fetching applications:', error);
    }
};
const approvedApplicants = async () => {
    try {
        const officeId = page.props.auth.user.office_id;
        const { applications: approvedApplications, count: approvedCount } = await ProductService.getApplicationsByStatus(STATUS_APPROVED_BY_RED, officeId);

        approved_application.value = approvedApplications;
        approvedTotalCount.value = approvedCount;
        displayApprovedCount.value = approvedTotalCount.value;


    } catch (error) {
        console.error('Error fetching applications:', error);
    }
}

    ;

const handleReturnReasonClick = async () => {
    if (!returnReason.value.trim()) {
        toast.add({
            severity: 'error',
            summary: 'Return Application',
            detail: 'Please enter a reason for returning.',
            life: 3000,
        });
        return;
    }

    try {
        isSubmitting.value = true;

        const response = await axios.post(`/applications/return`, {
            application_id: applicationDetails.value.id,
            reason: returnReason.value,
        });

        if (response.data.status === 'success') {
            toast.add({
                severity: 'success',
                summary: 'Return Application',
                detail: 'Reason saved successfully!',
                life: 3000,
            });
            showReturnFieldset.value = false; // hide fieldset after saving
            returnReason.value = ''; // reset input
        } else {
            toast.add({
                severity: 'error',
                summary: 'Return Application',
                detail: response.data.message || 'Something went wrong.',
                life: 3000,
            });
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Return Application',
            detail: 'Failed to save return reason.',
            life: 3000,
        });
    } finally {
        isSubmitting.value = false;
    }
};



const hideDialog = () => {
    productDialog.value = false;
    submitted.value = false;
};

const saveProduct = () => {
    submitted.value = true;

    if (product?.value.name?.trim()) {
        if (product.value.id) {
            product.value.inventoryStatus = product.value.inventoryStatus.value ? product.value.inventoryStatus.value : product.value.inventoryStatus;
            products.value[findIndexById(product.value.id)] = product.value;
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Updated', life: 3000 });
        } else {
            product.value.id = createId();
            product.value.code = createId();
            product.value.image = 'product-placeholder.svg';
            product.value.inventoryStatus = product.value.inventoryStatus ? product.value.inventoryStatus.value : 'INSTOCK';
            products.value.push(product.value);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Created', life: 3000 });
        }

        productDialog.value = false;
        product.value = {};
    }
};


const deleteProduct = () => {
    products.value = products.value.filter((val) => val.id !== product.value.id);
    deleteProductDialog.value = false;
    product.value = {};
    toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Deleted', life: 3000 });
};

const findIndexById = (id) => {
    let index = -1;
    for (let i = 0; i < products.value.length; i++) {
        if (products.value[i].id === id) {
            index = i;
            break;
        }
    }

    return index;
};

const createId = () => {
    let id = '';
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    for (var i = 0; i < 5; i++) {
        id += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return id;
};

const openFile = (file) => {
    selectedFile.value = file;
    showFileModal.value = true;
};

const editState = reactive({
    applicant: false,
    chainsaw: false,
});

const editableDetails = reactive({ ...applicationDetails.value });

const getEmbedUrl = (url) => {
    const match = url.match(/[-\w]{25,}/);
    const fileId = match ? match[0] : null;
    return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};

const editableApplicant = reactive({});
const editableChainsaw = reactive({});

// =================================
// APPLICATION DATA
// Author: Mark Kim A. Sacluti
// Date: August 01, 2024
// =================================

const getApplicantFile = async (id) => {
    try {
        const response = await axios.get(`https://cps.denrcalabarzon.com/api/getApplicantFile/${id}`);
        if (response.data.status && Array.isArray(response.data.data)) {
            files.value = response.data.data.map((file) => ({
                attachment_id: file.id,
                application_id: file.application_id,
                name: file.file_name,
                size: 'Unknown',
                dateUploaded: new Date(file.created_at).toLocaleDateString(),
                dateOpened: new Date().toLocaleDateString(),
                icon: 'png',
                thumbnail: null,
                url: file.file_url,
            }));
        }
    } catch (error) {
        console.error('Failed to fetch files:', error);
    }
};

const getApplicationDetails = async (id) => {
    isloadingSpinner.value = true;
    try {
        const response = await axios.get(`https://cps.denrcalabarzon.com/api/getApplicationDetails/${id}`);
        applicationDetails.value = response.data.data;
        await getApplicantFile(id);
        return response.data.data;
    } catch (error) {
        console.error(error);
    } finally {
        isloadingSpinner.value = false;
    }
};

const editProduct = (product) => {
    // Example: go to /applications/123/edit
    router.visit(`/applications/${product.id}/edit`);
};

// =============================
// Separate Update Functions
// =============================

// Update only Applicant Details
const saveApplicantDetails = async () => {
    try {
        isloadingSpinner.value = true;

        const response = await axios.put(`https://cps.denrcalabarzon.com/api/updateApplicantDetails/${applicationDetails.value.id}`, editableApplicant);

        if (response.data.status === 'success') {
            toast.add({
                severity: 'success',
                summary: 'Saved',
                detail: 'Applicant details updated successfully.',
                life: 3000,
            });
            applicationDetails.value = { ...applicationDetails.value, ...editableApplicant };
            editState.applicant = false;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to save applicant details.',
                life: 3000,
            });
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred while saving applicant details.',
            life: 3000,
        });
    } finally {
        isloadingSpinner.value = false;
    }
};

// Update only Chainsaw Information
const saveChainsawDetails = async () => {
    try {
        isloadingSpinner.value = true;

        const response = await axios.put(`https://cps.denrcalabarzon.com/api/updateChainsawInformation/${applicationDetails.value.id}`, editableChainsaw);

        if (response.data.status === 'success') {
            toast.add({
                severity: 'success',
                summary: 'Saved',
                detail: 'Chainsaw information updated successfully.',
                life: 3000,
            });
            applicationDetails.value = { ...applicationDetails.value, ...editableChainsaw };
            editState.chainsaw = false;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to save chainsaw details.',
                life: 3000,
            });
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred while saving chainsaw details.',
            life: 3000,
        });
    } finally {
        isloadingSpinner.value = false;
    }
};

// =============================
// Toggle Edit States
// =============================
const toggleApplicantEdit = () => {
    if (editState.applicant) {
        saveApplicantDetails();
    } else {
        Object.assign(editableApplicant, {
            application_no: applicationDetails.value.application_no,
            date_applied: applicationDetails.value.date_applied,
            application_type: applicationDetails.value.application_type,
            company_name: applicationDetails.value.company_name,
            authorized_representative: applicationDetails.value.authorized_representative,
            company_address: applicationDetails.value.company_address,
        });
        editState.applicant = true;
    }
};

const toggleChainsawEdit = () => {
    if (editState.chainsaw) {
        saveChainsawDetails();
    } else {
        Object.assign(editableChainsaw, {
            permit_chainsaw_no: applicationDetails.value.permit_chainsaw_no,
            permit_validity: applicationDetails.value.permit_validity,
            brand: applicationDetails.value.brand,
            model: applicationDetails.value.model,
            quantity: applicationDetails.value.quantity,
        });
        editState.chainsaw = true;
    }
};

const handleEndorseApplicationStatus = async () => {
    try {
        isloadingSpinner.value = true;

        // Send PUT request to update the application status to 'endorsed'
        const response = await axios.put(`https://cps.denrcalabarzon.com/api/updateApplicationStatus/${applicationDetails.value.id}`, {
            status: 2, //ENDORSED Only update the status field
        });

        if (response.data.status === 'success') {
            toast.add({
                severity: 'success',
                summary: 'Application Endorsed',
                detail: 'The application status has been updated to Endorsed.',
                life: 3000,
            });

            // Update the local application details to reflect the change
            applicationDetails.value.status = 'endorsed';
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update the application status.',
                life: 3000,
            });
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred while updating the status.',
            life: 3000,
        });
    } finally {
        isloadingSpinner.value = false;
    }
};

const triggerUpdateFile = (file) => {
    selectedFileToUpdate.value = file;
    updateFileInput.value.click();
};

const handleFileUpdate = async (event) => {
    const newFile = event.target.files[0];
    if (!newFile || !selectedFileToUpdate.value) return;

    try {
        const formData = new FormData();
        formData.append('application_id', selectedFileToUpdate.value.application_id);
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

        alert('File updated successfully!');
    } catch (error) {
        console.error(error);
        alert('Failed to update the file.');
    } finally {
        updateFileInput.value.value = ''; // reset file input
        selectedFileToUpdate.value = null;
    }
};

const openDialog = (type: 'endorse' | 'return' | 'receive', id: number) => {
    const office_id = page.props.auth.user.office_id;
    const user_id = page.props.auth.user.id;
    const role_id = page.props.auth.user.role_id;

    const config = {
        endorse: {
            header: 'Endorse this application to LPDD/FUS?',
            message: 'Please confirm that you want to endorse this application.',
            api: 'applications.penro.endorse',
            payload: { id },
            showTextarea: false,
            showDropdown: false,
            toastMessage: 'Application endorsed',
        },
        return: {
            header: 'Return Application?',
            message: 'Please indicate the reason and office to return this application.',
            api: 'applications.penro.return',
            payload: { id },
            showTextarea: true,
            showDropdown: true,
            toastMessage: 'Application returned',
            offices: [
                { label: 'CENRO Officer', value: 4 },
            ],
        },
        receive: {
            header: 'Receive Application?',
            message: 'Please confirm that you want to receive this application.',
            api: 'applications.fus.receive',
            payload: { id, office_id, user_id, role_id },
            showTextarea: false,
            showDropdown: false,
            toastMessage: 'Application received',
        },
    };

    const c = config[type];
    confirmDialogRef.value?.open({
        header: c.header,
        message: c.message,
        showTextarea: c.showTextarea,
        showDropdown: c.showDropdown,
        offices: c.offices,
        onConfirm: async (data?: { remarks?: string; returnTo?: string | number }) => {
            try {
                // ✅ send remarks and returnTo along with payload
                await axios.post(route(c.api), {
                    ...c.payload,
                    remarks: data?.remarks,
                    returnTo: data?.returnTo,
                });

                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: c.toastMessage,
                    life: 3000,
                });
                setTimeout(() => {
                    router.visit('/dashboard/rts');
                }, 1000);
            } catch (error) {
                console.log(error);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Something went wrong',
                    life: 3000,
                });
            }
        },
    });
};

const openCommentModal = async (data) => {
    showCommentsModal.value = true;
    loadingComment.value = true;
    try {
        const res = await axios.get(`/api/getCommentsByID/${data.id}`);
        commentsHistory.value = res.data;
    } catch (error) {
        console.error(error);
    } finally {
        loadingComment.value = false;
    }
};
const buttonState = (row: any) => {
    const isReceived = row.application_status === STATUS_RECEIVED_REGIONAL_TECHNICAL_STAFF ||
        row.application_status === STATUS_RETURNED_TO_CENRO_TECHNICAL ||
        row.application_status === STATUS_RETURNED_TO_PENRO_TECHNICAL ||
        row.application_status === STATUS_RECEIVED_CENRO_RPS_CHIEF ||
        row.application_status === STATUS_RECEIVED_CENRO_OFFICER ||
        row.application_status === STATUS_RECEIVED_PENRO_TECHNICAL ||
        row.application_status === STATUS_RECEIVED_PENRO_CHIEF_RPS ||
        row.application_status === STATUS_RECEIVED_PENRO_CHIEF_TSD ||
        row.application_status === STATUS_RECEIVED_PENRO_OFFICER ||
        row.application_status === STATUS_RECEIVED_FUS_CHIEF ||
        row.application_status === STATUS_RECEIVED_LPDD_CHIEF ||
        row.application_status === STATUS_ENDORSED_CENRO_RPS_CHIEF ||
        row.application_status === STATUS_ENDORSED_CENRO_OFFICER ||
        row.application_status === STATUS_ENDORSED_PENRO_TECHNICAL ||
        row.application_status === STATUS_ENDORSED_PENRO_CHIEF_RPS ||
        row.application_status === STATUS_ENDORSED_PENRO_CHIEF_TSD ||
        row.application_status === STATUS_ENDORSED_PENRO_OFFICER ||
        row.application_status === STATUS_ENDORSED_FUS_CHIEF ||
        row.application_status === STATUS_ENDORSED_LPDD_CHIEF ||
        row.application_status === STATUS_ENDORSED_ARDTS ||
        row.application_status === STATUS_ENDORSED_RED ||
        row.application_status === STATUS_RECEIVED_ARDTS ||
        row.application_status === STATUS_RECEIVED_RED;
    return {
        receiveDisable: isReceived, // ✅ disable if already received
        endorsedDisabled: false,
        viewDisabled: false,
        returnDisabled: false
    }
}

const canView = (row: any) => {
    return [
        STATUS_RECEIVED_REGIONAL_TECHNICAL_STAFF,
        STATUS_APPROVED_BY_RED,
        25
    ].includes(row.application_status)
}
const generatePdf = (data) => {
    window.open(`/permit/print/${data.id}`, "_blank"); //MULTIPLE BRANDS AND MODELS

};
const avatarColors = [
    'bg-blue-500',
    'bg-green-500',
    'bg-purple-500',
    'bg-pink-500',
    'bg-orange-500',
    'bg-cyan-500',
    'bg-indigo-500',
    'bg-red-500',
];

const getAvatarColor = (name: string) => {
    if (!name) return 'bg-gray-500';

    let sum = 0;
    for (const ch of name) {
        sum += ch.charCodeAt(0);
    }

    return avatarColors[sum % avatarColors.length];
};
const onTotalFilter = (event) => {
    if (event.filteredValue) {
        displayedCount.value = event.filteredValue.length;
    } else {
        // No filter applied
        displayedCount.value = totalCount.value;
    }
};
const onApprovedFilter = (event) => {
    displayApprovedCount.value = event.filteredValue.length;
    if (event.filteredValue) {
        displayApprovedCount.value = event.filteredValue.length;
    } else {
        // No filter applied
        displayApprovedCount.value = approvedTotalCount.value;
    }
};
const showPurposeModal = ref(false)
const purposeForm = ref({
    name: ''
})

const showPurposeDialog = () => {
    purposeForm.value.name = ''
    showPurposeModal.value = true
}
const insertPurpose = async () => {
    try {
        await savePurpose(purposeForm.value)

        showPurposeModal.value = false


        // Optional success message
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Purpose added successfully.',
            life: 3000
        })

    } catch (error) {
        console.error(error)
    }
}

// const buttonState = (row: any) => {
//     const isReceived = !!row.is_tsd_chief_received;

//     const isEndorsedToPENRO =
//         row.application_status === STATUS_ENDORSED_PENRO;

//     const isEndorsedToFUS =
//         row.application_status === STATUS_ENDORSED_PENRO;

//     return {
//         // 🔵 Receive is ENABLED when endorsed to TSD and not yet received
//         receiveDisabled: !isEndorsedToPENRO,

//         // 🔵 Endorse is ENABLED only while still at TSD level
//         endorseDisabled: isEndorsedToFUS,

//         // 🔵 adjust if you later add rules
//         returnDisabled: false
//     };
// };

</script>

<template>
    <div class="flex flex-col gap-4 rounded-xl p-4">
        <Toast />
        <!-- Tabs -->
        <div class="flex border-b border-gray-200">
            <!-- For Review / Evaluation Tab -->
            <button @click="activeTab = 're'" :class="[
                'border-b-2 px-4 py-2 text-sm font-medium transition flex items-center space-x-2',
                activeTab === 're'
                    ? 'border-green-600 text-green-700'
                    : 'border-transparent text-gray-500 hover:border-green-500 hover:text-green-600'
            ]">
                <!-- Tab Title -->
                <span>List of Permit Application</span>

                <!-- PrimeVue OverlayBadge with Icon -->
                <OverlayBadge :value="displayedCount" severity="danger" size="small">
                    <i class="pi pi-list" style="font-size: 25px" />

                </OverlayBadge>
            </button>

            <button @click="activeTab = 'aa'" :class="[
                'border-b-2 px-4 py-2 text-sm font-medium transition flex items-center space-x-2',
                activeTab === 'aa'
                    ? 'border-green-600 text-green-700'
                    : 'border-transparent text-gray-500 hover:border-green-500 hover:text-green-600'
            ]">
                <!-- Tab Title -->
                <span>Approved Applications</span>




                <div class="relative inline-block">
                    <OverlayBadge :value="displayApprovedCount" severity="danger" size="small"
                        class="absolute top-0 right-0" />
                    <i class="pi pi-check-circle" style="font-size: 25px" />

                </div>
            </button>



            |
        </div>

        <!-- Content -->
        <div class="flex-1 space-y-4 overflow-y-auto">
            <!-- For Review / Evaluation Table -->
            <div v-if="activeTab === 're'" class="space-y-2 text-sm text-gray-700">
                <div class="h-auto w-full">
                    <DataTable ref="dt" size="small" v-model:selection="selectedProducts" :value="endorsed_application"
                        dataKey="id" :paginator="true" :rows="20" :filters="filters" filterDisplay="menu"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
                        responsiveLayout="scroll" class="w-full text-sm" @filter="onTotalFilter">
                        <template #header>
                            <div class="flex flex-wrap items-center justify-between gap-3">

                                <!-- Search -->
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>

                                    <InputText v-model="filters['global'].value" placeholder="Search..." class="w-64" />
                                </IconField>

                                <!-- Filters -->
                                <div class="flex flex-wrap gap-2">

                                    <!-- Office Filter -->
                                    <!-- <Select v-model="filters['office_id'].value" :options="officeOptions" filter
                                        optionLabel="label" optionValue="value" placeholder="Filter by Office"
                                        class="w-52" showClear /> -->


                                    <Select v-model="selectedOffice" :options="officeOptions" optionLabel="label"
                                        optionValue="value" placeholder="Filter by Office" filter showClear
                                        @change="onOfficeChange" />

                                    <!-- Application Type Filter -->
                                    <Select v-model="filters['application_type'].value" filter
                                        :options="applicationTypeOptions" optionLabel="label" optionValue="value"
                                        placeholder="Application Type" class="w-52" showClear />

                                    <!-- Status Filter -->
                                    <Select v-model="filters['application_status'].value" :options="statusOptions"
                                        filter optionLabel="label" optionValue="value" placeholder="Filter by Status"
                                        class="w-52" showClear />
                                    <Button v-tooltip.top="'Insert Purpose of Purchase'" @click="showPurposeDialog"
                                        class="p-2 text-white">
                                        <CirclePlus :size="15" />
                                    </Button>


                                </div>
                            </div>
                        </template>
                        <Column header="Action" :exportable="false" style="min-width: 2rem">
                            <template #body="slotProps">
                                <div class="mt-2 flex gap-2">

                                    <!-- ✅ RECEIVE (disabled if endorsed) -->
                                    <Button v-tooltip.top="buttonState(slotProps.data).receiveDisable
                                        ? 'Application cannot be received yet'
                                        : 'Receive Application'" :disabled="buttonState(slotProps.data).receiveDisable"
                                        @click="openDialog('receive', slotProps.data.id)"
                                        style="background-color: #0f766e" class="p-2 text-white">
                                        <BadgeCheck :size="15" />
                                    </Button>

                                    <Link v-if="canView(slotProps.data)" v-tooltip.top="'Edit Application'" :href="route('applications.edit', {
                                        application_id: slotProps.data.id,
                                        type: slotProps.data.application_type,
                                        step: 4
                                    })" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-white"
                                        style="background-color: #0f766e">
                                        <SquarePen :size="16" />
                                    </Link>

                                    <Link v-if="slotProps.data.application_status != STATUS_DRAFT"
                                        v-tooltip.top="'View Application'" :href="route('applications.edit', {
                                            application_id: slotProps.data.id,
                                            type: slotProps.data.application_type,
                                            step: 4
                                        })"
                                        class="mr-2 inline-flex justify-center rounded-md bg-red-700 px-3 py-2 text-white hover:bg-red-600">
                                        <View :size="16" />
                                    </Link>

                                </div>
                            </template>
                        </Column>
                        <Column header="Applicant Name" style="min-width: 16rem">
                            <template #body="slotProps">
                                <div class="flex items-center gap-3">

                                    <!-- Avatar -->
                                    <div :class="[
                                        'flex h-10 w-10 items-center justify-center rounded-full text-white font-bold text-lg uppercase flex-shrink-0',
                                        getAvatarColor(
                                            slotProps.data.application_type === 'Individual'
                                                ? slotProps.data.applicant_name
                                                : slotProps.data.company_name
                                        )
                                    ]">
                                        {{
                                            (
                                                slotProps.data.application_type === 'Individual'
                                                    ? slotProps.data.applicant_name
                                                    : slotProps.data.company_name
                                            )?.charAt(0)
                                        }}
                                    </div>

                                    <!-- Name -->
                                    <div class="flex flex-col">
                                        <!-- Individual -->
                                        <template v-if="slotProps.data.application_type === 'Individual'">
                                            <span class="font-medium">
                                                {{ slotProps.data.applicant_name }}
                                            </span>
                                        </template>

                                        <!-- Company / Government -->
                                        <template v-else>
                                            <span class="font-medium">
                                                {{ slotProps.data.company_name }}
                                            </span>

                                            <span class="text-sm text-gray-500">
                                                {{ slotProps.data.authorized_representative }}
                                            </span>
                                        </template>
                                    </div>

                                </div>
                            </template>
                        </Column>
                        <Column field="status_title" header="Status" sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <div class="flex flex-col items-center">
                                    <Tag :value="data.status_title" :severity="data.application_status >= 25 && data.application_status <= 27
                                        ? 'danger'
                                        : data.status_title === 'Endorsed to TSD Chief' || data.status_title === 'Received by ARDTS'
                                            ? 'info'
                                            : 'success'
                                        " class="text-center" />
                                    <div class="italic text-gray-600">
                                        {{ data.updated_by }}
                                    </div>

                                    <Button v-if="data.application_status >= 25 && data.application_status <= 27" style="
                                        display: inline;
                                        padding: .2em .6em .3em;
                                        font-size: 75%;
                                        font-weight: 700;
                                        line-height: 1;
                                        color: #fff;
                                        text-align: center;
                                        white-space: nowrap;
                                        vertical-align: baseline;
                                        border-radius: .25em;
                                    " severity="info" class="mt-1 rounded bg-blue-900 px-1 py-1 text-xs text-white"
                                        @click="openCommentModal(data)" size="small">
                                        View Comments
                                    </Button>
                                </div>
                            </template>
                        </Column>
                        <Column field="application_no" header="Application No" sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <b>{{ data.application_no }}</b>
                            </template>
                        </Column>
                        <!-- <Column field="permit_no" header="Permit No" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <b>{{ data.permit_no }}</b>
                            </template>
                        </Column> -->
                        <Column header="Office" style="min-width: 10rem">
                            <template #body="slotProps">
                                {{ slotProps.data.office_title }}
                            </template>
                        </Column>


                        <Column field="application_type" header="Application Type" sortable />
                        <Column header="Type of Transaction" field="transaction_type" sortable></Column>
                        <Column header="Classification" field="classification" sortable></Column>

                        <Column field="date_applied" header="Date of Application" sortable style="min-width: 4rem" />

                    </DataTable>
                </div>
            </div>
            <div v-else-if="activeTab === 'aa'" class="space-y-2 text-sm text-gray-700">
                <div class="h-auto w-full">
                    <DataTable ref="dt" size="small" v-model:selection="selectedProducts" :value="approved_application"
                        dataKey="id" :paginator="true" :rows="20" :filters="filters" filterDisplay="menu"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
                        responsiveLayout="scroll" class="w-full text-sm" @filter="onApprovedFilter">
                        <template #header>
                            <div class="flex flex-wrap items-center justify-between gap-3">

                                <!-- Search -->
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>

                                    <InputText v-model="filters['global'].value" placeholder="Search..." class="w-64" />
                                </IconField>

                                <!-- Filters -->
                                <div class="flex flex-wrap gap-2">

                                    <!-- Office Filter -->
                                    <!-- <Select v-model="filters['office_id'].value" :options="officeOptions" filter
                                        optionLabel="label" optionValue="value" placeholder="Filter by Office"
                                        class="w-52" showClear /> -->

                                    <Select :modelValue="null" :options="officeOptions" optionLabel="label"
                                        optionValue="value" placeholder="Filter by Office" class="w-52" filter showClear
                                        @change="onOfficeChange" />

                                    <!-- Application Type Filter -->
                                    <Select v-model="filters['application_type'].value" filter
                                        :options="applicationTypeOptions" optionLabel="label" optionValue="value"
                                        placeholder="Application Type" class="w-52" showClear />

                                    <!-- Status Filter -->
                                    <Select v-model="filters['application_status'].value" :options="statusOptions"
                                        filter optionLabel="label" optionValue="value" placeholder="Filter by Status"
                                        class="w-52" showClear />

                                </div>
                            </div>
                        </template>
                        <Column header="Action" :exportable="false" style="min-width: 2rem">
                            <template #body="slotProps">
                                <div class="mt-2 flex gap-2">

                                    <!-- ✅ RECEIVE (disabled if endorsed) -->
                                    <Button v-tooltip.top="'Preview'" @click="generatePdf(slotProps.data)"
                                        style="background-color: #0D47A1" class="p-2 text-white">
                                        <PrinterCheck :size="15" />
                                    </Button>

                                    <Link v-if="canView(slotProps.data)" v-tooltip.top="'Edit Application'" :href="route('applications.edit', {
                                        application_id: slotProps.data.id,
                                        type: slotProps.data.application_type,
                                        step: 4
                                    })" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-white"
                                        style="background-color: #0f766e">
                                        <SquarePen :size="16" />
                                    </Link>

                                    <Link v-if="slotProps.data.application_status != STATUS_DRAFT"
                                        v-tooltip.top="'View Application'" :href="route('applications.edit', {
                                            application_id: slotProps.data.id,
                                            type: slotProps.data.application_type,
                                            step: 4
                                        })"
                                        class="mr-2 inline-flex justify-center rounded-md bg-red-700 px-3 py-2 text-white hover:bg-red-600">
                                        <View :size="16" />
                                    </Link>

                                </div>
                            </template>
                        </Column>
                        <Column header="Applicant Name" style="min-width: 16rem">
                            <template #body="slotProps">
                                <div class="flex items-center gap-3">

                                    <!-- Avatar -->
                                    <div :class="[
                                        'flex h-10 w-10 items-center justify-center rounded-full text-white font-bold text-lg uppercase flex-shrink-0',
                                        getAvatarColor(
                                            slotProps.data.application_type === 'Individual'
                                                ? slotProps.data.applicant_name
                                                : slotProps.data.company_name
                                        )
                                    ]">
                                        {{
                                            (
                                                slotProps.data.application_type === 'Individual'
                                                    ? slotProps.data.applicant_name
                                                    : slotProps.data.company_name
                                            )?.charAt(0)
                                        }}
                                    </div>

                                    <!-- Name -->
                                    <div class="flex flex-col">
                                        <!-- Individual -->
                                        <template v-if="slotProps.data.application_type === 'Individual'">
                                            <span class="font-medium">
                                                {{ slotProps.data.applicant_name }}
                                            </span>
                                        </template>

                                        <!-- Company / Government -->
                                        <template v-else>
                                            <span class="font-medium">
                                                {{ slotProps.data.company_name }}
                                            </span>

                                            <span class="text-sm text-gray-500">
                                                {{ slotProps.data.authorized_representative }}
                                            </span>
                                        </template>
                                    </div>

                                </div>
                            </template>
                        </Column>
                        <Column field="status_title" header="Status" sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <div class="flex flex-col items-center">
                                    <Tag :value="data.status_title" :severity="data.application_status >= 25 && data.application_status <= 27
                                        ? 'danger'
                                        : data.status_title === 'Endorsed to TSD Chief' || data.status_title === 'Received by ARDTS'
                                            ? 'info'
                                            : 'success'
                                        " class="text-center" />
                                    <div class="italic text-gray-600">
                                        {{ data.updated_by }}
                                    </div>

                                    <Button v-if="data.application_status >= 25 && data.application_status <= 27" style="
                                        display: inline;
                                        padding: .2em .6em .3em;
                                        font-size: 75%;
                                        font-weight: 700;
                                        line-height: 1;
                                        color: #fff;
                                        text-align: center;
                                        white-space: nowrap;
                                        vertical-align: baseline;
                                        border-radius: .25em;
                                    " severity="info" class="mt-1 rounded bg-blue-900 px-1 py-1 text-xs text-white"
                                        @click="openCommentModal(data)" size="small">
                                        View Comments
                                    </Button>
                                </div>
                            </template>
                        </Column>
                        <Column field="application_no" header="Application No" sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <b>{{ data.application_no }}</b>
                            </template>
                        </Column>
                        <Column field="permit_no" header="Permit No" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <b>{{ data.permit_no }}</b>
                            </template>
                        </Column>
                        <Column header="Office" style="min-width: 10rem">
                            <template #body="slotProps">
                                {{ slotProps.data.office_title }}
                            </template>
                        </Column>


                        <Column field="application_type" header="Application Type" sortable />
                        <Column header="Type of Transaction" field="transaction_type" sortable></Column>
                        <Column header="Classification" field="classification" sortable></Column>

                        <Column field="date_applied" header="Date of Application" sortable style="min-width: 4rem" />

                    </DataTable>
                </div>
            </div>


        </div>


        <ReusableConfirmDialog ref="confirmDialogRef" />



        <Dialog v-model:visible="showProgressModal" modal fusheader="Routing History" :style="{ width: '70vw' }">
            <div class="overflow-x-auto">
                <!-- Loading state -->
                <div v-if="loadingRouting" class="p-4 text-center text-gray-500">Loading routing history...</div>

                <!-- Table -->
                <table v-else class="min-w-full rounded-lg border border-gray-300 bg-white text-[12px]">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-4 py-2 text-left">#</th>
                            <th class="border px-4 py-2 text-left">Sender</th>
                            <th class="border px-4 py-2 text-left">Route Details</th>
                            <th class="border px-4 py-2 text-left">Receiver</th>
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
                                <b>Route No. {{ item.route_order }}</b>
                            </td>

                            <!-- Receiver -->
                            <td class="border px-4" style="width: 20rem">
                                <b>{{ item.receiver_role }}</b><br />

                                <Tag v-if="item.action === 'Received'" severity="danger" size="small"> Received </Tag>

                                <Tag v-else-if="item.action === 'Endorsed'" severity="info" size="small"> Endorsed
                                </Tag>

                                <Tag v-else severity="warning" size="small">
                                    {{ item.action }}
                                </Tag>

                                <br />
                            </td>

                            <td class="border px-4">
                                <span v-if="item.route_order == 2">
                                    {{
                                        new Date(item.date_received_rps_chief).toLocaleString('en-PH', {
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

                                <span v-else-if="item.route_order == 4">
                                    {{
                                        new Date(item.date_received_tsd_chief).toLocaleString('en-PH', {
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
                                <span v-else-if="item.route_order == 6">
                                    {{
                                        new Date(item.date_received_penro_chief).toLocaleString('en-PH', {
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
                                <span v-else-if="item.route_order == 8">
                                    {{
                                        new Date(item.date_received_fus_chief).toLocaleString('en-PH', {
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

                                <span v-else-if="item.route_order == 10">
                                    {{
                                        new Date(item.date_received_ardts).toLocaleString('en-PH', {
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

                            <td class="border px-4">
                                <span v-if="item.route_order == 3">
                                    {{
                                        new Date(item.date_endorsed_tsd_chief).toLocaleString('en-PH', {
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
                                <span v-if="item.route_order == 5">
                                    {{
                                        new Date(item.date_endorsed_penro).toLocaleString('en-PH', {
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
                                <span v-else-if="item.route_order == 7">
                                    {{
                                        new Date(item.date_endorsed_fus).toLocaleString('en-PH', {
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
                                <span v-else-if="item.route_order == 9">
                                    {{
                                        new Date(item.date_endorsed_ardts).toLocaleString('en-PH', {
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
            </div>
        </Dialog>
        <!-- COMMENTS MODAL -->
        <Dialog v-model:visible="showCommentsModal" modal header="Comments History" :style="{ width: '60vw' }">
            <div class="overflow-x-auto">

                <!-- Loading -->
                <div v-if="loadingComment" class="p-4 text-center text-gray-500">
                    Loading comments...
                </div>

                <!-- Table -->
                <table v-else class="min-w-full rounded-lg border border-gray-300 bg-white text-[12px]">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-4 py-2 text-left">#</th>
                            <th class="border px-4 py-2 text-left">Application No.</th>
                            <th class="border px-4 py-2 text-left">Action Officer</th>
                            <th class="border px-4 py-2 text-left">Role</th>
                            <th class="border px-4 py-2 text-left">Comments</th>
                            <th class="border px-4 py-2 text-left">Status</th>
                            <th class="border px-4 py-2 text-left">Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(item, index) in commentsHistory" :key="index" class="hover:bg-gray-50">
                            <!-- # -->
                            <td class="border px-4 py-2">
                                {{ index + 1 }}
                            </td>

                            <!-- Application No -->
                            <td class="border px-4 py-2">
                                {{ item.application_no }}
                            </td>

                            <!-- Officer -->
                            <td class="border px-4 py-2">
                                {{ item.action_officer }}
                            </td>

                            <!-- Role -->
                            <td class="border px-4 py-2">
                                {{ item.sender_role }}
                            </td>

                            <!-- Comments -->
                            <td class="border px-4 py-2">
                                {{ item.comments ?? '-' }}
                            </td>

                            <!-- Status -->
                            <td class="border px-4 py-2">
                                <Tag severity="info">
                                    {{ item.status_title }}
                                </Tag>
                            </td>

                            <!-- Date -->
                            <td class="border px-4 py-2">
                                {{
                                    new Date(item.created_at).toLocaleString('en-PH', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: '2-digit',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        second: '2-digit',
                                        hour12: true,
                                    })
                                }}
                            </td>
                        </tr>

                        <!-- Empty -->
                        <tr v-if="commentsHistory.length === 0">
                            <td colspan="7" class="p-4 text-center text-gray-500">
                                No comments history found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Dialog>

        <Dialog v-model:visible="showPurposeModal" modal header="Insert Purpose of Purchase"
            :style="{ width: '35rem' }">
            <div class="flex flex-col gap-4">

                <div>
                    <label class="mb-2 block font-semibold">
                        Purpose of Purchase
                    </label>

                    <Textarea v-model="purposeForm.name" rows="5" class="w-full" autoResize />
                </div>

            </div>

            <template #footer>

                <Button label="Cancel" severity="secondary" outlined @click="showPurposeModal = false" />

                <Button label="Save" icon="pi pi-save" @click="insertPurpose" />

            </template>
        </Dialog>

    </div>
</template>


<style scoped>
@media screen and (max-width: 960px) {
    ::v-deep(.customized-timeline) {
        .p-timeline-event:nth-child(even) {
            flex-direction: row;

            .p-timeline-event-content {
                text-align: left;
            }
        }

        .p-timeline-event-opposite {
            flex: 0;
        }
    }
}

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




/* HTML: <div class="ribbon">Your text content</div> */
/* HTML: <div class="ribbon">Your text content</div> */
.approved_ribbon {
    font-size: 18px;
    font-weight: bold;
    color: #fff;

}

.approved_ribbon {
    --r: .8em;
    /* control the ribbon shape */

    padding-inline: calc(var(--r) + .3em);
    line-height: 1.8;
    clip-path: polygon(0 0, 100% 0, calc(100% - var(--r)) 50%, 100% 100%, 0 100%, var(--r) 50%);
    background: #E65100;
    /* the main color */
    width: fit-content;
}

.comment-wrap {
    white-space: normal;
    /* ✅ Allow wrapping */
    word-break: break-word;
    /* ✅ Break long words */
    overflow-wrap: break-word;
    /* ✅ Support for various browsers */
    max-width: 200px;
    /* Optional: Limit the width */
}
</style>
