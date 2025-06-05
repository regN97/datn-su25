<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
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
  <div class="min-h-screen flex items-center justify-center px-4 bg-cover bg-center bg-no-repeat"
    style="background-image: url('https://png.pngtree.com/thumb_back/fh260/background/20230525/pngtree-3d-grocery-store-with-orange-goods-in-the-background-image_2617236.jpg');">
    <AuthBase class="relative z-10 bg-white bg-opacity-95 p-10 rounded-3xl shadow-xl max-w-md w-full text-gray-900">

      <Head title="Quên mật khẩu" />

      <h2 class="text-3xl font-bold mb-6 text-center">Quên mật khẩu</h2>
      <p class="text-center text-gray-600 mb-8">
        Nhập email của bạn để nhận liên kết đặt lại mật khẩu
      </p>

      <div v-if="status" class="mb-6 text-center text-green-600 font-medium">
        {{ status }}
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Email -->
        <div>
          <Label for="email" class="block mb-1 font-semibold">
            Địa chỉ email
          </Label>
          <Input id="email" type="email" required autofocus autocomplete="email" v-model="form.email"
            placeholder="vd: email@example.com"
            class="w-full px-4 py-3 border rounded-lg border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400"
            :tabindex="1" />
          <InputError :message="form.errors.email" />
        </div>

        <!-- Submit Button -->
        <Button type="submit" :disabled="form.processing"
          class="w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg transition duration-200 flex items-center justify-center"
          :tabindex="2">
          <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-3" />
          Gửi liên kết đặt lại mật khẩu
        </Button>
      </form>

      <p class="mt-8 text-center text-gray-700">
        Hoặc,
        <TextLink :href="route('login')" class="text-orange-600 font-semibold hover:underline" :tabindex="3">
          trở về đăng nhập
        </TextLink>
      </p>
    </AuthBase>
  </div>
</template>
