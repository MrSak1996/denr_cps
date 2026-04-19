<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import carousel1 from '../../images/carousel1.jpg'
import carousel2 from '../../images/carousel2.jpg'
import carousel3 from '../../images/carousel3.jpg'
import carousel4 from '../../images/carousel4.jpg'
import carousel5 from '../../images/carousel5.png'
import carousel6 from '../../images/carousel6.png'
import carousel7 from '../../images/carousel7.png'
import carousel8 from '../../images/carousel8.png'
import carousel9 from '../../images/carousel9.png'
const currentSlide = ref(0)

const slides = [
    {
        image: carousel1, // forest landscape
        title: 'Welcome to Chainsaw Purchase System',
        text: 'Efficient and sustainable permit processing for responsible forestry.'
    },
    {
        image: carousel2, // sunlight through trees
        title: 'Protecting Our Forests',
        text: 'Promoting environmental responsibility and sustainable resource use.'
    },
    {
        image: carousel3, // mountain forest
        title: 'Sustainable Forestry',
        text: 'Balancing development with environmental conservation.'
    },
       {
        image: carousel3, // mountain forest
        title: 'Sustainable Forestry',
        text: 'Balancing development with environmental conservation.'
    },
    {
        image: carousel4, // river in forest
        title: 'Streamlined Permitting',
        text: 'Simplifying the application process for chainsaw permits.'
    },
    {
        image: carousel5, // aerial forest view
        title: null,
        text: null
    },
    {
        image: carousel6, // tree canopy
        title: null,
        text: null
    },
    {
        image: carousel7, // logging activity
        title: null,
        text: null
    },
    {
        image: carousel8, // forest path
        title: null,
        text: null
    },
    {
        image: carousel9, // sunset in forest
        title: null,
        text: null
    }

]

const establishments = [
    {
        name: 'BJMP City of Cabuyao',
        image: '/images/certified/cabuyao.png',
        certified: 'July 01, 2021'
    },
    {
        name: 'BFP Calamba City Station',
        image: '/images/certified/bfp-calamba.jpg',
        certified: 'June 30, 2021'
    },
    {
        name: 'BFP Laguna Provincial Satellite Office',
        image: '/images/certified/bfp-laguna.jpg',
        certified: 'June 30, 2021'
    },
    {
        name: 'BFP Laguna Provincial Satellite Office',
        image: '/images/certified/bfp-laguna.jpg',
        certified: 'June 30, 2021'
    },
    {
        name: 'BFP Laguna Provincial Satellite Office',
        image: '/images/certified/bfp-laguna.jpg',
        certified: 'June 30, 2021'
    },
    {
        name: 'BFP Laguna Provincial Satellite Office',
        image: '/images/certified/bfp-laguna.jpg',
        certified: 'June 30, 2021'
    }
]

const estIndex = ref(0)

const nextEst = () => {
    estIndex.value = (estIndex.value + 1) % establishments.length
}

const prevEst = () => {
    estIndex.value =
        (estIndex.value - 1 + establishments.length) % establishments.length
}



const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % slides.length
}

const prevSlide = () => {
    currentSlide.value =
        (currentSlide.value - 1 + slides.length) % slides.length
}

onMounted(() => {
    setInterval(() => {
        nextSlide()
    }, 5000)
})
</script>

<template>

    <Head title="Chainsaw Purchase System" />

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">

        <!-- Navbar -->
        <nav class="bg-white dark:bg-gray-800 shadow-md" >
            <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                <h1 class="text-xl font-bold text-gray-800 dark:text-white">
                    Chainsaw Purchase System
                </h1>

                <div class="flex items-center gap-6">
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-red-500">
                        Home
                    </a>
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-red-500">
                        About
                    </a>
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-red-500">
                        Contact
                    </a>

                    <Link v-if="$page.props.auth.user" :href="route('applications.pending_application')"
                        class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                        Dashboard
                    </Link>

                    <template v-else>
                        <Link :href="route('login')" class="text-gray-600 dark:text-gray-300 hover:text-red-500">
                            Login
                        </Link>
                       
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero + Carousel -->
        <div class="relative w-full h-[90vh] overflow-hidden">

            <!-- Slides -->
            <div v-for="(slide, index) in slides" :key="index" class="absolute inset-0 transition-opacity duration-1000"
                :class="{
                    'opacity-100 z-10': currentSlide === index,
                    'opacity-0 z-0': currentSlide !== index
                }">
                <img :src="slide.image" class="w-full h-full object-cover" />

                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/20 flex flex-col justify-center items-center text-center px-6">
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        {{ slide.title }}
                    </h2>
                    <p class="text-lg md:text-xl text-gray-200 mb-6">
                        {{ slide.text }}
                    </p>
                    <!-- <Link :href="route('register')"
                        class="bg-red-500 text-white px-6 py-3 rounded-lg text-lg hover:bg-red-600 transition">
                        Get Started
                    </Link> -->
                </div>
            </div>

            <!-- Controls -->
            <button @click="prevSlide"
                class="absolute left-5 top-1/2 -translate-y-1/2 bg-black/50 text-white p-3 rounded-full hover:bg-black">
                ❮
            </button>

            <button @click="nextSlide"
                class="absolute right-5 top-1/2 -translate-y-1/2 bg-black/50 text-white p-3 rounded-full hover:bg-black">
                ❯
            </button>
        </div>

        <!-- Feature Grid Section -->
       


    </div>
</template>

<style scoped>
.bg-light {
    background-color: #f8f9fa !important;
}

.banner-guidelines {
    background-image: url('../../images/banner_guidelines.png');
    background-repeat: no-repeat;
    background-size: cover;
}

.list_establishment-banner {
    background-image: url('../../images/list_establishment.png');
    background-repeat: no-repeat;
    background-size: cover;
}

.rounded {
    border-radius: .25rem !important;
}

.bg-body {
    background-color: #fff !important;
}

.p-3 {
    padding: 1rem !important;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

.shadow {
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
}

@media (min-width: 992px) {

    .container,
    .container-lg,
    .container-md,
    .container-sm {
        max-width: 960px;
    }
}
</style>
