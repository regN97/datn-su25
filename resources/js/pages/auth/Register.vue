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
    <div class="min-h-screen flex items-center justify-center px-4 bg-cover bg-center bg-no-repeat"
        style="background-image: url('https://png.pngtree.com/thumb_back/fh260/background/20230525/pngtree-3d-grocery-store-with-orange-goods-in-the-background-image_2617236.jpg');">
        <AuthBase class="relative z-10 bg-white bg-opacity-95 p-10 rounded-3xl shadow-xl max-w-2xl w-full text-gray-900"
            title="Đăng ký vào tài khoản của bạn" description="Nhập thông tin bên dưới để tạo tài khoản của bạn">

            <Head title="Đăng ký" />            

            <br>

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name" class="block font-semibold">Họ và tên</Label>
                        <Input id="name" type="text" required autofocus autocomplete="name" v-model="form.name"
                            placeholder="Họ và tên đầy đủ"
                            class="w-full px-4 py-3 border rounded-lg border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400"
                            :tabindex="1" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email" class="block font-semibold">Địa chỉ email</Label>
                        <Input id="email" type="email" required autocomplete="email" v-model="form.email"
                            placeholder="vd: email@example.com"
                            class="w-full px-4 py-3 border rounded-lg border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400"
                            :tabindex="2" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="grid gap-2">
                        <Label for="password" class="block font-semibold">Mật khẩu</Label>
                        <Input id="password" type="password" required autocomplete="new-password"
                            v-model="form.password" placeholder="Mật khẩu"
                            class="w-full px-4 py-3 border rounded-lg border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400"
                            :tabindex="3" />
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Confirm Password -->
                    <div class="grid gap-2">
                        <Label for="password_confirmation" class="block font-semibold">Xác nhận mật khẩu</Label>
                        <Input id="password_confirmation" type="password" required autocomplete="new-password"
                            v-model="form.password_confirmation" placeholder="Nhập lại mật khẩu"
                            class="w-full px-4 py-3 border rounded-lg border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400"
                            :tabindex="4" />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <!-- Submit Button -->
                    <Button type="submit"
                        class="mt-2 w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg transition duration-200 flex items-center justify-center"
                        :tabindex="5" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-3" />
                        Tạo tài khoản
                    </Button>
                </div>
            </form>

            <p class="mt-8 text-center text-gray-700">
                Đã có tài khoản?
                <TextLink :href="route('login')" class="text-orange-600 font-semibold hover:underline" :tabindex="6">
                    Đăng nhập ngay</TextLink>
            </p>
        </AuthBase>
    </div>
</template>
