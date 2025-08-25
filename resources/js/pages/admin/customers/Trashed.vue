<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Trash2, Undo2 } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quản lý khách hàng', href: '/admin/customers' },
    { title: 'Thùng rác', href: '/admin/customers/trashed' },
];

type Customer = {
    id: number;
    customer_name: string;
    email: string | null;
    phone: string;
    address: string | null;
    wallet: number;
    deleted_at: string;
};

const page = usePage<SharedData>();
const customers = ref<Customer[]>(Array.isArray(page.props.customers) ? [...page.props.customers] : []);

function restoreCustomer(id: number) {
    router.post(
        route('admin.customers.restore', id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                customers.value = customers.value.filter((c) => c.id !== id);
                alert('Khôi phục khách hàng thành công!');
            },
            onError: () => {
                alert('Khôi phục khách hàng thất bại!');
            },
        },
    );
}

function forceDeleteCustomer(id: number) {
    if (confirm('Bạn có chắc muốn xóa vĩnh viễn khách hàng này?')) {
        router.delete(route('admin.customers.forceDelete', id), {
            preserveScroll: true,
            onSuccess: () => {
                customers.value = customers.value.filter((c) => c.id !== id);
                alert('Xóa vĩnh viễn khách hàng thành công!');
            },
            onError: () => {
                alert('Xóa vĩnh viễn khách hàng thất bại!');
            },
        });
    }
}
function goBack() {
  router.visit('/admin/customers') 
}
</script>

<template>
    <Head title="Thùng rác khách hàng" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <h1 class="text-2xl font-bold">Thùng rác khách hàng</h1>
            <!-- Nút quay lại -->
<div class="flex justify-end">
  <button
    @click="goBack"
    class="rounded bg-gray-200 px-6 py-2 text-gray-700 hover:bg-gray-300"
  >
    <ChevronLeft class="w-4 h-4" />
    <span>Quay lại</span>
  </button>
</div>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="w-full table-auto text-left">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="p-3 text-sm font-semibold">Tên khách hàng</th>
                            <th class="p-3 text-sm font-semibold">Email</th>
                            <th class="p-3 text-sm font-semibold">SĐT</th>
                            <!-- Đã xóa cột địa chỉ -->
                            <th class="p-3 text-sm font-semibold">Ví tiền</th>
                            <th class="p-3 text-sm font-semibold">Thời gian xóa</th>
                            <th class="p-3 text-sm font-semibold">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="customer in customers" :key="customer.id" class="border-t">
                            <td class="p-3 text-sm">{{ customer.customer_name }}</td>
                            <td class="p-3 text-sm">{{ customer.email || 'N/A' }}</td>
                            <td class="p-3 text-sm">{{ customer.phone || 'N/A' }}</td>
                            <!-- Đã xóa cột địa chỉ -->
                            <td class="p-3 text-sm">{{ customer.wallet.toLocaleString('vi-VN') }} VND</td>
                            <td class="p-3 text-sm text-gray-500">{{ customer.deleted_at }}</td>
                            <td class="p-3 text-center text-sm">
                                <div class="flex items-center justify-center space-x-2">
                                    <button
                                        @click="restoreCustomer(customer.id)"
                                        class="rounded-md bg-green-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none"
                                    >
                                        <Undo2 class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="forceDeleteCustomer(customer.id)"
                                        class="rounded-md bg-red-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="customers.length === 0">
                            <td colspan="7" class="p-3 text-center text-sm">Không có khách hàng nào trong thùng rác</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    border: 1px solid #e5e7eb;
}
</style>
