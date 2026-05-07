<script setup lang="ts">
import { useApi } from '@/composables/useApi';

import Fieldset from 'primevue/fieldset'
import FloatLabel from 'primevue/floatlabel'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import { ref, computed, watch, onMounted } from 'vue'
import { ShieldAlert } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3';
import DatePicker from 'primevue/datepicker';
import { lettersOnlyUppercase, lettersNumbersDashUppercase, emailUppercase} from './composables/useUppercaseLettersOnly';

// ------------------------------
// Directives
// ------------------------------
const vLettersOnlyUppercase = lettersOnlyUppercase;
const vLettersNumbersDashUppercase = lettersNumbersDashUppercase;
const vEmailUppercase = emailUppercase;
// ------------------------------
// Props
// ------------------------------

const props = defineProps({
    form: Object,
    insertFormData: Function,
    getProvinceCode: Function,
    prov_name: Array,
})

const { prov_name, getProvinceCode } = useApi()



const formData = computed(() => props.form)
// const applicationData = computed(() => props.app_data) 

// ------------------------------
// Reactive references
// ------------------------------


const PREFIX = "DENR-IV-A-"

let city_mun_opts = ref<{ id: any; name: any; code: any }[]>([]);
let barangay_opts = ref<{ id: any; name: any }[]>([]);
let geo_code = ref([]);

const page = usePage();

// Extract your application data
const application = page.props.application

const govIdOptions = [
    { label: "Philippine Identification (PhilID / ePhilID)", value: "philid" },
    { label: "Passport", value: "passport" },
    { label: "Driver's License (LTO)", value: "drivers_license" },
    { label: "Unified Multi-Purpose ID (UMID)", value: "umid" },
    { label: "Professional Regulation Commission (PRC) ID", value: "prc_id" },
    { label: "Social Security System (SSS) ID", value: "sss_id" },
    { label: "GSIS eCard", value: "gsis_ecard" },
    { label: "Voter's ID / COMELEC Certificate", value: "voters_id" },
    { label: "Postal ID", value: "postal_id" },
    { label: "Senior Citizen ID", value: "senior_id" },
    { label: "Persons with Disability (PWD) ID", value: "pwd_id" },
    { label: "Integrated Bar of the Philippines (IBP) ID", value: "ibp_id" },
    { label: "OWWA / iDOLE Card", value: "owwa_idole" },
];
const getApplicationIdFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('application_id') || urlParams.get('id');
};
const application_id = getApplicationIdFromUrl();

// ------------------------------

// ------------------------------
// Computed: formatted application number
// ------------------------------
const applicationNo = computed({
    get: () => formData.value.permit_no ?? PREFIX,
    set: (value: string) => {
        if (!value.startsWith(PREFIX)) value = PREFIX + value.replace(PREFIX, "")
        formData.value.permit_no = value
    }
})








// -----------------------------
// Load initial values (EDIT MODE)
// -----------------------------
onMounted(async () => {

    getProvinceCode()
    // -----------------------------
    // Watch City → Update Geo Code
    // -----------------------------
    watch(
        () => props.form.i_city_mun,
        (newCityMun) => {
            const selected = city_mun_opts.value.find(item => item.id === newCityMun)
            props.form.geo_code = selected?.code ?? ''
        }
    )

    // -----------------------------
    // Watch Province → Load Cities
    // -----------------------------
    watch(
        () => props.form.i_province,
        async (newProvince) => {

            if (!newProvince) {
                city_mun_opts.value = []
                props.form.i_city_mun = ''
                barangay_opts.value = []
                props.form.i_barangay = ''
                return
            }

            try {

                const response = await axios.get(
                    `http://localhost:8000/api/provinces/${newProvince}/cities`
                )

                city_mun_opts.value = response.data.map((item: any) => ({
                    id: item.mun_code,
                    name: item.mun_name,
                    code: item.geo_code
                }))

                props.form.i_city_mun = ''
                barangay_opts.value = []
                props.form.i_barangay = ''

            } catch (error) {
                console.error("Error loading cities", error)
                city_mun_opts.value = []
            }

        }
    )


    // -----------------------------
    // Watch City → Load Barangays
    // -----------------------------
    watch(
        () => props.form.i_city_mun,
        async (newCityMun) => {

            const province = props.form.i_province
            const region = props.form.i_region

            if (!newCityMun) {
                barangay_opts.value = []
                props.form.i_barangay = ''
                return
            }

            try {

                const response = await axios.get(
                    `http://localhost:8000/api/barangays`,
                    {
                        params: {
                            reg_code: region,
                            prov_code: province,
                            mun_code: newCityMun
                        }
                    }
                )

                barangay_opts.value = response.data.map((item: any) => ({
                    id: item.bgy_code,
                    name: item.bgy_name
                }))

                props.form.i_barangay = ''

            } catch (error) {

                console.error("Error loading barangays", error)
                barangay_opts.value = []

            }

        }
    )


})
</script>



