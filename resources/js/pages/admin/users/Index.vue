<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Trash, Eye } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string | null;
    phone_number: string | null;
    role?: {
        id: number;
        name: string;
        code: string;
    };
}

defineProps<{
    users: User[];
    userRoles: {
        id: number;
        name: string;
    }[];
}>();

const showForm = ref(false);
const isEditing = ref(false);
const editingUserId = ref<number | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    phone_number: '',
    role_id: '' as string | number, // Để ép user chọn
});

function submit() {
    if (isEditing.value && editingUserId.value !== null) {
        form.put(`/admin/users/${editingUserId.value}`, {
            onSuccess: () => {
                resetForm();
                alert('Cập nhật tài khoản thành công');
            },
        });
    } else {
        form.post('/admin/users', {
            onSuccess: () => {
                resetForm();
                alert('Thêm tài khoản thành công');
            },
        });
    } 
}

function goToShowPage(id: number) {
    router.get(`/admin/users/${id}`);
}

function editUser(user: User) {
    showForm.value = true;
    isEditing.value = true;
    editingUserId.value = user.id;
    form.name = user.name;
    form.email = user.email ?? '';
    form.phone_number = user.phone_number ?? '';
    form.password = '';
    form.role_id = user.role?.id ?? '';
}

function resetForm() {
    form.reset();
    showForm.value = false;
    isEditing.value = false;
    editingUserId.value = null;
}

function deleteUser(id: number) {
    if (confirm('Bạn có chắc chắn muốn xoá tài khoản này?')) {
        router.delete(`/admin/users/${id}`, {
            onSuccess: () => {
                alert('Đã xoá tài khoản thành công');
            },
        });
    }
}

const page = usePage() as { props: { flash: { success?: string } } };
const hasShownSuccess = ref(false);

onMounted(() => {
    if (page.props.flash.success && !hasShownSuccess.value) {
        alert(page.props.flash.success);
        hasShownSuccess.value = true;
    }
});
</script>

<template>
    <Head title="Quản lý tài khoản" />
    <AppLayout :breadcrumbs="[{ title: 'Quản lý tài khoản', href: '/admin/users' }]">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <!-- Tiêu đề và nút -->
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold text-gray-800">Danh sách tài khoản</h1>
                        <button class="rounded-3xl bg-green-600 px-6 py-2 text-white hover:bg-green-700" @click="showForm = !showForm">
                            {{ showForm ? 'Đóng' : 'Thêm mới' }}
                        </button>
                    </div>

                    <!-- Form thêm mới -->
                    <div v-if="showForm" class="mb-6 rounded-xl bg-white p-6 shadow">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block font-medium">Tên tài khoản</label>
                                <input v-model="form.name" type="text" class="w-full rounded border p-2" />
                                <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block font-medium">Email</label>
                                <input v-model="form.email" type="email" class="w-full rounded border p-2" />
                                <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block font-medium">Mật khẩu</label>
                                <input v-model="form.password" type="password" class="w-full rounded border p-2" />
                                <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
                            </div>
                            <div>
                                <label class="block font-medium">Số điện thoại</label>
                                <input v-model="form.phone_number" type="text" class="w-full rounded border p-2" />
                                <p v-if="form.errors.phone_number" class="text-sm text-red-600">{{ form.errors.phone_number }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="mb-3 block font-medium">Vai trò</label>
                            <select v-model="form.role_id" class="w-full rounded border p-2">
                                <option disabled value="">Chọn vai trò</option>
                                <option v-for="role in userRoles" :key="role.id" :value="Number(role.id)">
                                    {{ role.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.role_id" class="text-sm text-red-600">{{ form.errors.role_id }}</p>
                        </div>

                        <div class="mt-4">
                            <button @click="submit" class="rounded bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">
                                {{ isEditing ? 'Cập nhật tài khoản' : 'Thêm tài khoản' }}
                            </button>
                        </div>
                    </div>

                    <!-- Bảng danh sách -->
                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="min-w-full table-auto border-separate border-spacing-y-3">
                            <thead class="bg-gray-200 text-sm font-semibold text-gray-700 uppercase">
                                <tr>
                                    <th class="px-6 py-4 text-left">Tên</th>
                                    <th class="px-6 py-4 text-left">Email</th>
                                    <th class="px-6 py-4 text-left">Số điện thoại</th>
                                    <th class="px-6 py-4 text-left">Vai trò</th>
                                    <th class="px-6 py-4 text-center">Hành động</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="user in users"
                                    :key="user.id"
                                    class="rounded-lg border border-gray-200 bg-white shadow-sm transition hover:bg-gray-100"
                                >
                                    <td class="rounded-l-lg px-6 py-4">{{ user.name }}</td>
                                    <td class="px-6 py-4">{{ user.email }}</td>
                                    <td class="px-6 py-4">{{ user.phone_number }}</td>
                                    <td class="px-6 py-4">{{ user.role?.name }}</td>
                                    <td class="rounded-r-lg px-6 py-4 text-center">
                                        <button class="p-1 text-gray-600 transition hover:text-gray-800" title="Xem chi tiết" @click="goToShowPage(user.id)">
                                            <Eye class="h-5 w-5" />
                                         </button>
                                        <button class="p-1 text-blue-600 transition hover:text-blue-800" title="Sửa" @click="editUser(user)">
                                            <Pencil class="h-5 w-5" />
                                        </button>
                                        <button class="ml-2 p-1 text-red-600 transition hover:text-red-800" title="Xoá" @click="deleteUser(user.id)">
                                            <Trash class="h-5 w-5" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Không có dữ liệu</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
