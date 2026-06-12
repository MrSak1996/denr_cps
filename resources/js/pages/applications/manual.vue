<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { List, BookOpen, Users, Lightbulb, HelpCircle, CheckCircle2 } from 'lucide-vue-next';

import Card from 'primevue/card';
import Fieldset from 'primevue/fieldset';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

import application_icon from '../../../images/icons/application.png';
import review_icon from '../../../images/icons/review.png';
import approved_icon from '../../../images/icons/approved.png';
import reject_icon from '../../../images/icons/reject.png';

const breadcrumbs = [
    {
        title: "User's Manual",
        href: '/applicants/manual',
    },
];
</script>

<template>
    <Head title="User Manual" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 rounded-xl p-4">

            <!-- MAIN BOX -->
            <div class="manual-box">

                <!-- HEADER -->
                <div class="flex items-center gap-2 mb-6">
                    <List class="w-5 h-5 text-green-700" />
                    <h1 class="text-3xl font-bold text-gray-800">
                        User's Manual
                    </h1>
                </div>

                <!-- INTRO -->
                <Fieldset legend="System Overview" class="mb-6">

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                        <!-- CARD 1 -->
                        <Card class="summary-card">
                            <template #content>
                                <div class="flex items-center gap-4">

                                    <div class="summary-icon blue">
                                        <img :src="application_icon" class="h-10" />
                                    </div>

                                    <div>
                                        <h2 class="summary-title">
                                            Application Filing
                                        </h2>

                                        <p class="summary-desc">
                                            Submit chainsaw purchase applications online.
                                        </p>
                                    </div>

                                </div>
                            </template>
                        </Card>

                        <!-- CARD 2 -->
                        <Card class="summary-card">
                            <template #content>
                                <div class="flex items-center gap-4">

                                    <div class="summary-icon yellow">
                                        <img :src="review_icon" class="h-10" />
                                    </div>

                                    <div>
                                        <h2 class="summary-title">
                                            Evaluation
                                        </h2>

                                        <p class="summary-desc">
                                            Applications are reviewed by authorized personnel.
                                        </p>
                                    </div>

                                </div>
                            </template>
                        </Card>

                        <!-- CARD 3 -->
                        <Card class="summary-card">
                            <template #content>
                                <div class="flex items-center gap-4">

                                    <div class="summary-icon green">
                                        <img :src="approved_icon" class="h-10" />
                                    </div>

                                    <div>
                                        <h2 class="summary-title">
                                            Approval
                                        </h2>

                                        <p class="summary-desc">
                                            Approved applications proceed for release.
                                        </p>
                                    </div>

                                </div>
                            </template>
                        </Card>

                        <!-- CARD 4 -->
                        <Card class="summary-card">
                            <template #content>
                                <div class="flex items-center gap-4">

                                    <div class="summary-icon red">
                                        <img :src="reject_icon" class="h-10" />
                                    </div>

                                    <div>
                                        <h2 class="summary-title">
                                            Resubmission
                                        </h2>

                                        <p class="summary-desc">
                                            Correct and upload returned requirements.
                                        </p>
                                    </div>

                                </div>
                            </template>
                        </Card>

                    </div>
                </Fieldset>

                <!-- SEARCH + QUICK NAV -->
                

                <!-- CONTENT -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- HOW TO USE -->
                    <Card class="content-card">
                        <template #content>

                            <div class="flex items-center gap-2 mb-6">
                                <BookOpen class="w-5 h-5 text-green-700" />

                                <h2 class="section-title">
                                    How to Use the System
                                </h2>
                            </div>

                            <div class="space-y-6">

                                <!-- STEP -->
                                <div class="step-item">
                                    <div class="step-number">1</div>

                                    <div>
                                        <h3 class="step-title green">
                                            Create Application
                                        </h3>

                                        <p class="step-text">
                                            Start by filling out the applicant
                                            information form. Ensure all required
                                            fields are completed before proceeding.
                                        </p>
                                    </div>
                                </div>

                                <!-- STEP -->
                                <div class="step-item">
                                    <div class="step-number">2</div>

                                    <div>
                                        <h3 class="step-title green">
                                            Upload Requirements
                                        </h3>

                                        <p class="step-text">
                                            Upload all required supporting
                                            documents in PDF format only.
                                        </p>
                                    </div>
                                </div>

                                <!-- STEP -->
                                <div class="step-item">
                                    <div class="step-number">3</div>

                                    <div>
                                        <h3 class="step-title green">
                                            Evaluation Process
                                        </h3>

                                        <p class="step-text">
                                            Authorized evaluators review your
                                            application and validate submitted
                                            requirements.
                                        </p>
                                    </div>
                                </div>

                                <!-- STEP -->
                                <div class="step-item">
                                    <div class="step-number">4</div>

                                    <div>
                                        <h3 class="step-title green">
                                            Resubmission
                                        </h3>

                                        <p class="step-text">
                                            Returned applications may require
                                            corrected documents for resubmission.
                                        </p>
                                    </div>
                                </div>

                                <!-- STEP -->
                                <div class="step-item">
                                    <div class="step-number">5</div>

                                    <div>
                                        <h3 class="step-title green">
                                            Approval & Release
                                        </h3>

                                        <p class="step-text">
                                            Once approved, your permit is finalized
                                            and marked as completed.
                                        </p>
                                    </div>
                                </div>

                            </div>

                        </template>
                    </Card>

                    <!-- USER ROLES -->
                    <Card class="content-card">
                        <template #content>

                            <div class="flex items-center gap-2 mb-6">
                                <Users class="w-5 h-5 text-purple-600" />

                                <h2 class="section-title">
                                    User Roles Guide
                                </h2>
                            </div>

                            <div class="space-y-6">

                                <div class="role-item">
                                    <div class="role-circle purple">
                                        <Users class="w-5 h-5" />
                                    </div>

                                    <div>
                                        <h3 class="role-title purple">
                                            Applicants
                                        </h3>

                                        <p class="step-text">
                                            Presents all the requirements and other supporting requirements.
                                        </p>
                                    </div>
                                </div>

                                <div class="role-item">
                                    <div class="role-circle blue">
                                        <CheckCircle2 class="w-5 h-5" />
                                    </div>

                                    <div>
                                        <h3 class="role-title blue">
                                            Chief RPS, TSD, CENRO & PENRO
                                        </h3>

                                        <p class="step-text">
                                            Reviews documents and validates
                                            compliance status.
                                        </p>
                                    </div>
                                </div>

                                <div class="role-item">
                                    <div class="role-circle green">
                                        <Lightbulb class="w-5 h-5" />
                                    </div>

                                    <div>
                                        <h3 class="role-title green">
                                            Technical Staff
                                        </h3>

                                        <p class="step-text">
                                            Checks completeness and correctness
                                            of submitted requirements.
                                        </p>
                                    </div>
                                </div>

                                <div class="role-item">
                                    <div class="role-circle orange">
                                        <CheckCircle2 class="w-5 h-5" />
                                    </div>

                                    <div>
                                        <h3 class="role-title orange">
                                            Regional Executive Director
                                        </h3>

                                        <p class="step-text">
                                            Provides final approval of
                                            applications.
                                        </p>
                                    </div>
                                </div>

                            </div>

                        </template>
                    </Card>

                    <!-- HELPFUL TIPS -->
                    <Card class="content-card">
                        <template #content>

                            <div class="flex items-center gap-2 mb-6">
                                <Lightbulb class="w-5 h-5 text-yellow-500" />

                                <h2 class="section-title">
                                    Helpful Tips
                                </h2>
                            </div>

                            <ul class="space-y-4">

                                <li class="tip-item">
                                    ✔ Ensure all required fields are completed.
                                </li>

                                <li class="tip-item">
                                    ✔ Upload clear and readable PDF documents.
                                </li>

                                <li class="tip-item">
                                    ✔ Regularly monitor your application status.
                                </li>

                                <li class="tip-item">
                                    ✔ Respond promptly to returned applications.
                                </li>

                                <li class="tip-item">
                                    ✔ Contact your assigned office for assistance.
                                </li>

                            </ul>

                            <!-- HELP BOX -->
                            <div class="help-box mt-8">

                                <div class="flex items-start gap-3">

                                    <HelpCircle class="w-6 h-6 text-blue-600 mt-1" />

                                    <div>
                                        <h3 class="font-semibold text-blue-700 mb-1">
                                            Need Help?
                                        </h3>

                                        <p class="text-sm text-gray-700 leading-relaxed">
                                            For technical concerns or application
                                            assistance, please message our official Group Chat or system
                                            administrator.
                                        </p>
                                    </div>

                                </div>

                            </div>

                        </template>
                    </Card>

                </div>

                <!-- FAQ -->
                <Fieldset legend="Frequently Asked Questions" class="mt-6">

                    <Accordion :multiple="true">

                        <AccordionTab header="1. How do I start a new application?">
                            <p class="faq-text">
                                Navigate to the Chainsaw Applications page and
                                click the Create Application button.
                            </p>
                        </AccordionTab>

                        <AccordionTab header="2. What file types are accepted?">
                            <p class="faq-text">
                                Only PDF documents are accepted with a maximum
                                upload size depending on system settings.
                            </p>
                        </AccordionTab>

                        <AccordionTab header="3. Why was my application returned?">
                            <p class="faq-text">
                                Applications are usually returned due to missing,
                                incomplete, or invalid supporting requirements.
                            </p>
                        </AccordionTab>

                        <AccordionTab header="4. How long does approval take?">
                            <p class="faq-text">
                                Processing time depends on application completeness
                                and office workload.
                            </p>
                        </AccordionTab>

                    </Accordion>

                </Fieldset>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.manual-box {
    background: #fff;
    border-top: 4px solid #0f766e;
    padding: 24px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
}