<template>
    <div>
        <Fieldset legend="Chainsaw Application">
            <div class="relative">
              


                <!-- Application Number -->
                <div class="mb-6 grid gap-6 md:grid-cols-3 mt-5">
                    <FloatLabel>
                        <InputText id="application_no" v-model="props.form.application_no" class="w-full font-bold"
                            disabled />
                        <label for="application_no">Application No.</label>
                    </FloatLabel>
                    <FloatLabel>
                        <InputText :disabled="true" id="permit_no" v-model="formData.permit_no"
                            class="w-full font-bold" />
                        <label for="permit_no">Permit No.</label>
                    </FloatLabel>

                </div>

                <!-- Date & Transaction -->
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <FloatLabel>
                        <DatePicker id="date_applied" type="date" v-model="formData.date_applied" class="w-full" />
                        <label for="date_applied">Date Applied</label>
                    </FloatLabel>

                    <FloatLabel>
                        <Select id="type_of_transaction" v-model="formData.type_of_transaction"
                            :options="['G2C', 'G2B', 'G2G']" class="w-full" />
                        <label for="type_of_transaction">Type of Transaction</label>
                    </FloatLabel>

                    <FloatLabel>
                        <Select id="classification" v-model="formData.classification"
                            :options="['Simple', 'Complex', 'Highly Technical']" class="w-full" />
                        <label for="classification">Classification</label>
                    </FloatLabel>

                </div>

                <!-- NAME FIELDS -->
                <div class="grid gap-6 md:grid-cols-3">
                    <FloatLabel>
                        <InputText id="surname" v-model="formData.last_name" v-letters-only-uppercase class="w-full" />
                        <label for="surname">Last Name</label>
                    </FloatLabel>

                    <FloatLabel>
                        <InputText id="first_name" v-model="formData.first_name" v-letters-only-uppercase
                            class="w-full" />
                        <label for="first_name">First Name</label>
                    </FloatLabel>

                    <FloatLabel>
                        <InputText id="middlename" v-model="formData.middle_name" v-letters-only-uppercase 
                            class="w-full" />
                        <label for="middlename">Middle Name</label>
                    </FloatLabel>

                    <FloatLabel>
                        <Select id="sex" v-model="formData.sex" :options="[
                            { label: 'Male', value: 'male' },
                            { label: 'Female', value: 'female' },
                            { label: 'Prefer not to say', value: 'prefer_not_to_say' }
                        ]" optionLabel="label" optionValue="value" class="w-full" />
                        <label for="sex">Sex</label>
                    </FloatLabel>

                    <FloatLabel>
                        <Select id="gov_id_type" v-model="formData.gov_id_type" :options="govIdOptions"
                            optionLabel="label" optionValue="value" class="w-full" placeholder="Select Government ID" />
                        <label for="gov_id_type">Government ID</label>
                    </FloatLabel>


                    <FloatLabel>
                        <InputText id="gov_id_number" v-model="formData.gov_id_number" class="w-full" v-letters-numbers-dash-uppercase />
                        <label for="gov_id_number">ID Number</label>
                    </FloatLabel>
                </div>
            </div>
        </Fieldset>

        <!-- CONTACT INFO -->
        <Fieldset legend="Contact Information">
            <div class="mt-4 grid gap-6 md:grid-cols-4">
                <div>
                    <FloatLabel>
                        <InputText id="mobile" v-model="formData.mobile_no" class="w-full" />
                        <label for="mobile">Mobile Number</label>
                    </FloatLabel>
                    <InputError :message="props.form.errors?.mobile_no" />
                </div>

                <div>
                    <FloatLabel>
                        <InputText id="telephone" v-model="formData.telephone_no" class="w-full" />
                        <label for="telephone">Telephone Number</label>
                    </FloatLabel>
                    <InputError :message="props.form.errors?.telephone_no" />
                </div>

                <div class="md:col-span-2">
                    <FloatLabel>
                        <InputText id="email_address" v-model="formData.email_address" class="w-full"  v-email-uppercase />
                        <label for="email_address">Email Address</label>
                    </FloatLabel>
                    <InputError :message="props.form.errors?.email_address" />
                </div>
            </div>
        </Fieldset>

        <!-- COMPLETE ADDRESS -->
        <Fieldset legend="Complete Address">
            <div class="grid gap-6 md:grid-cols-4">
                <FloatLabel>
                    <InputText v-model="props.form.i_province" 
                    value="Region IV-A (CALABARZON)" class="w-full"
                        disabled />
                </FloatLabel>

                <FloatLabel>
                    <Select v-model="props.form.i_province" 
                    :options="prov_name" optionValue="id"
                        optionLabel="name" class="w-full" />


                </FloatLabel>

                <FloatLabel>
                    <Select filter v-model="props.form.i_city_mun" :options="city_mun_opts" optionValue="id"
                        optionLabel="name" placeholder="Municipality" class="w-full" />

                </FloatLabel>

                <FloatLabel>
                    <Select filter v-model="props.form.i_barangay" :options="barangay_opts" optionValue="id"
                        optionLabel="name" placeholder="Barangay" class="w-full" />

                </FloatLabel>

                <div class="md:col-span-2">
                    <label for="address" class="mb-1 block text-sm font-medium text-gray-700">
                        Complete Address
                    </label>
                    <Textarea id="address" rows="6" v-model="props.form.i_complete_address" v-letters-numbers-dash-uppercase 
                        placeholder="Complete Address (Street, Purok, etc.)"
                        class="w-[73rem] rounded-md border border-gray-300 p-2 text-sm">
                    {{ props.form.i_complete_address }}
                    </Textarea>
                </div>
            </div>
        </Fieldset>

       
    </div>
</template>
