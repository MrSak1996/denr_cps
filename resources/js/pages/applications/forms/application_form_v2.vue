<script setup lang="ts">
import { ref, onMounted, computed, defineAsyncComponent } from 'vue'
import { Head, router } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast';

import { usePrivacyConsent } from './composables/usePrivacyConsent'
import { useApplicationStepper } from './composables/useApplicationStepper'
import { saveApplicant, saveChainsaw, savePayment, saveSupplierInfo } from '../service/applicationApi'

const props = defineProps({
    application_id: [String, Number, null],
    step: Number,
    type: String
})

const { currentStep, next, prevStep } = useApplicationStepper(props.step)
const { showPrivacyDialog, hasAgreedPrivacy, checkConsent, accept } = usePrivacyConsent()

const form = ref({
    application_no: '',
    application_id: null
})

/* 🔥 SINGLE SOURCE OF TRUTH */
const application = ref<any>({})
const suppliers = ref<any[]>([])

const isProcessing = ref(false)
const defaultSupplierDialog = ref(false)
const toast = useToast();

/* Lazy components */
const StepApplicant = defineAsyncComponent(() => import('./components/steps/StepApplicant.vue'))
const StepChainsaw = defineAsyncComponent(() => import('./components/steps/StepChainsaw.vue'))
const StepPayment = defineAsyncComponent(() => import('./components/steps/StepPayment.vue'))
const StepReview = defineAsyncComponent(() => import('./components/steps/StepReview.vue'))

const activeComponent = computed(() => ({
    1: StepApplicant,
    2: StepChainsaw,
    3: StepPayment,
    4: StepReview
}[currentStep.value]))

/* 🔥 MAIN STEPPER LOGIC */
const nextStep = async (payload: any) => {
    if (isProcessing.value) return
    isProcessing.value = true

    try {
        if (currentStep.value === 1) {
            const res = await saveApplicant({
                ...payload,
                application_type: payload.application_type
            })

            application.value = res.data

            toast.add({ severity: 'success', summary: 'Saved', detail: 'Applicant saved', life: 3000 })
            next()
        }

        else if (currentStep.value === 2) {
            const res = await saveChainsaw({
                ...payload,
                suppliers: suppliers.value,
                application_type: payload.application_type,
            }, form.value.application_id)

            application.value = {
                ...application.value,
                ...res.data,
                suppliers: suppliers.value
            }

            toast.add({ severity: 'success', summary: 'Saved', detail: 'Chainsaw saved', life: 3000 })
            next()
        }

        else if (currentStep.value === 3) {
            const res = await savePayment({
                ...payload,
                application_type: payload.application_type,
            }, form.value.application_id)

            application.value = {
                ...application.value,
                ...res.data,
                suppliers: suppliers.value
            }

            toast.add({ severity: 'success', summary: 'Saved', detail: 'Payment saved', life: 3000 })
            next()
        }

    } catch (error: any) {
        console.error(error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error?.response?.data?.message || 'Something went wrong',
            life: 4000,
        })
    } finally {
        isProcessing.value = false
    }
}

/* Privacy */
const handleAcceptPrivacy = async () => {
    const data = await accept()
    form.value.application_no = data.application_no
    form.value.application_id = data.application_id
    showPrivacyDialog.value = false
}

/* Supplier */
const supplierSaved = async (data: any) => {
    suppliers.value = data

    await saveSupplierInfo({
        suppliers: data,
        application_id: form.value.application_id
    })
}

onMounted(() => {
    if (!form.value.application_id) showPrivacyDialog.value = true
    checkConsent(form.value.application_id)
})
</script>

<template>
    <Head title="Chainsaw Purchase System" />

    <AppLayout>
        <Toast />

        <div class="space-y-6 p-6">
            <component
                :is="activeComponent"
                :application="application"
                :form="form"
                :suppliers="suppliers"
                :application_type="type"
                :isProcessing="isProcessing"
                :currentStep="currentStep"
                @next="nextStep"
                @back="prevStep"
                @supplierSaved="supplierSaved"
            />
        </div>

        <Dialog v-model:visible="showPrivacyDialog" modal header="Privacy Consent">
            <div class="space-y-4 text-sm">
                <Checkbox v-model="hasAgreedPrivacy" binary />
                <label>I agree to Data Privacy Policy</label>
            </div>

            <template #footer>
                <Button label="Agree" @click="handleAcceptPrivacy" />
            </template>
        </Dialog>
    </AppLayout>
</template>