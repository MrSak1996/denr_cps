<script setup lang="ts">
import { useAppForm } from '@/composables/useAppForm';
import AppLayout from '@/layouts/AppLayout.vue';

import { type BreadcrumbItem } from '@/types';
import { Head,usePage } from '@inertiajs/vue3';
import { Info, List } from 'lucide-vue-next';

import Fieldset from 'primevue/fieldset';
import Card from 'primevue/card';
const { chainsaw_form, chainsaw } = useAppForm();
import { ref, onMounted, computed, defineAsyncComponent } from 'vue';
import axios from 'axios';
const UserTable = defineAsyncComponent(() =>
    import('../applications/table/user_management_tbl.vue')
);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pending Applications',
        href: '/applicants/pending_application',
    },
];

const page = usePage();
const userId = page.props.auth.user.id;
const officeId = page.props.auth.user.office_id;
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

    <Head title="Chainsaw Purchase System" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 rounded-xl p-4">
            <!-- Header Section -->


            <!-- Table Box Section -->
            <div class="box">
                <div class="flex items-center gap-2 text-sm">
                    <List class="h-5 w-5" />
                    <h1 class="text-xl font-semibold">User Management</h1>
                </div>
                <UserTable />
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
