<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
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
</script>

<template>
    <Head title="Thùng rác khách hàng" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <h1 class="text-2xl font-bold">Thùng rác khách hàng</h1>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="w-full table-auto text-left">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="p-3 text-sm font-semibold">Tên khách hàng</th>
                            <th class="p-3 text-sm font-semibold">Email</th>
                            <th class="p-3 text-sm font-semibold">SĐT</th>
                            <th class="p-3 text-sm font-semibold">Địa chỉ</th>
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
                            <td class="p-3 text-sm">{{ customer.address || 'N/A' }}</td>
                            <td class="p-3 text-sm">{{ customer.wallet.toLocaleString('vi-VN') }} VND</td>
                            <td class="p-3 text-sm text-gray-500">{{ customer.deleted_at }}</td>
                            <td class="p-3 text-sm">
                                <button @click="restoreCustomer(customer.id)" class="text-blue-600 hover:underline">Khôi phục</button>
                                <button @click="forceDeleteCustomer(customer.id)" class="ml-2 text-red-600 hover:underline">Xóa vĩnh viễn</button>
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