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
const lpdd_table = defineAsyncComponent(() => import('./applications/table/lpdd_chief_tbl.vue'));

const { officeTitle } = useOfficeTitle();
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'LPDD Chief Dashboard',
        href: '/lpdd-chief-dashboard',
    },
];


const page = usePage();
const userId = page.props.auth.user.id;
const officeId = page.props.auth.user.office_id;

const totalApplications = computed(() => dashboardData.value?.total || 0)
const totalApproved = computed(() => dashboardData.value?.approved || 0)

const totalDeferred = computed(() =>dashboardData.value?.deferred || 0)

const totalDraft = computed(() => dashboardData.value?.draft || 0)

const dashboardData = ref([])

const fetchDashboardData = async () => {
    try {

        const response = await axios.get('http://cps.denrcalabarzon.com/api/summary', {
            params: { user_id: userId,office_id:officeId }

        });

        dashboardData.value = response.data;
    } catch (error) {
        console.error('Failed to fetch dashboard data:', error);
    }
};
onMounted(() => {
    fetchDashboardData()
})
</script>

<template>

    <Head title="LPDD Chief Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 rounded-xl p-4">
            <!-- Header Section -->


            <!-- Table Box Section -->
            <div class="box">
                <div class="flex items-center gap-2 text-sm">
                    <List class="h-5 w-5" />
                    <h1 class="text-xl font-semibold"> LPDD Chief Dashboard</h1>
                </div>
                   <Fieldset legend="Dashboard Summary" class="mb-6">
                     <div class="grid gap-4 md:grid-cols-4">

                        <!-- TOTAL -->


                        <Card
                            class="rounded-2xl shadow-lg bg-gradient-to-r from-blue-500 to-blue-600 text-white overflow-hidden">
                            <template #content>
                                <div class="relative flex items-center justify-between">

                                    <!-- TEXT -->
                                    <div class="z-10 p-4">
                                        <p class="text-sm opacity-90">Total Applications</p>
                                        <h2 class="text-3xl font-bold">
                                            {{ totalApplications }}
                                        </h2>
                                    </div>

                                    <!-- IMAGE -->
                                    <img :src="total_icon" alt="Total Applications"
                                        class="absolute right-0 top-0 h-full w-auto object-cover opacity-90 transition-transform duration-500 ease-in-out hover:scale-150" />
                                </div>
                            </template>
                        </Card>

                        <!-- FOR REVIEW -->
                         <Card
                            class="rounded-2xl shadow-lg bg-gradient-to-r from-blue-500 to-blue-600 text-white overflow-hidden">
                            <template #content>
                                <div class="relative flex items-center justify-between">

                                    <!-- TEXT -->
                                    <div class="z-10 p-4">
                                        <p class="text-sm opacity-90">For Review</p>
                                        <h2 class="text-3xl font-bold">
                                            {{ totalDraft }}
                                        </h2>
                                    </div>

                                    <!-- IMAGE -->
                                    <img :src="review_icon" alt="For Review"
                                        class="absolute right-0 top-0 h-full w-auto object-cover opacity-90 transition-transform duration-500 ease-in-out hover:scale-150" />
                                </div>
                            </template>
                        </Card>

                        <!-- APPROVED -->
                         <Card
                            class="rounded-2xl shadow-lg bg-gradient-to-r from-blue-500 to-blue-600 text-white overflow-hidden">
                            <template #content>
                                <div class="relative flex items-center justify-between">

                                    <!-- TEXT -->
                                    <div class="z-10 p-4">
                                        <p class="text-sm opacity-90">Approved</p>
                                        <h2 class="text-3xl font-bold">
                                            {{ totalApproved }}
                                        </h2>
                                    </div>

                                    <!-- IMAGE -->
                                    <img :src="approved_icon" alt="Approved"
                                        class="absolute right-0 top-0 h-full w-auto object-cover opacity-90 transition-transform duration-500 ease-in-out hover:scale-150" />
                                </div>
                            </template>
                        </Card>

                        <Card
                            class="rounded-2xl shadow-lg bg-gradient-to-r from-blue-500 to-blue-600 text-white overflow-hidden">
                            <template #content>
                                <div class="relative flex items-center justify-between">

                                    <!-- TEXT -->
                                    <div class="z-10 p-4">
                                        <p class="text-sm opacity-90">Deferred</p>
                                        <h2 class="text-3xl font-bold">
                                            {{ totalDeferred }}
                                        </h2>
                                    </div>

                                    <!-- IMAGE -->
                                    <img :src="reject_icon" alt="Deferred"
                                        class="absolute right-0 top-0 h-full w-auto object-cover opacity-90 transition-transform duration-500 ease-in-out hover:scale-150" />
                                </div>
                            </template>
                        </Card>


                        

                    </div>
                </Fieldset>

                <lpdd_table />
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
