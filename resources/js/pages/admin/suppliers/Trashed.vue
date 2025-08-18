<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Trash2, Undo2 } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quản lý nhà cung cấp', href: '/admin/suppliers' },
    { title: 'Thùng rác', href: '/admin/suppliers/trashed' },
];

type Supplier = {
    id: number;
    name: string;
    contact_person: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    deleted_at: string;
};

const page = usePage<SharedData>();
const suppliers = ref<Supplier[]>(page.props.suppliers as Supplier[]);

function restoreSupplier(id: number) {
    router.post(
        `/admin/suppliers/${id}/restore`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                suppliers.value = suppliers.value.filter((s) => s.id !== id);
                alert('Khôi phục thành công!');
            },
            onError: () => {
                alert('Khôi phục thất bại!');
            },
        },
    );
}

function forceDeleteSupplier(id: number) {
    if (confirm('Bạn có chắc muốn xóa vĩnh viễn nhà cung cấp này?')) {
        router.delete(`/admin/suppliers/${id}/force-delete`, {
            preserveScroll: true,
            onSuccess: () => {
                suppliers.value = suppliers.value.filter((s) => s.id !== id);
                alert('Xóa vĩnh viễn thành công!');
            },
            onError: () => {
                alert('Xóa vĩnh viễn thất bại!');
            },
        });
    }
}
</script>

<template>
    <Head title="Thùng rác nhà cung cấp" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <h1 class="text-2xl font-bold">Thùng rác nhà cung cấp</h1>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="w-full table-auto text-left">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="p-3 text-sm font-semibold">Tên nhà cung cấp</th>
                            <th class="p-3 text-sm font-semibold">Người liên hệ</th>
                            <th class="p-3 text-sm font-semibold">Email</th>
                            <th class="p-3 text-sm font-semibold">SĐT</th>
                            <th class="p-3 text-sm font-semibold">Địa chỉ</th>
                            <th class="p-3 text-sm font-semibold">Thời gian xóa</th>
                            <th class="p-3 text-sm font-semibold">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="supplier in suppliers" :key="supplier.id" class="border-t">
                            <td class="p-3 text-sm">{{ supplier.name }}</td>
                            <td class="p-3 text-sm">{{ supplier.contact_person || 'N/A' }}</td>
                            <td class="p-3 text-sm">{{ supplier.email || 'N/A' }}</td>
                            <td class="p-3 text-sm">{{ supplier.phone || 'N/A' }}</td>
                            <td class="p-3 text-sm">{{ supplier.address || 'N/A' }}</td>
                            <td class="p-3 text-sm text-gray-500">{{ supplier.deleted_at }}</td>
                            <td class="p-3 text-center text-sm">
                                <div class="flex items-center justify-center space-x-2">
                                    <button
                                        @click="restoreSupplier(supplier.id)"
                                        class="rounded-md bg-green-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none"
                                    >
                                        <Undo2 class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="forceDeleteSupplier(supplier.id)"
                                        class="rounded-md bg-red-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="suppliers.length === 0">
                            <td colspan="7" class="p-3 text-center text-sm">Không có nhà cung cấp nào trong thùng rác</td>
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
