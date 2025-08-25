<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem, type SharedData } from '@/types';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Thêm tài khoản',
        href: '/admin/users',
    },
];

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
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Container giống danh sách -->
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min p-6 bg-white shadow">
                
                <!-- Tiêu đề -->
                <h1 class="mb-6 text-2xl font-bold">Thêm tài khoản mới</h1>

                <!-- Form -->
                <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 md:grid-cols-2">
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

                <!-- Nút hành động -->
                <div class="mt-6 flex justify-between">
                    <button 
                        @click="router.visit('/admin/users')" 
                        type="button" 
                        class="rounded bg-red-500 px-4 py-2 text-white"
                    >
                        Quay lại
                    </button>
                    <button 
                        @click="submit" 
                        type="button" 
                        class="rounded bg-blue-600 px-4 py-2 text-white"
                    >
                        Lưu
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
