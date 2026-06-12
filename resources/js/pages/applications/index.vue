<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

import {
    Info,
    Building2,
    UserRound,
    Landmark,
    ArrowRight,
} from 'lucide-vue-next';

import Tag from 'primevue/tag';
import Card from 'primevue/card';

import individual_img from '../../../images/man.png';
import company_img from '../../../images/office-building.png';
import government_img from '../../../images/government.png';

/* -----------------------------------------
| BREADCRUMBS
------------------------------------------ */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Applications',
        href: '/applications/index',
    },
];

/* -----------------------------------------
| NAVIGATION
------------------------------------------ */
const selectApplicant = (type: string) => {
    router.visit(`/applications/create/${type}`);
};
</script>

<template>

    <Head title="Chainsaw Purchase System" />

    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="page-wrapper">

            <!-- HERO SECTION -->
            <div class="hero-section">

                <div class="hero-content">

                    <div class="flex items-center gap-3 mb-4">

                        <div class="hero-icon">
                            <Info class="w-6 h-6 text-white" />
                        </div>

                        <div>
                            <h1 class="hero-title">
                                Chainsaw Purchase System
                            </h1>

                            <p class="hero-subtitle">
                                Select the applicant category to begin your
                                chainsaw purchase application.
                            </p>
                        </div>

                    </div>
                    <!-- 
                    <div class="flex items-center gap-2 mt-4">

                        <span class="text-sm text-gray-600">
                            Current Status:
                        </span>

                        <Tag severity="danger" value="Draft" />

                    </div> -->

                </div>

            </div>

            <!-- APPLICATION TYPE -->
            <div class="application-section">

                <div class="section-header">

                    <h2 class="section-title">
                        Choose Applicant Type
                    </h2>

                    <p class="section-subtitle">
                        Please select the category that best matches your
                        application.
                    </p>

                </div>

                <!-- CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                    <!-- GOVERNMENT -->
                    <!-- GOVERNMENT -->
                    <Card class="app-card government-card" @click="selectApplicant('government')">

                        <template #content>

                            <div class="card-content">

                                <div class="card-badge active">
                                    Available
                                </div>

                                <div class="card-image-wrapper">

                                    <img :src="government_img" class="card-image" />

                                </div>



                                <h3 class="card-title">
                                    Government
                                </h3>

                                <p class="card-description">
                                    For government agencies and public offices
                                    requesting chainsaw purchase permits.
                                </p>
                                <div class="requirements-section">

                                    <h4 class="requirements-title">
                                        Required Documents
                                    </h4>

                                    <ul class="requirements-list">
                                        <li>Duly accomplished Application Form/Letter of Intent</li>
                                        <li>Copy of Permit to Sell/Re-Sell Chainsaw</li>
                                        <li>Official Receipt of Permit Fee</li>
                                        <li>Authorization of representative/requesting person (if applicable)</li>
                                        <li>Duly signed and notarized affidavit (purpose of chainsaw purchase)</li>
                                        <li>Other Supporting Documents</li>
                                    </ul>

                                </div>

                                <button class="card-button government-btn">

                                    Start Application

                                    <ArrowRight class="w-4 h-4" />

                                </button>

                            </div>

                        </template>

                    </Card>

                    <!-- BUSINESS -->
                    <Card class="app-card business-card" @click="selectApplicant('business')">

                        <template #content>

                            <div class="card-content">

                                <div class="card-badge active">
                                    Available
                                </div>

                                <div class="card-image-wrapper">

                                    <img :src="company_img" class="card-image" />

                                </div>



                                <h3 class="card-title">
                                    Business
                                </h3>

                                <p class="card-description">
                                    For companies, enterprises, and private
                                    organizations applying for permits.
                                </p>
                                <div class="requirements-section">

                                    <h4 class="requirements-title">
                                        Required Documents
                                    </h4>

                                    <ul class="requirements-list">
                                        <li>Duly accomplished Application Form/Letter of Intent</li>
                                        <li>Copy of Permit to Sell/Re-Sell Chainsaw</li>
                                        <li>Official Receipt of Permit Fee</li>
                                        <li>Certificate of Registration of Business Name from DTI or SEC</li>
                                        <li>Business License/Mayor's Permit </li>
                                        <li>Authorization/Secretary's Certificate</li>
                                        <li>Duly signed and notarized affidavit (purpose of chainsaw purchase)</li>
                                        <li>Other Supporting Documents</li>
                                    </ul>

                                </div>

                                <button class="card-button business-btn">

                                    Start Application

                                    <ArrowRight class="w-4 h-4" />

                                </button>

                            </div>

                        </template>

                    </Card>

                    <!-- CITIZEN -->
                    <Card class="app-card citizen-card" @click="selectApplicant('citizen')">

                        <template #content>

                            <div class="card-content">

                                <div class="card-badge active">
                                    Available
                                </div>

                                <div class="card-image-wrapper">
                                    <img :src="individual_img" class="card-image" />
                                </div>

                                <h3 class="card-title">
                                    Citizen
                                </h3>

                                <p class="card-description">
                                    For individual citizens applying for
                                    personal chainsaw purchase permits.
                                </p>

                                <!-- REQUIRED DOCUMENTS -->
                                <div class="requirements-section">

                                    <h4 class="requirements-title">
                                        Required Documents
                                    </h4>

                                    <ul class="requirements-list">
                                         <li>Duly accomplished Application Form/Letter of Intent</li>
                                        <li>Copy of Permit to Sell/Re-Sell Chainsaw</li>
                                        <li>Official Receipt of Permit Fee</li>
                                        <li>Duly signed and notarized affidavit (purpose of chainsaw purchase)</li>
                                        <li>Authorization of representative/requesting person (if applicable)</li>
                                        <li>Other Supporting Documents</li>
                                    </ul>

                                </div>

                                <button class="card-button citizen-btn">

                                    Start Application

                                    <ArrowRight class="w-4 h-4" />

                                </button>

                            </div>

                        </template>

                    </Card>

                </div>

            </div>

        </div>

    </AppLayout>
