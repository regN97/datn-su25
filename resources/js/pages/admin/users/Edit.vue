<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string | null;
        phone_number: string | null;
        role_id: number;
        address: string | null;
        is_active: boolean;
    };
    userRoles: {
        id: number;
        name: string;
    }[];
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone_number: props.user.phone_number,
    address: props.user.address,
    is_active: props.user.is_active,
    role_id: props.user.role_id,
});

function submit() {
    form.put(`/admin/users/${props.user.id}`, {
        onSuccess: () => {
            // có thể hiển thị thông báo hoặc chuyển trang
        },
    });
}
</script>

<template>
    <Head title="Cập nhật thông tin nhân viên" />
    <AppLayout>
        <h1 class="mb-4 text-2xl font-bold px-4">Cập nhật thông tin nhân viên</h1>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-4 md:grid-cols-2 border rounded p-4 bg-white shadow mt-4">
            <div>
                <label class="block font-medium">Tên nhân viên</label>
                <input v-model="form.name" class="w-full rounded border p-2" />
            </div>
            <div>
                <label class="block font-medium">Email</label>
                <input v-model="form.email" type="email" class="w-full rounded border p-2" />
                <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="block font-medium">Chức vụ</label>
                <select v-model="form.role_id" class="w-full rounded border p-2">
                    <option v-for="role in props.userRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                </select>
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
            <button @click="router.visit('/admin/users')" class="rounded bg-red-500 px-4 py-2 text-white">Quay lại</button>

            <button @click="submit" class="rounded bg-blue-600 px-4 py-2 text-white">Lưu</button>
        </div>
    </AppLayout>
</template>