/* SUMMARY */
.summary-card {
    border-radius: 16px;
    border: 1px solid #edf2f7;
    transition: all 0.2s ease;
}

.summary-card:hover {
    transform: translateY(-2px);
}

.summary-icon {
    width: 70px;
    height: 70px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.summary-icon.blue {
    background: #eff6ff;
}

.summary-icon.yellow {
    background: #fefce8;
}

.summary-icon.green {
    background: #f0fdf4;
}

.summary-icon.red {
    background: #fef2f2;
}

.summary-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.summary-desc {
    color: #6b7280;
    font-size: 14px;
    margin-top: 4px;
}

/* CONTENT */
.content-card {
    border-radius: 16px;
    height: 100%;
}

.section-title {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
}

/* STEP GUIDE */
.step-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.step-number {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    background: #15803d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.step-title {
    font-weight: 700;
    margin-bottom: 4px;
}

.step-title.green {
    color: #15803d;
}

.step-text {
    color: #4b5563;
    font-size: 14px;
    line-height: 1.5;
}

/* ROLES */
.role-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.role-circle {
    width: 42px;
    height: 42px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.role-circle.purple {
    background: #f3e8ff;
    color: #9333ea;
}

.role-circle.blue {
    background: #dbeafe;
    color: #2563eb;
}

.role-circle.green {
    background: #dcfce7;
    color: #16a34a;
}

.role-circle.orange {
    background: #ffedd5;
    color: #ea580c;
}

.role-title {
    font-weight: 700;
    margin-bottom: 4px;
}

.role-title.purple {
    color: #9333ea;
}

.role-title.blue {
    color: #2563eb;
}

.role-title.green {
    color: #16a34a;
}

.role-title.orange {
    color: #ea580c;
}

/* TIPS */
.tip-item {
    font-size: 15px;
    color: #374151;
    line-height: 1.7;
}

.help-box {
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    border-radius: 12px;
    padding: 18px;
}

/* FAQ */
.faq-text {
    color: #4b5563;
    line-height: 1.7;
}
</style>