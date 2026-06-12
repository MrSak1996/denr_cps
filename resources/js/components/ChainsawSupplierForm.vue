<script setup lang="ts">
import { ref, watch } from 'vue'

import FloatLabel from 'primevue/floatlabel'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import DatePicker from 'primevue/datepicker'

const emit = defineEmits(['cancel', 'save'])

const props = defineProps({
    supplierData: {
        type: Array,
        default: () => []
    }
})

const emptySupplier = () => ({
    supplier_name: '',
    supplier_address: '',
    permit_to_sell_no: '',
    issued_by: '',
    issued_date: null,
    permit_validity: null,

    rows: [
    {
        id: null,
        brand_name: '',
        model: '',
        quantity: 1
    }
]
})

const suppliers = ref<any[]>([
    emptySupplier()
])

// watch(
//     () => props.supplierData,
//     (data: any) => {

//         if (!data?.length) {
//             suppliers.value = [emptySupplier()]
//             return
//         }

//         suppliers.value = data.map((supplier: any) => {

//             const rows: any[] = []

//             supplier.brands?.forEach((brand: any) => {
//                 brand.models?.forEach((model: any) => {
//                     rows.push({
//                         brand_name: brand.name,
//                         model: model.model,
//                         quantity: model.quantity
//                     })
//                 })
//             })

//             return {
//                 supplier_name: supplier.supplier_name || '',
//                 supplier_address: supplier.supplier_address || '',
//                 permit_to_sell_no: supplier.permit_to_sell_no || '',
//                 issued_by: supplier.issued_by || '',

//                 issued_date: supplier.issued_date
//                     ? new Date(supplier.issued_date)
//                     : null,

//                 permit_validity:
//                     supplier.permit_validity || supplier.valid_until
//                         ? new Date(
//                             supplier.permit_validity ||
//                             supplier.valid_until
//                         )
//                         : null,

//                 rows: rows.length
//                     ? rows
//                     : [
//                         {
//                             brand_name: '',
//                             model: '',
//                             quantity: 1
//                         }
//                     ]
//             }
//         })
//     },
//     { immediate: true }
// )

watch(
    () => props.supplierData,
    (data: any) => {

        if (!data?.length) {
            suppliers.value = [emptySupplier()]
            return
        }

        suppliers.value = data.map((supplier: any) => ({

            id: supplier.id,

            supplier_name: supplier.supplier_name || '',
            supplier_address: supplier.supplier_address || '',
            permit_to_sell_no: supplier.permit_to_sell_no || '',
            issued_by: supplier.issued_by || '',

            issued_date: supplier.issued_date
                ? new Date(supplier.issued_date)
                : null,

            permit_validity: supplier.valid_until
                ? new Date(supplier.valid_until)
                : null,

            rows:
                supplier.rows?.map((row: any) => ({
                    id: row.id,
                    brand_name: row.brand_name || '',
                    model: row.model ?? row.model_name ?? '',
                    quantity: row.quantity ?? 1
                })) || [
                    {
                        brand_name: '',
                        model: '',
                        quantity: 1
                    }
                ]
        }))
    },
    { immediate: true }
)

const addSupplier = () => {
    suppliers.value.push(emptySupplier())
}

const removeSupplier = (index: number) => {
    if (suppliers.value.length === 1) return

    suppliers.value.splice(index, 1)
}

const addRow = (supplier: any) => {
    supplier.rows.push({
        id: null,
        brand_name: '',
        model: '',
        quantity: 1
    })
}

const removeRow = (supplier: any, index: number) => {
    if (supplier.rows.length === 1) return

    supplier.rows.splice(index, 1)
}

const formatDate = (date: Date | null) => {
    if (!date) return null

    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
}

