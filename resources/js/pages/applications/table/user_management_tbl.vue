<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { SquarePen, UserCheck } from 'lucide-vue-next';
import Button from 'primevue/button';

import { ProductService } from '../service/ProductService';

const page = usePage();
const userId = page.props.auth.user.id;

// ✅ FIXED: proper state variable
const users = ref<any[]>([]);

const filters = ref({
    global: { value: null, matchMode: 'contains' },
    
});


const impersonateUser = async (userId: number) => {
    try {

        const response = await axios.post(
            route('impersonate.start'),
            {
                id: userId
            }
        )

        // redirect to dynamic dashboard
        window.location.href = response.data.redirect

    } catch (error) {

        console.error(error)

        alert('Failed to impersonate user.')
    }
}

onMounted(() => {
    ProductService.getUserList(userId).then((data) => (users.value = data));
});

</script>

<template>
    <div class="flex flex-col gap-4 rounded-xl p-4">


        <DataTable ref="dt" size="small" :value="users" dataKey="id" stripedRows showGridlines :paginator="true"
            :rows="10" :filters="filters" filterDisplay="menu"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products" responsiveLayout="scroll"
            class="w-full text-sm" tableStyle="min-width: 60rem">
            <!-- HEADER SEARCH -->
            <template #header>
                <div class="flex justify-between items-center">
                    <!-- <h2 class="font-semibold">User Management</h2> -->

                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="filters.global.value" placeholder="Search users..." />
                    </IconField>
                </div>
            </template>

            <!-- NAME -->
            <Column header="Name" sortable>
                <template #body="{ data }">
                    {{ data.name }}
                </template>
            </Column>

            <!-- EMAIL -->
            <Column field="uname" header="Username" sortable />

            <!-- OFFICE -->
            <Column header="Office" sortable>
                <template #body="{ data }">
                    {{ data.office_name ?? data.office?.name }}
                </template>
            </Column>

            <!-- ROLE -->
            <Column header="Role" sortable>
                <template #body="{ data }">
                    {{ data.role_name ?? data.role?.name }}
                </template>
            </Column>
            <Column header="Action" sortable>
                <template #body="{ data }">
                    <Link :href="route('user-management.edit', { id: data.id })"
                        class="mr-2 inline-flex items-center justify-center bg-orange-700 text-white rounded-md px-3 py-2 hover:bg-orange-600">
                        <SquarePen :size="16" />
                    </Link>
                    <Button @click="impersonateUser(data.id)"
                        class="mr-2 inline-flex items-center justify-center bg-blue-700 text-white rounded-md px-3 py-2 hover:bg-blue-600">
                        <UserCheck :size="16" class="mr-1" />
                        Impersonate
                    </Button>

                </template>
            </Column>

        </DataTable>

    </div>
</template>