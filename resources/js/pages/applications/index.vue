<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head,router } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import { ref, watch, onMounted } from 'vue';
import application_form from './forms/application_form_v2.vue';
import company_application_form from './forms/company_application_form.vue';
import individual_img from '../../../images/man.png';
import company_img from '../../../images/office-building.png';
import government_img from '../../../images/government.png';
import axios from 'axios';
import Tag from 'primevue/tag';

// ---------------------
// STATE
// ---------------------
const applicationData = ref([]);
const checked = ref<boolean | null>(null);
const hasSelected = ref(false);

// ---------------------
// BREADCRUMBS
// ---------------------
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '/applicants/index' },
];

// ---------------------
// UTILITY: URL PARAMS
// ---------------------
const getUrlParams = () => new URLSearchParams(window.location.search);

const getApplicationTypeFromUrl = (): string | null => {
    return getUrlParams().get('type');
};

const getApplicationIdFromUrl = () => {
    const params = getUrlParams();
    return params.get('application_id') || params.get('id');
};

// ---------------------
// ACTIONS
// ---------------------
const selectApplicant = (type: string) => {
    router.visit(`/applications/create/${type}`);
};


// ---------------------
// API
// ---------------------
const getApplicationDetails = async () => {
    const applicationId = getApplicationIdFromUrl();
    if (!applicationId) return;

    try {
        const { data } = await axios.get(
            `https://cps.denrcalabarzon.com/api/getApplicationDetails/${applicationId}`
        );
        applicationData.value = data?.data ?? [];
    } catch (error) {
        console.error('Error fetching application details:', error);
    }
};

// ---------------------
// WATCHERS
// ---------------------
watch(checked, (value) => {
    hasSelected.value = value !== null;
});

// ---------------------
// LIFECYCLE
// ---------------------

</script>


<style scoped>
.box {
    background-color: #fff;
    border-top: 4px solid #00943a;
    margin-bottom: 20px;
    padding: 20px;
    -moz-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    -o-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    -webkit-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
}

.box .title {
    border-bottom: 1px solid #e0e0e0;
    color: #432c0b !important;
    font-weight: bold;
    margin-bottom: 20px;
    margin-top: 0;
    padding-bottom: 10px;
    padding-top: 0;
    text-transform: uppercase;
    font-size: 10pt;
}

/* Base style for ToggleButton - Green (unchecked/default state) */
/* Default state - Green */
/* Base style for ToggleButton - Green (unchecked/default state) */
.p-togglebutton {
    font-weight: 600;
    font-size: 1rem;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    border: none;
    background-color: #22c55e;
    /* green-500 */
    color: white;
    transition: background-color 0.3s ease, filter 0.3s ease;
}

/* Hover effect */
.p-togglebutton:hover {
    filter: brightness(1.1);
}

/* Checked state - Darker green */
.p-togglebutton.p-togglebutton-checked {
    background-color: #15803d !important;
    /* green-700 */
    border-color: #166534;
    color: rgb(0, 0, 0);
}

/* Fix inner white background */
.p-togglebutton.p-togglebutton-checked .p-togglebutton-content {
    background-color: #15803d !important;
    box-shadow: none;
    color: white !important;
}

/* Ensure label and icon are white in all states */
.p-togglebutton .p-togglebutton-icon,
.p-togglebutton .p-togglebutton-label {
    color: white !important;
}
</style>


<template>
    <Head title="Chainsaw Purchase System" />

    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="rounded-xl p-6 space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-2">
                <Info class="h-5 w-5" />
                <h1 class="text-xl font-semibold">
                    Application Status:
                </h1>

                <Tag severity="danger">
                    Draft
                </Tag>
            </div>

            <div class="box">

                <!-- Applicant Type Selection -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">

                    <!-- Government -->
                    <button
                        :disabled="true"
                        class="flex flex-col items-center justify-center gap-3 p-6 rounded-2xl shadow-md border bg-[#093C5D] text-[#3B7597] hover:bg-purple-200 hover:shadow-lg transition"
                    >
                        <img :src="government_img" class="h-28 w-28 object-contain" />
                        <span class="text-lg font-semibold">Government</span>
                    </button>

                    <!-- Business -->
                    <button
                        @click="selectApplicant('business')"
                        class="flex flex-col items-center justify-center gap-3 p-6 rounded-2xl shadow-md border bg-[#093C5D] text-[#3B7597] hover:bg-indigo-200 hover:shadow-lg transition"
                    >
                        <img :src="company_img" class="h-28 w-28 object-contain" />
                        <span class="text-lg font-semibold">Business</span>
                    </button>

                    <!-- Citizen -->
                    <button
                        @click="selectApplicant('citizen')"
                        class="flex flex-col items-center justify-center gap-3 p-6 rounded-2xl shadow-md border bg-[#093C5D] text-[#3B7597] hover:bg-green-200 hover:shadow-lg transition"
                    >
                        <img :src="individual_img" class="h-28 w-28 object-contain" />
                        <span class="text-lg font-semibold">Citizen</span>
                    </button>

                </div>

            </div>
        </div>

    </AppLayout>
</template>
