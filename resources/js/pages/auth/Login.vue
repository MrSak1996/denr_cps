<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { useAuth } from '@/composables/useAuth'
import { usePage, router } from '@inertiajs/vue3'

const { userId } = useAuth()

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    uname: '',
    password: '',
    remember: false,
});

// const submit = () => {
//     form.post(route('login'), {
//         onFinish: () => {
//             form.reset('password');
//             const page = usePage();
//         },
//     });
// };

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password')
    });
};


</script>
<template>
    <AuthBase title="" description="">

        <Head title="Log in" />

        <!-- Background -->
        <div
            class="relative min-h-screen flex items-center justify-center bg-cover bg-center px-4"
            style="background-image: url('/images/wall.png');"
        >

            <!-- Overlay -->
            <div
                class="absolute inset-0 bg-gradient-to-br from-slate-900/70 via-emerald-900/40 to-cyan-900/60">
            </div>

            <!-- Login Card -->
            <div
                class="relative w-full max-w-md rounded-3xl border border-white/20 bg-white/15 backdrop-blur-xl shadow-[0_20px_60px_rgba(0,0,0,.45)] p-10 animate-in fade-in duration-500">

                <!-- Logo -->
                <div class="flex flex-col items-center mb-8">

                    <img
                        src="/images/denr_logo.png"
                        alt="DENR Logo"
                        class="w-20 h-20 mb-4 object-contain"
                    />

                    <h1
                        class="text-2xl font-bold text-white text-center tracking-wide">
                        Chainsaw Purchase System
                    </h1>

                    <p
                        class="text-sm text-slate-200 text-center mt-2">
                        Department of Environment and Natural Resources
                    </p>

                </div>

                <!-- Status -->
                <div
                    v-if="status"
                    class="mb-4 rounded-lg bg-green-500/20 border border-green-400/30 p-3 text-center text-sm text-green-100">
                    {{ status }}
                </div>

                <!-- Form -->
                <form
                    @submit.prevent="submit"
                    class="space-y-5">

                    <!-- Username -->
                    <div>

                        <Label
                            for="uname"
                            class="text-white mb-2 block">
                            Username
                        </Label>

                        <Input
                            id="uname"
                            v-model="form.uname"
                            type="text"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="Enter your username"
                            class="h-12 rounded-xl bg-white/80 border-white/30 focus:ring-emerald-500"
                        />

                        <InputError
                            :message="form.errors.uname" />

                    </div>

                    <!-- Password -->
                    <div>

                        <div
                            class="flex justify-between items-center mb-2">

                            <Label
                                for="password"
                                class="text-white">
                                Password
                            </Label>

                            <TextLink
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs text-cyan-200 hover:text-white">

                                Forgot password?

                            </TextLink>

                        </div>

                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            class="h-12 rounded-xl bg-white/80 border-white/30 focus:ring-emerald-500"
                        />

                        <InputError
                            :message="form.errors.password" />

                    </div>

                    <!-- Remember -->
                    <div
                        class="flex items-center">

                        <Label
                            for="remember"
                            class="flex items-center gap-3 text-white cursor-pointer">

                            <Checkbox
                                id="remember"
                                v-model="form.remember" />

                            <span>
                                Remember me
                            </span>

                        </Label>

                    </div>

                    <!-- Button -->

                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full h-12 rounded-xl bg-gradient-to-r from-emerald-600 to-cyan-600 hover:from-emerald-700 hover:to-cyan-700 text-white text-base font-semibold transition-all">

                        <LoaderCircle
                            v-if="form.processing"
                            class="mr-2 h-4 w-4 animate-spin" />

                        Sign In

                    </Button>

                </form>

                <!-- Footer -->

                <div
                    class="mt-8 border-t border-white/20 pt-5 text-center">

                    <p class="text-xs text-slate-200">

                        Authorized Personnel Only

                    </p>

                    <p class="text-xs text-slate-300 mt-1">

                        © DENR Region IV-A • Chainsaw Purchase System

                    </p>

                </div>

            </div>

        </div>

    </AuthBase>
</template>

