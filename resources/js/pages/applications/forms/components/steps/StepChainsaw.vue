<script setup lang="ts">
import { ref, computed } from 'vue'
import Select from 'primevue/select'
import Fieldset from 'primevue/fieldset'
import Dialog from 'primevue/dialog'
import FloatLabel from 'primevue/floatlabel'
import { Button } from '@/components/ui/button'
import ChainsawSupplierForm from '@/components/ChainsawSupplierForm.vue'
import { MonitorUp } from 'lucide-vue-next'

/* -------------------------------------------------------
| EMITS (FIXED)
------------------------------------------------------- */
const emit = defineEmits(['next', 'back', 'supplierSaved'])

/* -------------------------------------------------------
| PROPS
------------------------------------------------------- */
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    application_type: String,
    currentStep: Number,
    isProcessing: {
        type: Boolean,
        default: false
    }
})

/* -------------------------------------------------------
| STATE
------------------------------------------------------- */
const defaultSupplierDialog = ref(false)

const files = ref({
    mayorDTI: null,
    affidavit: null,
    permit: null
})

/* -------------------------------------------------------
| OPTIONS
------------------------------------------------------- */
const options = [
    'For cutting of trees with legal permit',
    'For post-calamity clearing operations',
    'For farm lot/tree orchard maintenance',
    'For maintenance of trees/vegetation within private property',
    'For cutting/trimming of trees posing danger within a private property',
    'For selling / re-selling',
    'For cutting of trees to be used for house repair/perimeter fencing/residential area development',
    'For commercial use',
    'Forestry/landscaping service provider',
    'Other legal purpose(s)',
    'Other Supporting Documents',
]

/* -------------------------------------------------------
| COMPUTED UPLOAD TYPE
------------------------------------------------------- */
const uploadType = computed(() => {
    const purpose = props.form.purpose

    if (['For selling / re-selling', 'Forestry/landscaping service provider'].includes(purpose)) {
        return 'mayorDTI'
    }
    if (purpose === 'Other legal purpose(s)') {
        return 'affidavit'
    }
    if (purpose === 'Other Supporting Documents') {
        return 'permit'
    }
    return null
})

/* -------------------------------------------------------
| FILE HANDLER
------------------------------------------------------- */
const handleFileUpload = (event: Event, field: string | null) => {
    if (!field) return

    const file = (event.target as HTMLInputElement).files?.[0]
    if (!file) return

    if (file.type !== 'application/pdf') {
        alert('Only PDF files are allowed')
        return
    }

    if (file.size > 5 * 1024 * 1024) {
        alert('Max file size is 5MB')
        return
    }

    files.value[field] = file
}

/* -------------------------------------------------------
| SUPPLIER HANDLER (FIXED EMIT)
------------------------------------------------------- */
const handleSupplierSaved = (data: any) => {
    emit('supplierSaved', data)
    defaultSupplierDialog.value = false
}

/* -------------------------------------------------------
| STEP SUBMIT
------------------------------------------------------- */
const submitStep = () => {
    if (props.isProcessing) return

    emit('next', {
        purpose: props.form.purpose,
        application_type: props.application_type,
        ...files.value
    })
}
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-xl font-semibold">Chainsaw Information</h2>

        <Fieldset legend="Chainsaw Information">

            <!-- Supplier Dialog -->
            <Dialog v-model:visible="defaultSupplierDialog" modal header="Supplier Form">
                <ChainsawSupplierForm
                    @cancel="defaultSupplierDialog = false"
                    @save="handleSupplierSaved"
                />
            </Dialog>

            <!-- Open Dialog -->
            <Button
                class="w-full bg-blue-900 hover:bg-blue-700"
                @click="defaultSupplierDialog = true"
            >
                Chainsaw Supplier Form
            </Button>

            <!-- Purpose -->
            <div class="mt-6">
                <FloatLabel>
                    <Select v-model="props.form.purpose" :options="options" class="w-full" />
                    <label>Purpose of Purchase</label>
                </FloatLabel>
            </div>

            <!-- Dynamic Upload -->
            <div v-if="uploadType" class="mt-6">
                <label class="text-sm font-medium">
                    Upload Required Document
                </label>

                <div
                    class="relative mt-2 flex h-[330px] w-full cursor-pointer flex-col items-center justify-center rounded-xl border-4 border-dashed border-blue-400 bg-white transition hover:bg-blue-50"
                >
                    <MonitorUp :size="64" class="mb-4 h-12 w-12 text-blue-400" />

                    <p class="mb-2 text-center text-sm text-gray-700">
                        Drag & drop file or click to upload
                    </p>
                    <p class="text-center text-xs text-gray-400">
                        PDF only, max 5MB
                    </p>

                    <input
                        type="file"
                        accept="application/pdf"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        @change="(e) => handleFileUpload(e, uploadType)"
                    />
                </div>
            </div>

            <!-- No Upload -->
            <div v-else class="p-4 text-gray-600 bg-gray-100 rounded">
                No additional documents required.
            </div>

            <!-- Actions -->
            <div
                :class="[
                    'w-full pt-6',
                    currentStep > 1 ? 'grid grid-cols-2 gap-4' : 'flex justify-end'
                ]"
            >
                <Button
                    v-if="currentStep > 1"
                    @click="$emit('back')"
                    class="w-full bg-gray-300 hover:bg-gray-400"
                >
                    Back
                </Button>

                <Button
                    :disabled="isProcessing"
                    class="w-full bg-green-900 text-white transition-colors hover:bg-green-500 text-white"
                    @click="submitStep"
                >
                    {{ isProcessing ? 'Saving...' : 'Save & Continue' }}
                </Button>
                
            </div>

        </Fieldset>
    </div>
</template>
