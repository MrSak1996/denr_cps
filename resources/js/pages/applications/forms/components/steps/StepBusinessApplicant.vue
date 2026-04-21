<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import { Button } from '@/components/ui/button'

import Fieldset from 'primevue/fieldset'
import FloatLabel from 'primevue/floatlabel'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import { useToast } from 'primevue/usetoast';

import axios from 'axios'
import { useApi } from '@/composables/useApi'
import { lettersOnlyUppercase, lettersNumbersDashUppercase, emailUppercase } from '../../composables/useUppercaseLettersOnly';

// ------------------------------
// Directives
// ------------------------------
const vLettersOnlyUppercase = lettersOnlyUppercase;
const vLettersNumbersDashUppercase = lettersNumbersDashUppercase;
const vEmailUppercase = emailUppercase;
const toast = useToast();

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    application_type: String,
    isProcessing: {
        type: Boolean,
        default: false
    }
})

props.form.date_applied = ref(new Date())
const emit = defineEmits(['next'])

const { prov_name, getProvinceCode } = useApi()

const PREFIX = 'DENR-IV-A-'

const city_mun_opts = ref([])
const barangay_opts = ref([])

const govIdOptions = [
    { label: 'Philippine Identification (PhilID / ePhilID)', value: 'philid' },
    { label: 'Passport', value: 'passport' },
    { label: "Driver's License (LTO)", value: 'drivers_license' },
    { label: 'UMID', value: 'umid' },
    { label: 'PRC ID', value: 'prc_id' },
    { label: 'SSS ID', value: 'sss_id' },
    { label: 'Postal ID', value: 'postal_id' },
]
const formData = computed(() => props.form)

const permitNo = computed({
    get: () => formData.value.permit_no || PREFIX,
    set: (value) => {
        formData.value.permit_no = value.startsWith(PREFIX)
            ? value
            : PREFIX + value
    }
})

const save = () => {
    if (props.isProcessing) return

    emit('next', {
        ...props.form,
        application_type: props.application_type
    })
}

const handleFileUpload = (event: Event, field: string) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (!file) return

    // PDF validation
    if (
        file.type !== 'application/pdf' &&
        !file.name.toLowerCase().endsWith('.pdf')
    ) {
        toast.add({
            severity: 'warn',
            summary: 'Invalid File Format',
            detail: 'Only PDF files are allowed.',
            life: 3000
        })

        target.value = '' // reset input
        return
    }

    props.form[field] = file

    // Optional success message
    toast.add({
        severity: 'success',
        summary: 'File Accepted',
        detail: 'PDF file uploaded successfully.',
        life: 3000
    })
}

onMounted(async () => {
    await getProvinceCode()

    watch(
        () => props.form.i_province,
        async (province) => {
            if (!province) {
                city_mun_opts.value = []
                props.form.i_city_mun = ''
                return
            }

            const res = await axios.get(`/api/provinces/${province}/cities`)

            city_mun_opts.value = res.data.map((item: any) => ({
                id: item.mun_code,
                name: item.mun_name,
                code: item.geo_code
            }))
        },
        { immediate: true }
    )

    watch(
        () => props.form.i_city_mun,
        async (city) => {
            if (!city) {
                barangay_opts.value = []
                props.form.i_barangay = ''
                return
            }

            const res = await axios.get('/api/barangays', {
                params: {
                    reg_code: props.form.i_region,
                    prov_code: props.form.i_province,
                    mun_code: city
                }
            })

            barangay_opts.value = res.data.map((item: any) => ({
                id: item.bgy_code,
                name: item.bgy_name
            }))
        }
    )
})
</script>

<template>
    <div class="space-y-6">

          <Fieldset legend="Chainsaw Application">
            <div class="relative">
                <div class="mb-6 mt-4 grid gap-6 md:grid-cols-3">
                    <div>
                        <FloatLabel>
                            <InputText id="application_no" v-model="props.form.application_no" class="w-full font-bold"
                                :disabled="true" />
                            <label for="application_no">Application No.</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                    <FloatLabel :hidden="true">
                        <InputText id="permit_no" v-model="props.form.permit_no" class="w-full font-bold" />
                        <label for="permit_no">Permit No.</label>
                    </FloatLabel>
                    <FloatLabel>
                        <Select id="classification" v-model="props.form.classification" :options="['Highly Technical']"
                            class="w-full" />
                        <label for="classification">Classification</label>
                    </FloatLabel>



                </div>
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <!-- Date Applied -->
                    <div>
                        <FloatLabel>
                            <InputText id="date_applied" v-model="props.form.date_applied" type="date" class="w-full" />
                            <label for="date_applied">Date Applied</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                    <div>
                        <FloatLabel>
                            <Select v-model="props.form.type_of_transaction" :options="['G2C', 'G2B', 'G2G']"
                                class="w-full" />
                            <label>Type of Transaction</label>
                        </FloatLabel>
                    </div>
                    <div class="md:col-span-1">
                        <FloatLabel>
                            <InputText id="company_mobile_no" v-model="props.form.company_mobile_no" class="w-full" />
                            <label for="company_mobile_no">Mobile Number</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                </div>
                <!-- Main Fields -->
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <!-- Company Name -->
                    <div class="md:col-span-2">
                        <FloatLabel>

                            <!-- <InputText id="surname" v-model="props.form.company_name" v-letters-only-uppercase class="w-full" /> -->
                            <InputText id="surname" v-model="props.form.company_name" letters-numbers-dash-uppercase
                                class="w-full" />
                            <label for="surname">Company / Corporation / Cooperative Name</label>
                        </FloatLabel>
                        <InputError />
                    </div>

                    <!-- Authorized Representative -->
                    <div class="md:col-span-1">
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
                <div class="grid gap-6 md:grid-cols-1">
                    <div class="flex flex-col md:col-span-2">
                        <label for="requestLetter" class="mb-2 text-sm font-medium text-gray-700"> Upload Application
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
        </Fieldset>


        <!-- Address -->
        <Fieldset legend="Company Address">

            <div class="grid gap-4 md:grid-cols-4">

                <FloatLabel>
                    <InputText value="Region IV-A (CALABARZON)" class="w-full" readonly />
                    <label>Region</label>
                </FloatLabel>

                <FloatLabel>
                    <Select v-model="props.form.company_c_province" :options="prov_name" optionLabel="name" optionValue="id"
                        class="w-full" />
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
                    <label class="block mb-2 text-sm font-medium">
                        Complete Address
                    </label>

                    <Textarea v-model="props.form.company_address" rows="4" class="w-full"
                        v-letters-numbers-dash-uppercase />
                </div>

            </div>

        </Fieldset>
        <Button :disabled="props.isProcessing" type="button"
            class="w-full mt-2 bg-green-900 text-white transition-colors hover:bg-green-500 text-white" @click="save">
            {{ props.isProcessing ? 'Saving...' : 'Save & Continue' }}
        </Button>

    </div>
</template>