<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import {
    computed,
    defineAsyncComponent,
    onMounted,
    ref,
    watch,
} from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

import {
    getApplicationReview,
    getRoutingHistory,
    saveApplicant,
    saveChainsaw,
    savePayment,
    saveSupplierInfo,
} from '../service/applicationApi'

import { useApplicationStepper } from './composables/useApplicationStepper'
import { usePrivacyConsent } from './composables/usePrivacyConsent'
import { UserRoundIcon } from 'lucide-vue-next'

/* -------------------- PAGE + PROPS -------------------- */

const page = usePage()
const userId = page.props.auth?.user?.id

const props = defineProps({
    application_id: [String, Number, null],
    step: Number,
    type: String,
    mode: String,
})

const isEdit = computed(() => props.mode === 'edit')
const isCreate = computed(() => props.mode === 'create')

/* -------------------- STEPPER -------------------- */

const { currentStep, next, prevStep } = useApplicationStepper(props.step)

/* keep step synced with URL */
watch(
    () => props.step,
    (step) => {
        currentStep.value = Number(step || 1)
    },
    { immediate: true }
)

/* -------------------- PRIVACY -------------------- */

const { showPrivacyDialog, hasAgreedPrivacy, checkConsent, accept } =
    usePrivacyConsent()

/* -------------------- STATE -------------------- */

const form = ref({
    application_no: '',
    application_id: null,
})

const application = ref<any>({})
const suppliers = ref<any[]>([])
const files = ref<any[]>([])
const routingHistory = ref([]);

const isProcessing = ref(false)
const defaultSupplierDialog = ref(false)

const toast = useToast()

/* -------------------- LAZY COMPONENTS -------------------- */

const StepApplicant = defineAsyncComponent(() =>
    import('./components/steps/StepApplicant.vue')
)
const StepChainsaw = defineAsyncComponent(() =>
    import('./components/steps/StepChainsaw.vue')
)
const StepPayment = defineAsyncComponent(() =>
    import('./components/steps/StepPayment.vue')
)
const StepReview = defineAsyncComponent(() =>
    import('./components/steps/StepReview.vue')
)

const activeComponent = computed(() => {
    return {
        1: StepApplicant,
        2: StepChainsaw,
        3: StepPayment,
        4: StepReview,
    }[currentStep.value]
})

/* -------------------- LOAD DATA -------------------- */

const loadExistingApplication = async () => {
    const id = form.value.application_id
    if (!id) return

    try {
        const res = await getApplicationReview(id)

        application.value = res.application
        suppliers.value = res.suppliers
        files.value = res.files

        const { i_province, i_city_mun, i_barangay, ...rest } =
            res.application

        form.value = {
            ...form.value,
            ...rest,
            i_province: Number(i_province),
            i_city_mun: Number(i_city_mun),
            i_barangay: Number(i_barangay),
        }
    } catch (error) {
        console.error(error)
    }
}

const loadReviewData = async () => {
    const id = form.value.application_id
    if (!id) return

    const res = await getApplicationReview(id)

    application.value = res.application
    suppliers.value = res.suppliers
    files.value = res.files
}

const fetchRoutingHistory = async () => {
    const id = form.value.application_id

    if (!id) {
        console.warn("Missing application_id")
        return
    }

    try {
        routingHistory.value = await getRoutingHistory(id)
        console.log("Routing History:", routingHistory.value)
    } catch (error) {
        console.error(error)
    }
}

/* -------------------- STEP NAVIGATION -------------------- */

