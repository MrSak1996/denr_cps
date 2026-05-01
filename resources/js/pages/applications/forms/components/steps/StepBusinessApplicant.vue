<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { computed, onMounted, ref, watch } from 'vue';

import { useApi } from '@/composables/useApi';
import axios from 'axios';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Fieldset from 'primevue/fieldset';
import FloatLabel from 'primevue/floatlabel';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import { Info } from 'lucide-vue-next'
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import { emailUppercase, lettersNumbersDashUppercase, lettersOnlyUppercase } from '../../composables/useUppercaseLettersOnly';
import FileCard from '../../file_card.vue';

// ------------------------------
// Directives
// ------------------------------
const vLettersOnlyUppercase = lettersOnlyUppercase;
const vLettersNumbersDashUppercase = lettersNumbersDashUppercase;
const vEmailUppercase = emailUppercase;
const toast = useToast();
const showModal = ref(false);
const isLoading = ref(false);
const selectedFile = ref<any>(null);
const selectedFileToUpdate = ref(null);
const updateFileInput = ref(null);


const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    application_type: String,
    isProcessing: {
        type: Boolean,
        default: false,
    },
    mode: String,
    files: Array,

});
const isEdit = computed(() => props.mode === 'edit');

const files = computed(() => {
    return (props.files || []).map((file: any) => ({
        id: file.id,
        application_id: file.application_id,
        attachment_id: file.attachment_id,
        name: file.file_name,
        url: file.file_url,
    }));
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
props.form.date_applied = ref(new Date());
const emit = defineEmits(['next', 'proceed']);

const { prov_name, getProvinceCode } = useApi();

const PREFIX = 'DENR-IV-A-';

const city_mun_opts = ref([]);
const barangay_opts = ref([]);

const govIdOptions = [
    { label: 'Philippine Identification (PhilID / ePhilID)', value: 'philid' },
    { label: 'Passport', value: 'passport' },
    { label: "Driver's License (LTO)", value: 'drivers_license' },
    { label: 'UMID', value: 'umid' },
    { label: 'PRC ID', value: 'prc_id' },
    { label: 'SSS ID', value: 'sss_id' },
    { label: 'Postal ID', value: 'postal_id' },
];
const formData = computed(() => props.form);

const permitNo = computed({
    get: () => formData.value.permit_no || PREFIX,
    set: (value) => {
        formData.value.permit_no = value.startsWith(PREFIX) ? value : PREFIX + value;
    },
});

const save = () => {
    if (props.isProcessing) return;

    emit('next', {
        ...props.form,
        application_type: props.application_type,
    });
};

const handleFileUpload = (event: Event, field: string) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    // PDF validation
    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
        toast.add({
            severity: 'warn',
            summary: 'Invalid File Format',
            detail: 'Only PDF files are allowed.',
            life: 3000,
        });

        target.value = ''; // reset input
        return;
    }

    props.form[field] = file;

    // Optional success message
    toast.add({
        severity: 'success',
        summary: 'File Accepted',
        detail: 'PDF file uploaded successfully.',
        life: 3000,
    });
};
const loadMunicipalities = async (province: any) => {
    if (!province) {
        city_mun_opts.value = [];
        barangay_opts.value = [];
        return;
    }

    try {
        const res = await axios.get(`/api/provinces/${province}/cities`);

        city_mun_opts.value = res.data.map((item: any) => ({
            id: Number(item.mun_code),
            name: item.mun_name,
        }));
    } catch (error) {
        console.error('Municipality load error:', error);
    }
};
const loadBarangays = async (city: any) => {
    if (!city) {
        barangay_opts.value = [];
        return;
    }

    try {
        const res = await axios.post('/api/barangays', {
            prov_code: Number(props.form.company_c_province),
            mun_code: city,
        });

        barangay_opts.value = res.data.map((item: any) => ({
            id: Number(item.bgy_code), // FIXED
            name: item.bgy_name,
        }));
    } catch (error) {
        console.error('Barangay load error:', error);
    }
};
onMounted(async () => {
    await getProvinceCode();

    watch(
        () => props.form.company_c_province,
        async (province, oldVal) => {
            await loadMunicipalities(province);

            // reset only if user changed manually
            if (oldVal && province !== oldVal) {
                props.form.c_city_mun = '';
                props.form.c_barangay = '';
                barangay_opts.value = [];
            }
        },
        { immediate: true },
    );
    watch(
        () => props.form.c_city_mun,
        async (city, oldVal) => {
            await loadBarangays(city);

            if (oldVal && city !== oldVal) {
                props.form.c_barangay = '';
            }
        },
        { immediate: true },
    );
    watch(
        () => props.form,
        (val) => {
            if (!val) return;

            if (!val.type_of_transaction) {
                props.form.type_of_transaction = 'G2B';
            }

            if (!val.classification) {
                props.form.classification = 'Highly Technical';
            }
        },
        { immediate: true, deep: true },
    );
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

        <Fieldset legend="Chainsaw Application">
            <div class="mb-6 mt-4 grid gap-6 md:grid-cols-3">
                <div>
                    <FloatLabel>
                        <InputText id="application_no" v-model="props.form.application_no" class="w-full font-bold"
                            :disabled="true" />
                        <label for="application_no">Application No.</label>
                    </FloatLabel>
                    <InputError />
                </div>
                <FloatLabel>
                    <InputText id="permit_no" v-model="props.form.permit_no" class="w-full font-bold" />
                    <label for="permit_no">Permit No.</label>
                </FloatLabel>
                <FloatLabel>
                    <DatePicker v-model="props.form.date_applied" class="w-full" />
                    <label>Date Applied</label>
                </FloatLabel>

            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <FloatLabel>
                    <Select id="classification" v-model="props.form.classification" :options="['Highly Technical']"
                        class="w-full" />
                    <label for="classification">Classification</label>
                </FloatLabel>
                <FloatLabel>
                    <Select v-model="props.form.type_of_transaction" :options="['G2B']" :disabled="isEdit"
                        class="w-full" />
                    <label>Type of Transaction</label>
                </FloatLabel>
                <FloatLabel>
                    <InputText id="company_mobile_no" v-model="props.form.company_mobile_no" class="w-full" />
                    <label for="company_mobile_no">Mobile Number</label>
                </FloatLabel>
                <InputError />
            </div>

            <div class="relative">

                <!-- Main Fields -->
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <!-- Company Name -->
                    <div class="md:col-span-2 mt-2">
                        <FloatLabel>
                            <!-- <InputText id="surname" v-model="props.form.company_name" v-letters-only-uppercase class="w-full" /> -->
                            <InputText id="surname" v-model="props.form.company_name" letters-numbers-dash-uppercase
                                class="w-full" />
                            <label for="surname">Company / Corporation / Cooperative Name</label>
                        </FloatLabel>
                        <InputError />
                    </div>

                    <!-- Authorized Representative -->
                    <div class="md:col-span-1 mt-2">
                        <FloatLabel>
                            <InputText id="first_name" v-model="props.form.authorized_representative"
                                v-letters-only-uppercase class="w-full" />
                            <label for="first_name">Name of Authorized Representative</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                </div>

                <!-- Additional Fields -->

                <!-- Application Letter Upload -->
                <div class="mb-3" v-if="isEdit">
                    <div class="container">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <FileCard v-for="(file, index) in files" :key="index" :file="file"
                                @openPreview="openFileModal" @updateFile="triggerUpdateFile" />
                        </div>
                    </div>
                    <input type="file" ref="updateFileInput" class="hidden" @change="handleFileUpdate" />
                </div>
                <div v-else>
                    <div class="grid gap-6 md:grid-cols-1">
                        <div class="flex flex-col md:col-span-2">
                            <label for="requestLetter" class="mb-2 text-sm font-medium text-gray-700"> Upload
                                Application
                                Letter / Request Letter </label>
                            <input type="file" id="requestLetter" accept="application/pdf"
                                @change="e => handleFileUpload(e, 'request_letter')"
                                class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />

                        </div>
                    </div>
                    <!-- Soc. Certificate Upload -->
                    <div class="mt-4 grid gap-6 md:grid-cols-1">
                        <div class="flex flex-col md:col-span-2">
                            <label for="socCertificate" class="mb-2 text-sm font-medium text-gray-700">
                                Upload Authorization Documents (e.g. Secretary's Certificate)
                            </label>
                            <input id="socCertificate" type="file" accept="application/pdf"
                                @change="e => handleFileUpload(e, 'soc_certificate')"
                                class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                        </div>
                    </div>
                </div>
            </div>
        </Fieldset>

        <!-- Address -->
        <Fieldset legend="Company Address">
            <div class="grid gap-4 md:grid-cols-4">
                <FloatLabel>
                    <InputText value="Region IV-A (CALABARZON)" class="w-full" readonly />
                    <label>Region</label>
                </FloatLabel>

                <FloatLabel>
                    <Select v-model="props.form.company_c_province" :options="prov_name" optionLabel="name"
                        optionValue="id" class="w-full" />
                    <label>Province</label>
                </FloatLabel>

                <FloatLabel>
                    <Select filter v-model="props.form.c_city_mun" :options="city_mun_opts" optionLabel="name"
                        optionValue="id" class="w-full" />
                    <label>Municipality</label>
                </FloatLabel>

                <FloatLabel>
                    <Select filter v-model="props.form.c_barangay" :options="barangay_opts" optionLabel="name"
                        optionValue="id" class="w-full" />
                    <label>Barangay</label>
                </FloatLabel>

                <div class="md:col-span-4">
                    <label class="mb-2 block text-sm font-medium"> Complete Address </label>

                    <Textarea v-model="props.form.company_address" rows="4" class="w-full"
                        v-letters-numbers-dash-uppercase />
                </div>
            </div>
        </Fieldset>

        <div :class="[
            'w-full pt-6',
            isEdit ? 'grid grid-cols-2 gap-4' : 'flex justify-end'
        ]">
            <Button :disabled="props.isProcessing" type="button"
                class="w-full bg-green-900 text-white transition-colors hover:bg-green-500" @click="save">
                {{ props.isProcessing ? 'Saving...' : 'Save & Continue' }}
            </Button>

            <Button v-if="isEdit" @click="$emit('proceed')" class="w-full bg-gray-300 hover:bg-gray-400">
                Next
            </Button>
        </div>

         <Dialog v-model:visible="isLoading" modal :closable="false" :draggable="false" :style="{ width: '300px' }">
            <div class="flex flex-col items-center gap-4 py-4">
                <span>Saving, please wait...</span>
                <ProgressBar mode="indeterminate" style="width: 100%; height: 6px" />
            </div>
        </Dialog>

        <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
            <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500"
                allow="autoplay"></iframe>
        </Dialog>


    </div>
</template>
