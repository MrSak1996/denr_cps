<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
    suppliers: any[]
    applicationData: any
}>()

const emit = defineEmits(['cancel','save'])

const cancel = () => {
    emit('cancel')
}

const brands = ref([
{
    name:'',
    models:[
        {model:'',quantity:1}
    ]
}
])

const save = () => {

    const supplierPayload = [
        {
            supplier_name: props.applicationData.supplier_name,
            supplier_address: props.applicationData.supplier_address,
            issued_date: props.applicationData.issued_date,
            valid_until: props.applicationData.validity_date,
            brands: brands.value
        }
    ]

    emit('save', supplierPayload)
}




// BRAND ACTIONS
const addBrand = () => {
    brands.value.push({
        name: '',
        models: [{ model: '', quantity: 1 }]
    })
}

const removeBrand = (index: number) => {
    if (brands.value.length > 1) {
        brands.value.splice(index, 1)
    }
}

// MODEL ACTIONS
const addModel = (brandIndex: number) => {
    brands.value[brandIndex].models.push({ model: '', quantity: 1 })
}

const removeModel = (brandIndex: number, modelIndex: number) => {
    const models = brands.value[brandIndex].models
    if (models.length > 1) {
        models.splice(modelIndex, 1)
    }
}
</script>
<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <FloatLabel class="mt-5">
                <InputText v-model="applicationData.supplier_name" v-letters-numbers-dash-uppercase class="w-full" />
                <label>Supplier Name</label>
            </FloatLabel>

            <FloatLabel class="mt-5">
                <InputText v-model="applicationData.supplier_address" rows="3" class="w-full" />
                <label>Supplier Address</label>
            </FloatLabel>
        </div>
        <div style="height:300px; overflow-y: auto;">
            <div v-for="(brand, bIndex) in suppliers" :key="bIndex"
                class="bg-white border rounded-lg shadow-sm p-5 space-y-4">
                <!-- BRAND HEADER -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <Button icon="pi pi-times" severity="danger" text
                        class="mt-3 bg-red-900 hover:bg-red-700 self-start" @click="removeBrand(bIndex)"
                        v-if="brands.length > 1">
                        <Trash2 :size="15" />
                    </Button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-1 gap-3">
                    <FloatLabel class="mt-3">
                        <InputText v-model="brand.name" v-letters-numbers-dash-uppercase class="w-full" />
                        <label>Brand Name</label>
                    </FloatLabel>
                </div>

                <!-- MODELS TABLE -->
                <DataTable :value="brand.models" responsiveLayout="scroll" class="border rounded-lg overflow-hidden">

                    <Column header="Model"
                        :headerStyle="{ backgroundColor: '#0D47A1', color: '#fff', fontWeight: 'bold' }">
                        <template #body="{ data }">
                            <InputText v-model="data.model" v-letters-numbers-dash-uppercase placeholder="Enter model"
                                class="w-full" />
                        </template>
                    </Column>

                    <Column header="Quantity" style="width:70px"
                        :headerStyle="{ backgroundColor: '#0D47A1', color: '#fff', fontWeight: 'bold', width: '70px' }">
                        <template #body="{ data }">
                            <InputNumber v-model="data.quantity" :min="1" inputClass="w-full" />
                        </template>
                    </Column>

                    <!-- PERMIT TO SELL -->
                    <Column header="Permit to Sell No"
                        :headerStyle="{ backgroundColor: '#0D47A1', color: '#fff', fontWeight: 'bold' }">
                        <template #body="{ data }">
                            <InputText v-model="data.permit_to_sell_no" placeholder="Enter permit no" class="w-full" />
                        </template>
                    </Column>

                    <!-- ISSUED DATE -->
                    <Column header="Issued On" style="width:150px"
                        :headerStyle="{ backgroundColor: '#0D47A1', color: '#fff', fontWeight: 'bold' }">
                        <template #body="{ data }">
                            <DatePicker class="w-full" v-model="applicationData.issued_date" :show-icon="true"
                                date-format="yy-mm-dd" />
                        </template>
                    </Column>

                    <!-- VALID UNTIL -->
                    <Column header="Valid Until" style="width:150px"
                        :headerStyle="{ backgroundColor: '#0D47A1', color: '#fff', fontWeight: 'bold' }">
                        <template #body="{ data }">
                            <DatePicker class="w-full" v-model="applicationData.validity_date" :show-icon="true"
                                date-format="yy-mm-dd" />
                        </template>
                    </Column>

                    <!-- ISSUED BY -->
                    <Column header="Issued By"
                        :headerStyle="{ backgroundColor: '#0D47A1', color: '#fff', fontWeight: 'bold' }">
                        <template #body="{ data }">
                            <InputText v-model="data.issued_by" placeholder="Enter issuer" class="w-full" />
                        </template>
                    </Column>

                    <!-- ACTIONS -->
                    <Column header="Actions" style="width: 120px"
                        :headerStyle="{ backgroundColor: '#0D47A1', color: '#fff', fontWeight: 'bold' }">
                        <template #body="{ index }">
                            <div class="flex gap-2 justify-center">
                                <Button icon="pi pi-plus" severity="success" text @click="addModel(bIndex)"
                                    class="bg-green-900 hover:bg-green-700">
                                    <CirclePlus :size="15" />
                                </Button>
                                <Button icon="pi pi-times" severity="danger" text @click="removeModel(bIndex, index)"
                                    v-if="brand.models.length > 1" class="bg-red-900 hover:bg-red-700">
                                    <Trash2 :size="15" />
                                </Button>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- ADD BRAND BUTTON -->
            <div class="flex justify-end">
                <Button icon="pi pi-plus" label="Add Brand" class="bg-green-900 hover:bg-green-700" @click="addBrand">
                    <CirclePlus :size="15" />
                </Button>
            </div>




        </div>
        <div class="flex justify-end gap-3 pt-4 border-t">

            <Button label="Cancel" severity="secondary" outlined @click="cancel" />

            <Button label="Save" icon="pi pi-save" @click="save" />

        </div>
    </div>
</template>