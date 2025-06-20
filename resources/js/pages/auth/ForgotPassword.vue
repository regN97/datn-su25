<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>
<template>
    <div
        class="flex min-h-screen items-center justify-center bg-cover bg-center bg-no-repeat px-4"
        style="
            background-image: url('https://png.pngtree.com/thumb_back/fh260/background/20230525/pngtree-3d-grocery-store-with-orange-goods-in-the-background-image_2617236.jpg');
        "
    >
        <AuthBase class="bg-opacity-95 relative z-10 w-full max-w-md rounded-3xl bg-white p-10 text-gray-900 shadow-xl">
            <Head title="Quên mật khẩu" />

            <h2 class="mb-6 text-center text-3xl font-bold">Quên mật khẩu</h2>
            <p class="mb-8 text-center text-gray-600">Nhập email của bạn để nhận liên kết đặt lại mật khẩu</p>

            <div v-if="status" class="mb-6 text-center font-medium text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Email -->
                <div>
                    <Label for="email" class="mb-1 block font-semibold"> Địa chỉ email </Label>
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
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="flex w-full items-center justify-center rounded-lg bg-orange-500 py-3 font-bold text-white transition duration-200 hover:bg-orange-600"
                    :tabindex="2"
                >
                    <LoaderCircle v-if="form.processing" class="mr-3 h-5 w-5 animate-spin" />
                    Gửi liên kết đặt lại mật khẩu
                </Button>
            </form>

            <p class="mt-8 text-center text-gray-700">
                Hoặc,
                <TextLink :href="route('login')" class="font-semibold text-orange-600 hover:underline" :tabindex="3"> trở về đăng nhập </TextLink>
            </p>
        </AuthBase>
    </div>
</template>
