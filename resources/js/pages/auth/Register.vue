<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

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
    <div
        class="flex min-h-screen items-center justify-center bg-cover bg-center bg-no-repeat px-4"
        style="
            background-image: url('https://png.pngtree.com/thumb_back/fh260/background/20230525/pngtree-3d-grocery-store-with-orange-goods-in-the-background-image_2617236.jpg');
        "
    >
        <AuthBase
            class="bg-opacity-95 relative z-10 w-full max-w-2xl rounded-3xl bg-white p-10 text-gray-900 shadow-xl"
            title="Đăng ký vào tài khoản của bạn"
            description="Nhập thông tin bên dưới để tạo tài khoản của bạn"
        >
            <Head title="Đăng ký" />

            <br />

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name" class="block font-semibold">Họ và tên</Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            autocomplete="name"
                            v-model="form.name"
                            placeholder="Họ và tên đầy đủ"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-orange-400 focus:outline-none"
                            :tabindex="1"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email" class="block font-semibold">Địa chỉ email</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="vd: email@example.com"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-orange-400 focus:outline-none"
                            :tabindex="2"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="grid gap-2">
                        <Label for="password" class="block font-semibold">Mật khẩu</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            autocomplete="new-password"
                            v-model="form.password"
                            placeholder="Mật khẩu"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-orange-400 focus:outline-none"
                            :tabindex="3"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Confirm Password -->
                    <div class="grid gap-2">
                        <Label for="password_confirmation" class="block font-semibold">Xác nhận mật khẩu</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            placeholder="Nhập lại mật khẩu"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-orange-400 focus:outline-none"
                            :tabindex="4"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        class="mt-2 flex w-full items-center justify-center rounded-lg bg-orange-500 py-3 font-bold text-white transition duration-200 hover:bg-orange-600"
                        :tabindex="5"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="mr-3 h-5 w-5 animate-spin" />
                        Tạo tài khoản
                    </Button>
                </div>
            </form>

            <p class="mt-8 text-center text-gray-700">
                Đã có tài khoản?
                <TextLink :href="route('login')" class="font-semibold text-orange-600 hover:underline" :tabindex="6"> Đăng nhập ngay</TextLink>
            </p>
        </AuthBase>
    </div>
</template>
