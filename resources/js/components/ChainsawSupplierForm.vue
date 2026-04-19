<script setup lang="ts">
import { ref } from 'vue'
import FloatLabel from 'primevue/floatlabel'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import DatePicker from 'primevue/datepicker'
import { lettersOnlyUppercase, lettersNumbersDashUppercase, emailUppercase} from '../pages/applications/forms/composables/useUppercaseLettersOnly';
const emit = defineEmits(['cancel', 'save'])


// ------------------------------
// Directives
// ------------------------------
const vLettersOnlyUppercase = lettersOnlyUppercase;
const vLettersNumbersDashUppercase = lettersNumbersDashUppercase;
const vEmailUppercase = emailUppercase;

const suppliers = ref([
    {
        supplier_name: '',
        supplier_address: '',
        permit_to_sell_no: '',
        issued_date: '',
        validity_date: '',
        issued_by: '',
        brands: [
            {
                name: '',
                models: [
                    { model: '', quantity: 1, serial_no: '' }
                ]
            }
        ]
    }
])

/* ---------------------------
Supplier Functions
----------------------------*/
const addSupplier = () => {
    suppliers.value.push({
        supplier_name: '',
        supplier_address: '',
        permit_to_sell_no: '',
        issued_date: '',
        validity_date: '',
        issued_by: '',
        brands: [
            {
                name: '',
                models: [{ model: '', quantity: 1, serial_no: '' }]
            }
        ]
    })
}

const removeSupplier = (index: number) => {
    suppliers.value.splice(index, 1)
}

/* ---------------------------
Brand Functions
----------------------------*/
const addBrand = (supplier: any) => {
    supplier.brands.push({
        name: '',
        models: [{ model: '', quantity: 1, serial_no: '' }]
    })
}

const removeBrand = (supplier: any, index: number) => {
    supplier.brands.splice(index, 1)
}

/* ---------------------------
Model Functions
----------------------------*/
const addModel = (brand: any) => {
    brand.models.push({
        model: '',
        quantity: 1,
        serial_no: ''
    })
}

const removeModel = (brand: any, index: number) => {
    brand.models.splice(index, 1)
}

/* ---------------------------
Actions
----------------------------*/
const save = () => {
    emit('save', suppliers.value)
}

const cancel = () => {
    emit('cancel')
}
</script>

<template>

    <div class="space-y-8">

        <!-- SUPPLIERS -->
        <div v-for="(supplier, sIndex) in suppliers" :key="sIndex" class="border rounded-lg p-6 space-y-6 bg-gray-50">

            <div class="flex justify-between items-center">
                <h3 class="font-semibold text-lg">
                    Supplier {{ sIndex + 1 }}
                </h3>

                <Button icon="pi pi-trash" severity="danger" text @click="removeSupplier(sIndex)"
                    v-if="suppliers.length > 1" />
            </div>


            <!-- Supplier Info -->
            <div class="grid grid-cols-2 gap-4">

                <FloatLabel>
                    <InputText v-model="supplier.supplier_name" v-letters-only-uppercase class="w-full" />
                    <label>Supplier Name</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="supplier.supplier_address" v-letters-only-uppercase class="w-full" />
                    <label>Supplier Address</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="supplier.permit_to_sell_no" class="w-full"v-letters-numbers-dash-uppercase  />
                    <label>Permit To Sell No</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="supplier.issued_by" class="w-full" v-letters-only-uppercase />
                    <label>Issued By</label>
                </FloatLabel>

                <FloatLabel>
                    <DatePicker v-model="supplier.issued_date" date-format="yy-mm-dd" class="w-full" />
                    <label>Issued Date</label>
                </FloatLabel>

                <FloatLabel>
                    <DatePicker v-model="supplier.validity_date" date-format="yy-mm-dd" class="w-full" />
                    <label>Valid Until</label>
                </FloatLabel>

            </div>


            <!-- BRANDS -->
            <div v-for="(brand, bIndex) in supplier.brands" :key="bIndex"
                class="border rounded-md p-5 bg-white space-y-4">

                <div class="flex justify-between items-center">

                    <h4 class="font-semibold text-md">
                        Brand {{ bIndex + 1 }}
                    </h4>

                    <Button icon="pi pi-trash" severity="danger" text @click="removeBrand(supplier, bIndex)"
                        v-if="supplier.brands.length > 1" />

                </div>

                <!-- Brand Name -->
                <FloatLabel>
                    <InputText v-model="brand.name" class="w-full" v-letters-only-uppercase/>
                    <label>Brand Name</label>
                </FloatLabel>


                <!-- MODELS TABLE -->
                <DataTable :value="brand.models" responsiveLayout="scroll" class="p-datatable-sm">

                    <Column header="Model">
                        <template #body="slotProps">
                            <InputText v-model="slotProps.data.model" v-letters-only-uppercase class="w-full" />
                        </template>
                    </Column>

                    <Column header="Quantity">
                        <template #body="slotProps">
                            <InputNumber v-model="slotProps.data.quantity" :min="1" class="w-full" />
                        </template>
                    </Column>

                    <Column header="Serial No">
                        <template #body="slotProps">
                            <InputText v-model="slotProps.data.serial_no" class="w-full" v-letters-only-uppercase/>
                        </template>
                    </Column>

                    <Column header="Action" style="width:80px">
                        <template #body="slotProps">
                            <Button icon="pi pi-trash" severity="danger" text
                                @click="removeModel(brand, slotProps.index)" v-if="brand.models.length > 1" />
                        </template>
                    </Column>

                </DataTable>


                <!-- ADD MODEL -->
                <Button label="Add Model" icon="pi pi-plus" text class="bg-teal-900" @click="addModel(brand)" />

            </div>

            <Button label="Add Brand" icon="pi pi-plus" outlined @click="addBrand(supplier)" />

        </div>


        <!-- ADD SUPPLIER -->
        <Button label="Add Supplier" icon="pi pi-plus" severity="secondary" outlined @click="addSupplier" />


        <!-- ACTION BUTTONS -->
        <div class="flex justify-end gap-3 pt-6 border-t">

            <Button label="Cancel" severity="secondary" outlined @click="cancel" />

            <Button label="Save" icon="pi pi-save" @click="save" />

        </div>

    </div>

</template>