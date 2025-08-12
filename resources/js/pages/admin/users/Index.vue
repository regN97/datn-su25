<script setup lang="ts">
import DeleteModal from '@/components/DeleteModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PackagePlus, Pencil, Trash2, Eye } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý tài khoản',
        href: '/admin/users',
    },
];

type User = {
    id: number;
    name: string;
    email: string;
    phone_number: string | null;
    role?: { name: string };
};

const page = usePage<SharedData>();
const users = ref<User[]>(Array.isArray(page.props.users) ? [...page.props.users] : []);

const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

const total = computed(() => users.value.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return users.value.slice(start, start + perPage.value);
});

function goToPage(page: number) {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
}

function prevPage() {
    if (currentPage.value > 1) currentPage.value--;
}

function nextPage() {
    if (currentPage.value < totalPages.value) currentPage.value++;
}

function changePerPage(event: Event) {
    perPage.value = +(event.target as HTMLSelectElement).value;
    currentPage.value = 1;
}

function goToCreatePage() {
    router.visit('/admin/users/create');
}

function goToEditPage(id: number) {
    router.visit(`/admin/users/${id}/edit`);
}

function goToShowPage(id: number) {
    router.visit(`/admin/users/${id}`);
}

const showDeleteModal = ref(false);
const userToDelete = ref<number | null>(null);

function confirmDelete(id: number) {
    userToDelete.value = id;
    showDeleteModal.value = true;
}

function handleDeleteUser() {
    if (!userToDelete.value) return;
    router.delete(`/admin/users/${userToDelete.value}`, {
        onSuccess: () => {
            const idx = users.value.findIndex((u) => u.id === userToDelete.value);
            if (idx !== -1) users.value.splice(idx, 1);
            showDeleteModal.value = false;
            userToDelete.value = null;
        },
        preserveState: true,
    });
}

function cancelDelete() {
    showDeleteModal.value = false;
    userToDelete.value = null;
}
</script>

<template>
    <Head title="Danh sách tài khoản" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <!-- Tiêu đề + nút thêm -->
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Tài khoản</h1>
                        <button @click="goToCreatePage" class="rounded-3xl bg-green-500 px-8 py-2 text-white hover:bg-green-600">
                            <PackagePlus />
                        </button>
                    </div>

                    <!-- Bảng danh sách -->
                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="w-[5%] p-3 text-center text-sm font-semibold">STT</th>
                                    <th class="w-[20%] p-3 text-left text-sm font-semibold">Tên</th>
                                    <th class="w-[25%] p-3 text-left text-sm font-semibold">Email</th>
                                    <th class="w-[15%] p-3 text-center text-sm font-semibold">Số điện thoại</th>
                                    <th class="w-[15%] p-3 text-center text-sm font-semibold">Chức vụ</th>
                                    <th class="w-[20%] p-3 text-center text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(user, idx) in paginatedUsers" :key="user.id" class="border-t">
                                    <td class="p-3 text-center text-sm">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                    <td class="p-3 text-left text-sm">{{ user.name }}</td>
                                    <td class="p-3 text-left text-sm">
                                        {{ user.email && user.email.length > 20 ? user.email.slice(0, 20) + '...' : user.email }}
                                    </td>
                                    <td class="p-3 text-center text-sm">{{ user.phone_number || 'N/A' }}</td>
                                    <td class="p-3 text-center text-sm">{{ user.role?.name || 'N/A' }}</td>
                                    <td class="p-3 text-center text-sm">
                                        <button class="me-1 rounded-md bg-blue-600 px-3 py-1 text-white hover:bg-blue-700" @click="goToShowPage(user.id)">
                                            <Eye class="h-4 w-4" />
                                        </button>
                                        <button class="me-1 rounded-md bg-yellow-500 px-3 py-1 text-white hover:bg-yellow-600" @click="goToEditPage(user.id)">
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                        <button class="rounded-md bg-red-600 px-3 py-1 text-white hover:bg-red-700" @click="confirmDelete(user.id)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="paginatedUsers.length === 0">
                                    <td colspan="6" class="p-3 text-center text-sm">Không có dữ liệu</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Phân trang -->
                    <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm">
                            Hiển thị kết quả từ
                            <span class="font-semibold">{{ (currentPage - 1) * perPage + 1 }}</span>
                            -
                            <span class="font-semibold">{{ Math.min(currentPage * perPage, total) }}</span>
                            trên tổng <span class="font-semibold">{{ total }}</span>
                        </p>
                        <div class="flex items-center space-x-2">
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700" :disabled="currentPage === 1" @click="prevPage">
                                ← Trang trước
                            </button>
                            <template v-for="page in totalPages" :key="page">
                                <button
                                    class="rounded px-3 py-1 text-sm"
                                    :class="page === currentPage ? 'bg-gray-200 font-bold' : 'text-gray-500 hover:text-gray-700'"
                                    @click="goToPage(page)"
                                >
                                    {{ page }}
                                </button>
                            </template>
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700" :disabled="currentPage === totalPages" @click="nextPage">
                                Trang sau →
                            </button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <p class="text-sm">Hiển thị</p>
                            <select class="rounded border p-1 text-sm" v-model="perPage" @change="changePerPage">
                                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <p class="text-sm">kết quả</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DeleteModal
            :is-open="showDeleteModal"
            title="Xóa tài khoản"
            message="Bạn có chắc chắn muốn xóa tài khoản này?"
            @confirm="handleDeleteUser"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>

<style scoped>
.table-wrapper table {
    min-width: 100%;
    table-layout: fixed;
}
</style>
