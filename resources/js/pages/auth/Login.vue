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

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    uname: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password')
    });
};
</script>

<template>
    <AuthBase
        title="Chainsaw Purchase System"
        description="Department of Environment and Natural Resources"
    >
        <Head title="Log in" />

        <!-- STATUS -->
        <div
            v-if="status"
            class="mb-4 text-center text-sm text-emerald-200"
        >
            {{ status }}
        </div>

        <!-- GLASS LOGIN CARD (WIDER) -->
        <div
            class="w-full max-w-3xl mx-auto rounded-3xl border border-white/15 bg-black/20 backdrop-blur-xl shadow-2xl p-10 lg:p-14"
        >

            <!-- HEADER -->

            <!-- FORM -->
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Username -->
                <div>
                    <Label for="uname" class="text-white mb-2 block">
                        Username
                    </Label>

                    <Input
                        id="uname"
                        v-model="form.uname"
                        type="text"
                        required
                        autofocus
                        placeholder="Enter username"
                        class="h-14 rounded-2xl bg-white/10 border-white/20 text-white placeholder:text-slate-300 focus:ring-emerald-400"
                    />

                    <InputError :message="form.errors.uname" />
                </div>

                <!-- Password -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <Label for="password" class="text-white">
                            Password
                        </Label>

                        <TextLink
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-cyan-200 hover:text-white"
                        >
                            Forgot password?
                        </TextLink>
                    </div>

                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        placeholder="Enter password"
                        class="h-14 rounded-2xl bg-white/10 border-white/20 text-white placeholder:text-slate-300 focus:ring-emerald-400"
                    />

                    <InputError :message="form.errors.password" />
                </div>

                <!-- REMEMBER -->
                <div class="flex items-center">
                    <Label class="flex items-center gap-3 text-white">
                        <Checkbox v-model="form.remember" />
                        Remember me
                    </Label>
                </div>

                <!-- BUTTON -->
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full h-14 rounded-2xl bg-gradient-to-r from-emerald-600 via-green-600 to-cyan-600 text-lg font-semibold hover:scale-[1.02] transition-all"
                >
                    <LoaderCircle
                        v-if="form.processing"
                        class="mr-2 h-5 w-5 animate-spin"
                    />
                    Log in
                </Button>

            </form>

            <!-- FOOTER -->
            <div class="mt-10 text-center border-t border-white/10 pt-5">
                <p class="text-xs text-slate-300">
                    Authorized Personnel Only
                </p>
                <p class="text-xs text-slate-400 mt-2">
                    © DENR Chainsaw Purchase System
                </p>
            </div>

        </div>
    </AuthBase>
</template>