<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { computed, onMounted, ref, watch } from 'vue';

import { useApi } from '@/composables/useApi';
import axios from 'axios';
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

const emit = defineEmits(['next', 'proceed']);
const { prov_name, getProvinceCode } = useApi();
const PREFIX = 'DENR-IV-A-';
const city_mun_opts = ref([]);
const barangay_opts = ref([]);

const files = computed(() => {
    return (props.files || [])
        .map((file: any) => ({
            id: file.id,
            application_type: file.application_type,
            application_id: file.application_id,
            attachment_id: file.attachment_id,
            name: file.file_name,
            url: file.file_url,
        }))
        .filter((file: any) =>
            typeof file.name === 'string' &&
            file.name.startsWith('valid_id_')
        );
});


// default date only if empty
if (!props.form.date_applied) {
    props.form.date_applied = new Date();
}

const formData = computed(() => props.form);

const permitNo = computed({
    get: () => formData.value.permit_no || PREFIX,
    set: (value) => {
        formData.value.permit_no = value.startsWith(PREFIX) ? value : PREFIX + value;
    },
});

const save = () => {
    if (props.isProcessing) return;
    isLoading.value = true;

    emit('next', {
        ...props.form,
        application_type: props.application_type,
    });
};

const handleImageUpload = (event: Event, field: string) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    if (!file.type.startsWith('image/')) {
        toast.add({
            severity: 'warn',
            summary: 'Invalid File Format',
            detail: 'Only image files are allowed.',
            life: 3000,
        });

        target.value = '';
        return;
    }

    props.form[field] = file;

    toast.add({
        severity: 'success',
        summary: 'Uploaded',
        detail: 'Image uploaded successfully.',
        life: 3000,
    });
};

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
        formData.append('application_type', selectedFileToUpdate.value.application_type);
        formData.append('file', newFile);
        formData.append('attachment_id', selectedFileToUpdate.value.attachment_id);
        formData.append('name', selectedFileToUpdate.value.name);

        const response = await axios.post(
            'http://localhost:8000/api/files/update',
            formData,
            {
                headers: { 'Content-Type': 'multipart/form-data' }
            }
        );

        // ✅ Find correct file using attachment_id (not id)
        const updatedIndex = files.value.findIndex(
            (f) => f.attachment_id === selectedFileToUpdate.value.attachment_id
        );

        // ✅ Update only the changed fields based on backend response
        if (updatedIndex !== -1 && response.data?.data) {
            files.value[updatedIndex].name = response.data.data.file_name;
            files.value[updatedIndex].url = response.data.data.file_url;
        }

        toast.add({
            severity: 'success',
            summary: 'Successful',
            detail: 'File updated successfully',
            life: 3000
        });

    } catch (error) {
        console.error(error);

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error?.response?.data?.message || 'Failed to update the file.',
            life: 3000
        });

    } finally {
        // ✅ Reset file input safely
        if (updateFileInput.value) {
            updateFileInput.value.value = '';
        }

        selectedFileToUpdate.value = null;
    }
};
// -------------------------------------
// LOAD MUNICIPALITIES
// -------------------------------------
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