</template>

<style scoped>
/* PAGE */
.requirements-section {
    width: 100%;
    margin-top: 1rem;
    padding: 0.75rem;
    border-radius: 0.75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.requirements-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1e3a8a;
    margin-bottom: 0.5rem;
}

.requirements-list {
    font-size: 0.75rem;
    color: #374151;
    line-height: 1.5;

    list-style-position: outside;
    text-align: left;
    padding-left: 1.25rem;
    margin: 0;
}

.requirements-list li {
    margin-bottom: 0.35rem;
}

.page-wrapper {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 28px;
}

/* HERO */
.hero-section {
    background: linear-gradient(135deg,
            #0f766e 0%,
            #115e59 100%);
    border-radius: 24px;
    padding: 32px;
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(15, 118, 110, 0.18);
}

.hero-section::before {
    content: '';
    position: absolute;
    right: -50px;
    top: -50px;
    width: 220px;
    height: 220px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 999px;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-title {
    font-size: 30px;
    font-weight: 800;
    line-height: 1.2;
}

.hero-subtitle {
    margin-top: 4px;
    color: rgba(255, 255, 255, 0.85);
    font-size: 15px;
}

/* SECTION */
.application-section {
    background: white;
    border-radius: 24px;
    padding: 32px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.05);
}

.section-header {
    margin-bottom: 30px;
}

.section-title {
    font-size: 26px;
    font-weight: 700;
    color: #111827;
}

.section-subtitle {
    margin-top: 6px;
    color: #6b7280;
    font-size: 15px;
}

/* CARD */
.app-card {
    border-radius: 24px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    transition: all 0.25s ease;
    cursor: pointer;
    position: relative;
}

.app-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 35px rgba(0, 0, 0, 0.08);
}

.card-content {
    padding: 28px;
    display: flex;
    flex-direction: column;
  
    position: relative;
}

/* BADGE */
.card-badge {
    position: absolute;
    top: 18px;
    right: 18px;
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 999px;
    font-weight: 600;
}

.card-badge.active {
    background: #dcfce7;
    color: #15803d;
}

.card-badge.disabled {
    background: #fee2e2;
    color: #b91c1c;
}

/* IMAGE */
.card-image-wrapper {
    width: 120px;
    height: 120px;
    border-radius: 999px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 22px;
}

.card-image {
    width: 72px;
    height: 72px;
    object-fit: contain;
}

/* ICON */
.card-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
}

.card-icon.business {
    background: #e0e7ff;
    color: #4338ca;
}

.card-icon.citizen {
    background: #dcfce7;
    color: #15803d;
}

.card-icon.government {
    background: #fee2e2;
    color: #b91c1c;
}

/* TEXT */
.card-title {
    font-size: 24px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 10px;
}

.card-description {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.7;
    min-height: 70px;
}

/* BUTTON */
.card-button {
    margin-top: 20px;
    width: 100%;
    border: none;
    border-radius: 14px;
    padding: 14px 18px;
    font-weight: 700;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

/* GOVERNMENT BUTTON */
.government-btn {
    background: #0f766e;
    color: white;
}

.government-btn:hover {
    background: #115e59;
}

.business-btn {
    background: #4338ca;
    color: white;
}

.business-btn:hover {
    background: #3730a3;
}

.citizen-btn {
    background: #15803d;
    color: white;
}

.citizen-btn:hover {
    background: #166534;
}

.disabled-button {
    background: #e5e7eb;
    color: #6b7280;
    cursor: not-allowed;
}

/* DISABLED */
.disabled-card {
    opacity: 0.85;
    cursor: not-allowed;
}

.disabled-card:hover {
    transform: none;
    box-shadow: none;
}
</style>