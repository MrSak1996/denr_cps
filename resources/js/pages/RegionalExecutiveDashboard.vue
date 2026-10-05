<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, onMounted, computed, defineAsyncComponent } from 'vue';
import { Info, List } from 'lucide-vue-next';
import Card from 'primevue/card';
import Fieldset from 'primevue/fieldset';
import { Head, usePage } from '@inertiajs/vue3';
import { useOfficeTitle } from '@/composables/useOfficeTitle';
import axios from 'axios';
import total_icon from '../../images/icons/application.png';
import review_icon from '../../images/icons/review.png';
import approved_icon from '../../images/icons/approved.png';
import reject_icon from '../../images/icons/reject.png';

const red_table = defineAsyncComponent(() => import('./applications/table/red_tbl.vue'));

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
const { officeTitle } = useOfficeTitle();
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Regional Executive Dashboard',
        href: '/regional-executive-dashboard',
    },
];

const page = usePage();
const userId = page.props.auth.user.id;
const officeId = page.props.auth.user.office_id;

const totalApplications = computed(() => dashboardData.value?.total || 0)
const totalApproved = computed(() => dashboardData.value?.approved || 0)

const totalDeferred = computed(() => dashboardData.value?.deferred || 0)

const totalDraft = computed(() => dashboardData.value?.draft || 0)

const dashboardData = ref([])

const fetchDashboardData = async () => {
    try {

        const response = await axios.get('https://cps.denrcalabarzon.com/api/summary', {
            params: { user_id: userId, office_id: officeId, status: STATUS_ENDORSED_RED }

        });

        dashboardData.value = response.data;
    } catch (error) {
        console.error('Failed to fetch dashboard data:', error);
    }
};
const summaryCards = computed(() => [
    { label: 'Total Applications', value: totalApplications.value, icon: total_icon },
    { label: 'For Review', value: totalDraft.value, icon: review_icon },
    { label: 'Approved', value: totalApproved.value, icon: approved_icon },
    { label: 'Deferred', value: totalDeferred.value, icon: reject_icon },
])
onMounted(() => {
    fetchDashboardData()
})
</script>

<template>

    <Head title="Regional Executive Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 rounded-xl p-4">
            <!-- Header Section -->


            <!-- Table Box Section -->
            <div class="box">
                <div class="flex items-center gap-2 text-sm">
                    <List class="h-5 w-5" />
                    <h1 class="text-xl font-semibold"> Regional Executive Director Dashboard</h1>
                </div>
                <Fieldset legend="Dashboard Summary" class="mb-6">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <Card v-for="item in summaryCards" :key="item.label"
                            class="group rounded-2xl shadow-lg overflow-hidden border-0 active:scale-95 transition-transform rounded-lg"
                            :pt="{ body: { class: 'p-0' }, content: { class: 'p-0' } }">
                            <template #content>
                                <div class="relative flex min-h-[104px] md:min-h-[112px] flex-col justify-between p-3.5 md:p-4 text-white bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg">
                                    <!-- LABEL: pr-12 keeps text clear of the icon -->
                                    <p class="pr-12 text-xs md:text-sm font-medium leading-tight">
                                        {{ item.label }}
                                    </p>

                                    <!-- NUMBER -->
                                    <h2 class="text-3xl font-bold leading-none">{{ item.value }}</h2>

                                    <!-- ICON CHIP -->
                                    <div class="absolute right-3 top-3 flex h-10 w-10 md:h-12 md:w-12 items-center
                   justify-center rounded-xl bg-white/95 shadow-sm">
                                        <img :src="item.icon" alt="" class="h-7 w-7 md:h-9 md:w-9 object-contain transition-transform
                     duration-300 md:group-hover:scale-110" />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </Fieldset>


                <red_table />
            </div>
        </div>

    </AppLayout>
</template>
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
</style>
