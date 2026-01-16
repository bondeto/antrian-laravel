<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="mb-5 text-center">
            <h1 class="text-2xl font-bold text-gray-800">Create Account</h1>
            <p class="text-sm text-gray-500 mt-1">Get started with your free account</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Name" class="mb-1" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full py-3"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="John Doe"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" class="mb-1" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full py-3"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="name@company.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" class="mb-1" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full py-3"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                    class="mb-1"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full py-3"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="pt-2 flex flex-col space-y-3">
                <PrimaryButton
                    :class="{ 'opacity-70 cursor-wait': form.processing }"
                    :disabled="form.processing"
                >
                    Create Account
                </PrimaryButton>
                
                <div class="text-center">
                    <Link
                        :href="route('login')"
                        class="text-sm text-gray-500 hover:text-gray-900 transition-colors"
                    >
                        Already have an account? <span class="text-blue-600 font-medium hover:underline">Log in</span>
                    </Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