const updateStepInUrl = () => {
    const routeName = isEdit.value
        ? 'applications.edit'
        : 'applications.create.citizen'

    router.visit(
        route(routeName, {
            application_id: form.value.application_id,
            type: props.type,
            step: currentStep.value,
        }),
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

const proceed = async () => {
    next()
}

/* -------------------- MAIN FLOW -------------------- */

const nextStep = async (payload: any) => {
    if (isProcessing.value) return
    isProcessing.value = true

    try {
        let res: any

        if (currentStep.value === 1) {
            res = await saveApplicant({
                ...payload,
                mode: props.mode,
                encoded_by: userId,
                application_type: payload.application_type,
            })

            form.value.application_id = res.application_id
            Object.assign(form.value, res.application)

            toast.add({
                severity: 'success',
                summary: 'Saved',
                detail: 'Applicant saved',
                life: 3000,
            })

        } else if (currentStep.value === 2) {
            res = await saveChainsaw(
                {
                    ...payload,
                    mode: props.mode,
                    suppliers: suppliers.value,
                    application_type: payload.application_type,
                },
                form.value.application_id
            )

            toast.add({
                severity: 'success',
                summary: 'Saved',
                detail: 'Chainsaw saved',
                life: 3000,
            })

        } else if (currentStep.value === 3) {
            res = await savePayment(
                {
                    ...payload,
                    mode: props.mode,
                    application_type: payload.application_type,
                },
                form.value.application_id
            )

            toast.add({
                severity: 'success',
                summary: 'Saved',
                detail: 'Payment saved',
                life: 3000,
            })
        }

        next()
        updateStepInUrl()

        if (isCreate.value) {
            router.visit(
                route('applications.create.citizen', {
                    application_id: form.value.application_id,
                    type: props.type,
                    step: currentStep.value,
                }),
                {
                    preserveState: true,
                    preserveScroll: true,
                }
            )
        }
    } catch (error: any) {
        console.error(error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail:
                error?.response?.data?.message || 'Something went wrong',
            life: 4000,
        })
    } finally {
        isProcessing.value = false
    }
}

/* -------------------- SUPPLIER -------------------- */

const supplierSaved = async (data: any) => {
    try {
        suppliers.value = data

        await saveSupplierInfo({
            suppliers: data,
            application_id: form.value.application_id,
        })

        toast.add({
            severity: 'success',
            summary: 'Saved',
            detail: 'Supplier Information successfully saved',
            life: 3000,
        })

        return true
    } catch {
        return false
    }
}

/* -------------------- BACK -------------------- */

const goBack = () => {
    prevStep()
    loadExistingApplication()

    router.visit(
        route('applications.edit', {
            application_id: form.value.application_id,
            type: props.type,
            step: currentStep.value,
        }),
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

/* -------------------- PRIVACY -------------------- */

const handleAcceptPrivacy = async () => {
    const data = await accept(userId)

    form.value.application_no = data.application_no
    form.value.application_id = data.application_id

    showPrivacyDialog.value = false
}

/* -------------------- WATCHERS -------------------- */

watch(
    () => form.value.application_id,
    async (id) => {
        if (id) await loadExistingApplication()
    },
    { immediate: true }
)

watch(currentStep, async (step) => {
    if (step === 2) {
        await loadExistingApplication()
    }

    if (step === 4) {
        await loadReviewData()
    }
})

/* -------------------- INIT -------------------- */

onMounted(async () => {
    if (props.mode === 'edit') {
        form.value.application_id = props.application_id
    }

    if (props.step) {
        currentStep.value = Number(props.step)
    }

    await loadExistingApplication()
    await fetchRoutingHistory()

    if (props.mode !== 'edit') {
        showPrivacyDialog.value = false

        const hasConsent = await checkConsent(form.value.application_id)

        if (!hasConsent) {
            showPrivacyDialog.value = true
        }
    }
})
</script>

<template>

    <Head title="Chainsaw Purchase System" />
    <AppLayout>
        <div class="flex flex-col gap-6 rounded-xl p-4 sm:grid-cols-3">
            <div class="box">
                <Toast />
                <div class="space-y-6 p-6">
                    <component :is="activeComponent" :application="application" :form="form" :suppliers="suppliers"
                        :application_type="type" :isProcessing="isProcessing" :currentStep="currentStep"
                        :supplier="suppliers" :routingHistory="routingHistory" :files="files" @proceed="proceed" @next="nextStep" @back="goBack"
                        :mode="props.mode" @supplierSaved="supplierSaved" />
                </div>

                <Dialog header="Privacy Consent" v-model:visible="showPrivacyDialog" modal :closable="false"
                    :draggable="false" :style="{ width: '500px' }">
                    <div class="space-y-4 text-sm text-gray-700">
                        <p>
                            In compliance with the <b>Data Privacy Act of 2012 (RA 10173)</b>, we collect and process
                            your personal information solely
                            for the purpose of processing your Chainsaw Purchase System.
                        </p>
                        <p>Your data will be treated confidentially and will not be shared without your consent unless
                            required by law.</p>
                        <div class="mt-4 flex items-start gap-2">
                            <Checkbox v-model="hasAgreedPrivacy" binary />
                            <label class="text-sm"> I have read and agree to the Data Privacy Policy. </label>
                        </div>
                    </div>
                    <template #footer>
                        <Button label="Decline" class="p-button-text"
                            @click="router.get(route('applications.create.citizen'))">Decline</Button>
                        <Button label="Agree & Continue" :disabled="!hasAgreedPrivacy" class="bg-green-900 text-white"
                            @click="handleAcceptPrivacy">Agree & Continue</Button>
                    </template>
                </Dialog>
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

/* Base style for ToggleButton - Green (unchecked/default state) */
/* Default state - Green */
/* Base style for ToggleButton - Green (unchecked/default state) */
.p-togglebutton {
    font-weight: 600;
    font-size: 1rem;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    border: none;
    background-color: #22c55e;
    /* green-500 */
    color: white;
    transition:
        background-color 0.3s ease,
        filter 0.3s ease;
}

/* Hover effect */
.p-togglebutton:hover {
    filter: brightness(1.1);
}

/* Checked state - Darker green */
.p-togglebutton.p-togglebutton-checked {
    background-color: #15803d !important;
    /* green-700 */
    border-color: #166534;
    color: rgb(0, 0, 0);
}

/* Fix inner white background */
.p-togglebutton.p-togglebutton-checked .p-togglebutton-content {
    background-color: #15803d !important;
    box-shadow: none;
    color: white !important;
}

/* Ensure label and icon are white in all states */
.p-togglebutton .p-togglebutton-icon,
.p-togglebutton .p-togglebutton-label {
    color: white !important;
}
</style>