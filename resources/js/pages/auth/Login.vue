<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, RefreshCw } from 'lucide-vue-next';
import { ref } from 'vue';

const showPassword = ref(false);

function togglePasswordVisibility() {
    showPassword.value = !showPassword.value;
}
defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
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
            title="Đăng nhập vào tài khoản của bạn"
            description="Nhập email và mật khẩu bên dưới để đăng nhập G7 Mart"
        >
            <Head title="Đăng nhập" />

            <br />

            <div v-if="status" class="mb-6 text-center font-medium text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Email -->
                <div>
                    <Label for="email" class="mb-1 block font-semibold">Địa chỉ email</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="vd: email@example.com"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-orange-400 focus:outline-none"
                        :tabindex="1"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <!-- Password -->
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <Label for="password" class="font-semibold">Mật khẩu</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-orange-500 hover:underline"
                            :tabindex="5"
                        >
                            Quên mật khẩu?
                        </TextLink>
                    </div>

                    <div class="relative">
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            v-model="form.password"
                            placeholder="Nhập mật khẩu của bạn"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-orange-400 focus:outline-none"
                            :tabindex="2"
                        />
                        <button
                            type="button"
                            @click="togglePasswordVisibility"
                            class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-orange-600"
                            tabindex="-1"
                        >
                            <EyeOff v-if="!showPassword" class="h-5 w-5" />
                            <Eye v-else class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <Label for="remember" class="flex cursor-pointer items-center space-x-2 text-gray-700 select-none">
                    <Checkbox
                        id="remember"
                        v-model="form.remember"
                        :tabindex="3"
                        class="form-checkbox h-5 w-5 border-gray-300 text-orange-500 focus:ring-orange-400"
                    />
                    <span>Ghi nhớ đăng nhập</span>
                </Label>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="flex w-full items-center justify-center rounded-lg bg-orange-500 py-3 font-bold text-white transition duration-200 hover:bg-orange-600"
                    :tabindex="4"
                >
                    <RefreshCw v-if="form.processing" class="mr-3 h-5 w-5 animate-spin" />
                    ĐĂNG NHẬP
                </Button>
            </form>

            <!-- Register -->
            <!-- <p class="mt-8 text-center text-gray-700">
                Chưa có tài khoản?
                <TextLink :href="route('register')" class="font-semibold text-orange-600 hover:underline" :tabindex="6"> Đăng ký ngay </TextLink>
            </p> -->
        </AuthBase>
    </div>
</template>
