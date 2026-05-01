<script setup lang="ts">
import { ref, watch } from 'vue'

import FloatLabel from 'primevue/floatlabel'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import DatePicker from 'primevue/datepicker'

import {
    lettersNumbersDashUppercase
} from '../pages/applications/forms/composables/useUppercaseLettersOnly'

const emit = defineEmits(['cancel', 'save'])

/* -------------------------------------------------------
| PROPS
------------------------------------------------------- */
const props = defineProps({
    supplierData: {
        type: Array,
        default: () => []
    }
})

/* -------------------------------------------------------
| STATE
------------------------------------------------------- */
const suppliers = ref<any[]>([])

/* -------------------------------------------------------
| CONSTANT TEMPLATE
------------------------------------------------------- */
const emptySupplier = () => ({
    supplier_name: '',
    supplier_address: '',
    permit_to_sell_no: '',
    issued_date: null,
    validity_date: null,
    issued_by: '',
    brands: [
        {
            name: '',
            models: [
                {
                    model: '',
                    quantity: 1
                }
            ]
        }
    ]
})

/* -------------------------------------------------------
| SYNC FROM PARENT (EDIT MODE SUPPORT)
------------------------------------------------------- */
watch(
    () => props.supplierData,
    (data) => {
        if (data?.length) {
            suppliers.value = data.map((s: any) => ({
                ...emptySupplier(),
                ...s,
                issued_date: s.issued_date ? new Date(s.issued_date) : null,
                validity_date: s.validity_date || s.valid_until
                    ? new Date(s.validity_date || s.valid_until)
                    : null,
                brands: s.brands?.length ? s.brands : emptySupplier().brands
            }))
        } else {
            suppliers.value = [emptySupplier()]
        }
    },
    { immediate: true }
)

/* -------------------------------------------------------
| SUPPLIER ACTIONS
------------------------------------------------------- */
const addSupplier = () => {
    suppliers.value.push(emptySupplier())
}

const removeSupplier = (index: number) => {
    if (suppliers.value.length === 1) return
    suppliers.value.splice(index, 1)
}

/* -------------------------------------------------------
| BRAND ACTIONS
------------------------------------------------------- */
const addBrand = (supplier: any) => {
    supplier.brands.push({
        name: '',
        models: [{ model: '', quantity: 1 }]
    })
}

const removeBrand = (supplier: any, index: number) => {
    if (supplier.brands.length === 1) return
    supplier.brands.splice(index, 1)
}

/* -------------------------------------------------------
| MODEL ACTIONS
------------------------------------------------------- */
const addModel = (brand: any) => {
    brand.models.push({
        model: '',
        quantity: 1
    })
}

const removeModel = (brand: any, index: number) => {
    if (brand.models.length === 1) return
    brand.models.splice(index, 1)
}

/* -------------------------------------------------------
| FORMAT DATE FOR BACKEND
------------------------------------------------------- */
const formatDate = (date: Date | null) => {
    if (!date) return null
    return date.toISOString().slice(0, 19).replace('T', ' ')
}

/* -------------------------------------------------------
| SAVE
------------------------------------------------------- */
const save = () => {
    const formatted = suppliers.value.map((s: any) => ({
        ...s,
        issued_date: formatDate(s.issued_date),
        validity_date: formatDate(s.validity_date)
    }))

    emit('save', formatted)
}

/* -------------------------------------------------------
| CANCEL
------------------------------------------------------- */
const cancel = () => emit('cancel')
</script>

<template>
    <div class="space-y-8">

        <!-- SUPPLIERS -->
        <div
            v-for="(supplier, sIndex) in suppliers"
            :key="sIndex"
            class="border rounded-lg p-6 space-y-6 bg-gray-50"
        >

            <div class="flex justify-between items-center">
                <h3 class="font-semibold text-lg">
                    Supplier {{ sIndex + 1 }}
                </h3>

                <Button
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    @click="removeSupplier(sIndex)"
                    v-if="suppliers.length > 1"
                />
            </div>

            <!-- SUPPLIER INFO -->
            <div class="grid grid-cols-2 gap-4">

                <FloatLabel>
                    <InputText v-model="supplier.supplier_name" class="w-full" />
                    <label>Supplier Name</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="supplier.supplier_address" class="w-full" />
                    <label>Supplier Address</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText
                        v-model="supplier.permit_to_sell_no"
                        class="w-full"
                        
                    />
                    <label>Permit To Sell No</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText v-model="supplier.issued_by" class="w-full" />
                    <label>Issued By</label>
                </FloatLabel>

                <FloatLabel>
                    <DatePicker
                        v-model="supplier.issued_date"
                        date-format="yy-mm-dd"
                        class="w-full"
                    />
                    <label>Issued Date</label>
                </FloatLabel>

                <FloatLabel>
                    <DatePicker
                        v-model="supplier.validity_date"
                        date-format="yy-mm-dd"
                        class="w-full"
                    />
                    <label>Valid Until</label>
                </FloatLabel>

            </div>

            <!-- BRANDS -->
            <div
                v-for="(brand, bIndex) in supplier.brands"
                :key="bIndex"
                class="border rounded-md p-5 bg-white space-y-4"
            >

                <div class="flex justify-between items-center">
                    <h4 class="font-semibold">
                        Brand {{ bIndex + 1 }}
                    </h4>

                    <Button
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        @click="removeBrand(supplier, bIndex)"
                        v-if="supplier.brands.length > 1"
                    />
                </div>

                <!-- BRAND NAME -->
                <FloatLabel>
                    <InputText v-model="brand.name" class="w-full" />
                    <label>Brand Name</label>
                </FloatLabel>

                <!-- MODELS -->
                <DataTable :value="brand.models" class="p-datatable-sm">

                    <Column header="Model">
                        <template #body="{ data }">
                            <InputText v-model="data.model" class="w-full" />
                        </template>
                    </Column>

                    <Column header="Quantity">
                        <template #body="{ data }">
                            <InputNumber v-model="data.quantity" :min="1" class="w-full" />
                        </template>
                    </Column>

                    <Column header="Action" style="width:80px">
                        <template #body="{ index }">
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                @click="removeModel(brand, index)"
                                v-if="brand.models.length > 1"
                            />
                        </template>
                    </Column>

                </DataTable>

                <Button
                    label="Add Model"
                    icon="pi pi-plus"
                    text
                    class="bg-teal-900"
                    @click="addModel(brand)"
                />

            </div>

            <Button
                label="Add Brand"
                icon="pi pi-plus"
                outlined
                @click="addBrand(supplier)"
            />

        </div>

        <!-- ADD SUPPLIER -->
        <Button
            label="Add Supplier"
            icon="pi pi-plus"
            severity="secondary"
            outlined
            @click="addSupplier"
        />

        <!-- ACTIONS -->
        <div class="flex justify-end gap-3 pt-6 border-t">

            <Button
                label="Cancel"
                severity="secondary"
                outlined
                @click="cancel"
            />

            <Button
                label="Save"
                icon="pi pi-save"
                @click="save"
            />

        </div>

    </div>
</template>