// -------------------------------------
// LOAD BARANGAYS
// -------------------------------------
const loadBarangays = async (city: any) => {
    if (!city) {
        barangay_opts.value = [];
        return;
    }

    try {
        const res = await axios.post('/api/barangays', {
            prov_code: Number(props.form.i_province),
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

    // province watcher
    watch(
        () => props.form.i_province,
        async (province, oldVal) => {
            await loadMunicipalities(province);

            // reset only if user changed manually
            if (oldVal && province !== oldVal) {
                props.form.i_city_mun = '';
                props.form.i_barangay = '';
                barangay_opts.value = [];
            }
        },
        { immediate: true },
    );

    // municipality watcher
    watch(
        () => props.form.i_city_mun,
        async (city, oldVal) => {
            await loadBarangays(city);

            if (oldVal && city !== oldVal) {
                props.form.i_barangay = '';
            }
        },
        { immediate: true },
    );

    watch(
        () => props.form,
        (val) => {
            if (!val) return;

            if (!val.type_of_transaction) {
                props.form.type_of_transaction = 'G2C';
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
        <!-- Chainsaw Application -->
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
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <FloatLabel>
                    <InputText v-model="props.form.application_no" :disabled="isEdit" class="w-full font-bold"
                        readonly />
                    <label>Application No.</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="permitNo" class="w-full font-bold" :disabled="isEdit" readonly />
                    <label>Permit No.</label>
                </FloatLabel>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <FloatLabel class="mt-2">
                    <InputText type="date" v-model="props.form.date_applied" class="w-full" />
                    <label>Date Applied</label>
                </FloatLabel>
                <FloatLabel class="mt-2">
                    <Select v-model="props.form.type_of_transaction" :options="['G2C', 'G2B', 'G2G']" :disabled="isEdit"
                        class="w-full" />
                    <label>Type of Transaction</label>
                </FloatLabel>

                <FloatLabel class="mt-2">
                    <Select v-model="props.form.classification" :options="['Simple', 'Complex', 'Highly Technical']"
                        :disabled="isEdit" class="w-full" />
                    <label>Classification</label>
                </FloatLabel>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <FloatLabel class="mt-2">
                    <InputText v-model="props.form.last_name" class="w-full" v-letters-only-uppercase />
                    <label>Last Name</label>
                </FloatLabel>

                <FloatLabel class="mt-2">
                    <InputText v-model="props.form.first_name" class="w-full" v-letters-only-uppercase />
                    <label>First Name</label>
                </FloatLabel>

                <FloatLabel class="mt-2">
                    <InputText v-model="props.form.middle_name" class="w-full" v-letters-only-uppercase />
                    <label>Middle Name</label>
                </FloatLabel>

                <FloatLabel class="mt-2">
                    <Select v-model="props.form.sex" :options="[
                        { label: 'Male', value: 'male' },
                        { label: 'Female', value: 'female' },
                    ]" optionLabel="label" optionValue="value" class="w-full" />
                    <label>Sex</label>
                </FloatLabel>
            </div>
            <div class="mb-3" v-if="isEdit">
                <div v-if="files && files.length > 0">
                    <div class="container">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <FileCard v-for="(file, index) in files" :key="index" :file="file"
                                @openPreview="openFileModal" @updateFile="triggerUpdateFile" />
                        </div>
                    </div>
                </div>

                <!-- ✅ SHOW UPLOAD UI WHEN NO FILES -->
                <div v-else class="p-4 text-center border rounded bg-gray-50 relative border-2 border-dashed p-4 rounded bg-white" >
                    <p class="mb-2 text-gray-600">No files uploaded yet.</p>

                    <input type="file" id="validId" accept="image/*" @change="(e) => handleImageUpload(e, 'valid_id')"
                        class="w-full absolute inset-0 opacity-0 cursor-pointer" />
                </div>

            </div>



        </Fieldset>

        <!-- Contact -->
        <Fieldset legend="Contact Information">
            <div class="grid gap-4 md:grid-cols-4 mt-4">
                <FloatLabel>
                    <InputText v-model="props.form.mobile_no" class="w-full" />
                    <label>Mobile No</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="props.form.telephone_no" class="w-full" />
                    <label>Telephone No</label>
                </FloatLabel>

                <div class="md:col-span-2">
                    <FloatLabel>
                        <InputText v-model="props.form.email_address" class="w-full" v-email-uppercase />
                        <label>Email Address</label>
                    </FloatLabel>
                </div>
            </div>
        </Fieldset>

        <!-- Address -->
        <Fieldset legend="Complete Address">
            <div class="grid gap-4 md:grid-cols-4">
                <FloatLabel>
                    <InputText value="Region IV-A (CALABARZON)" class="w-full" readonly />
                    <label>Region</label>
                </FloatLabel>

                <FloatLabel>
                    <Select v-model="props.form.i_province" :options="prov_name" optionLabel="name" optionValue="id"
                        class="w-full" />
                    <label>Province</label>
                </FloatLabel>

                <FloatLabel>
                    <Select filter v-model="props.form.i_city_mun" :options="city_mun_opts" optionLabel="name"
                        optionValue="id" class="w-full" />
                    <label>Municipality</label>
                </FloatLabel>

                <FloatLabel>
                    <Select filter v-model="props.form.i_barangay" :options="barangay_opts" optionLabel="name"
                        optionValue="id" class="w-full" />
                    <label>Barangay</label>
                </FloatLabel>

                <div class="md:col-span-4">
                    <label class="mb-2 block text-sm font-medium"> Complete Address </label>

                    <Textarea v-model="props.form.i_complete_address" rows="4" class="w-full" />
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
