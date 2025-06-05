<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, EyeOff, Eye, RefreshCw  } from 'lucide-vue-next';
import { ref } from 'vue';

const showPassword = ref(false)

function togglePasswordVisibility() {
    showPassword.value = !showPassword.value
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
    <div class="min-h-screen flex items-center justify-center px-4 bg-cover bg-center bg-no-repeat"
        style="background-image: url('https://png.pngtree.com/thumb_back/fh260/background/20230525/pngtree-3d-grocery-store-with-orange-goods-in-the-background-image_2617236.jpg');">
        <AuthBase class="relative z-10 bg-white bg-opacity-95 p-10 rounded-3xl shadow-xl max-w-2xl w-full text-gray-900"
            title="Đăng nhập vào tài khoản của bạn" description="Nhập email và mật khẩu bên dưới để đăng nhập G7 Mart">

            <Head title="Đăng nhập" />

            <br>

            <div v-if="status" class="mb-6 text-center text-green-600 font-medium">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Email -->
                <div>
                    <Label for="email" class="block mb-1 font-semibold">Địa chỉ email</Label>
                    <Input id="email" type="email" required autofocus autocomplete="email" v-model="form.email"
                        placeholder="vd: email@example.com"
                        class="w-full px-4 py-3 border rounded-lg border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400"
                        :tabindex="1" />
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <Label for="password" class="font-semibold">Mật khẩu</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')"
                            class="text-sm text-orange-500 hover:underline" :tabindex="5">
                            Quên mật khẩu?
                        </TextLink>
                    </div>

                    <div class="relative">
                        <Input id="password" :type="showPassword ? 'text' : 'password'" required
                            autocomplete="current-password" v-model="form.password" placeholder="Nhập mật khẩu của bạn"
                            class="w-full px-4 py-3 border rounded-lg border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400"
                            :tabindex="2" />
                        <button type="button" @click="togglePasswordVisibility"
                            class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-orange-600"
                            tabindex="-1">
                            <EyeOff v-if="!showPassword" class="w-5 h-5" />
                            <Eye v-else class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <Label for="remember" class="flex items-center space-x-2 cursor-pointer select-none text-gray-700">
                    <Checkbox id="remember" v-model="form.remember" :tabindex="3"
                        class="form-checkbox h-5 w-5 text-orange-500 border-gray-300 focus:ring-orange-400" />
                    <span>Ghi nhớ đăng nhập</span>
                </Label>

                <!-- Submit Button -->
                <Button type="submit" :disabled="form.processing"
                    class="w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg transition duration-200 flex items-center justify-center"
                    :tabindex="4">
                    <RefreshCw  v-if="form.processing" class="h-5 w-5 animate-spin mr-3" />
                    ĐĂNG NHẬP
                </Button>
            </form>

            <!-- Register -->
            <p class="mt-8 text-center text-gray-700">
                Chưa có tài khoản?
                <TextLink :href="route('register')" class="text-orange-600 font-semibold hover:underline" :tabindex="6">
                    Đăng ký ngay
                </TextLink>
            </p>

        </AuthBase>
    </div>
</template>
