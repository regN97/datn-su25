<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    userRoles: {
        id: number;
        name: string;
    }[];
}>();

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    role_id: '',
    address: '',
    is_active: true
});

function submit() {
    form.post('/admin/users', {
        onSuccess: () => {
            router.visit('/admin/users');
        },
    });
}
</script>

<template>
    <Head title="Thêm tài khoản mới" />
    <AppLayout>
        <h1 class="mb-4 text-2xl font-bold px-4">Thêm tài khoản mới</h1>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-4 md:grid-cols-2 border rounded p-4 bg-white shadow">
            <div>
                <label class="block font-medium">Tên nhân viên</label>
                <input v-model="form.name" class="w-full rounded border p-2" />
                <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="block font-medium">Email</label>
                <input v-model="form.email" type="email" class="w-full rounded border p-2" />
                <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="block font-medium">Chức vụ</label>
                <select v-model="form.role_id" class="w-full rounded border p-2">
                    <option disabled value="">Chọn chức vụ</option>
                    <option v-for="role in props.userRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                </select>
                <p v-if="form.errors.role_id" class="text-sm text-red-600">{{ form.errors.role_id }}</p>
            </div>
            <div>
                <label class="block font-medium">Số điện thoại</label>
                <input v-model="form.phone_number" class="w-full rounded border p-2" />
                <p v-if="form.errors.phone_number" class="text-sm text-red-600">{{ form.errors.phone_number }}</p>
            </div>
            <div>
                <label class="block font-medium">Địa chỉ</label>
                <input v-model="form.address" class="w-full rounded border p-2" />
                <p v-if="form.errors.address" class="text-sm text-red-600">{{ form.errors.address }}</p>
            </div>
            <div>
                <label class="block font-medium">Trạng thái</label>
                <select v-model="form.is_active" class="w-full rounded border p-2">
                    <option :value="true">Hoạt động</option>
                    <option :value="false">Ngừng hoạt động</option>
                </select>
                <p v-if="form.errors.is_active" class="text-sm text-red-600">{{ form.errors.is_active }}</p>
            </div>
        </form>

        <div class="mt-6 flex justify-between">
            <button @click="router.visit('/admin/users')" type="button" class="rounded bg-red-500 px-4 py-2 text-white">
                Quay lại
            </button>
            <button @click="submit" type="button" class="rounded bg-blue-600 px-4 py-2 text-white">
                Lưu
            </button>
        </div>
    </AppLayout>
</template>