const save = () => {

    const payload = suppliers.value.map(supplier => ({
        id: supplier.id ?? null,   // <-- Supplier ID

        supplier_name: supplier.supplier_name,
        supplier_address: supplier.supplier_address,
        permit_to_sell_no: supplier.permit_to_sell_no,
        issued_by: supplier.issued_by,
        issued_date: formatDate(supplier.issued_date),
        permit_validity: formatDate(supplier.permit_validity),

        rows: supplier.rows.map(row => ({
            id: row.id ?? null,    // <-- Brand/Model ID
            brand_name: row.brand_name,
            model_name: row.model,
            quantity: row.quantity
        }))
    }));

    emit('save', payload);
}
const cancel = () => {
    emit('cancel')
}
</script>

<template>
    <div class="space-y-6">

        <div
            v-for="(supplier, sIndex) in suppliers"
            :key="sIndex"
            class="border rounded-xl p-6 bg-gray-50 shadow-sm"
        >

            <!-- HEADER -->
            <div class="flex justify-between items-center mb-6">

                <h3 class="font-semibold text-lg">
                    Supplier {{ sIndex + 1 }}
                </h3>

                <Button
                    v-if="suppliers.length > 1"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    @click="removeSupplier(sIndex)"
                />

            </div>

            <!-- SUPPLIER INFO -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

                <FloatLabel>
                    <InputText
                        v-model="supplier.supplier_name"
                        class="w-full"
                    />
                    <label>Supplier Name</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText
                        v-model="supplier.supplier_address"
                        class="w-full"
                    />
                    <label>Supplier Address</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText
                        v-model="supplier.permit_to_sell_no"
                        class="w-full"
                    />
                    <label>Permit To Sell No/Re-Sell Chainsaw No.</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText
                        v-model="supplier.issued_by"
                        class="w-full"
                    />
                    <label>Issued By</label>
                </FloatLabel>

                <FloatLabel>
                    <DatePicker
                        v-model="supplier.issued_date"
                        show-icon
                        date-format="yy-mm-dd"
                        class="w-full"
                    />
                    <label>Issued Date</label>
                </FloatLabel>

                <FloatLabel>
                    <DatePicker
                        v-model="supplier.permit_validity"
                        show-icon
                        date-format="yy-mm-dd"
                        class="w-full"
                    />
                    <label>Valid Until</label>
                </FloatLabel>

            </div>

            <!-- MODELS TABLE -->
            <div class="bg-white rounded-lg border p-4">

                <div class="flex justify-between items-center mb-4">

                    <h4 class="font-medium">
                        Chainsaw Models
                    </h4>

                    <Button
                        label="Add Model"
                        icon="pi pi-plus"
                        size="small"
                        @click="addRow(supplier)"
                    />

                </div>

                <DataTable
                    :value="supplier.rows"
                    class="p-datatable-sm"
                    responsiveLayout="scroll"
                >

                    <Column
                        field="brand_name"
                        header="Brand"
                    >
                        <template #body="{ data }">
                            <InputText
                                v-model="data.brand_name"
                                class="w-full"
                            />
                        </template>
                    </Column>

                    <Column
                        field="model"
                        header="Model"
                    >
                        <template #body="{ data }">
                            <InputText
                                v-model="data.model"
                                class="w-full"
                            />
                        </template>
                    </Column>

                    <Column
                        field="quantity"
                        header="Quantity"
                        style="width:180px"
                    >
                        <template #body="{ data }">
                            <InputNumber
                                v-model="data.quantity"
                                :min="1"
                                class="w-full"
                            />
                        </template>
                    </Column>

                    <Column
                        header=""
                        style="width:80px"
                    >
                        <template #body="{ index }">

                            <Button
                                v-if="supplier.rows.length > 1"
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                @click="removeRow(supplier, index)"
                            />

                        </template>
                    </Column>

                </DataTable>

            </div>

        </div>

        <!-- ADD SUPPLIER -->
        <Button
            label="Add Supplier"
            icon="pi pi-plus"
            outlined
            @click="addSupplier"
        />

        <!-- ACTIONS -->
        <div class="flex justify-end gap-3 border-t pt-5">

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