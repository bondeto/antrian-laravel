<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="mb-5 text-center">
            <h1 class="text-2xl font-bold text-gray-800">Reset Password</h1>
            <p class="text-sm text-gray-500 mt-1">
                Enter your email to receive a reset link.
            </p>
        </div>

        <div class="mb-6 text-sm text-gray-600 bg-blue-50 p-4 rounded-xl border border-blue-100 leading-relaxed">
            Forgot your password? No problem. Just let us know your email start
            address and we will email you a password reset link that will allow
            you to choose a new one.
        </div>

        <div
            v-if="status"
            class="mb-4 text-sm font-medium text-green-600 bg-green-50 px-4 py-2 rounded-lg border border-green-100"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="email" value="Email" class="mb-1" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full py-3"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="name@company.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex flex-col space-y-3">
                <PrimaryButton
                    :class="{ 'opacity-70 cursor-wait': form.processing }"
                    :disabled="form.processing"
                >
                    Email Password Reset Link
                </PrimaryButton>

                <div class="text-center">
                    <Link :href="route('login')" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">
                        &larr; Back to Log in
                    </Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
