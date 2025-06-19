<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

defineProps<{
    purchaseReturn: {
        return_number: string;
        purchase_order_code: string;
        supplier_name: string;
        reason: string | null;
        return_date: string;
        status: string;
        created_by: string;
        total_items_returned: number;
        total_value_returned: number;
        items: {
            product_name: string;
            batch_number: string;
            product_sku: string;
            manufacturing_date: string | null;
            expiry_date: string | null;
            quantity_returned: number;
            unit_cost: number;
            subtotal: number;
            reason: string | null;
        }[];
    };
}>();

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
};

const statusTextClass = (status: string) => {
    switch (status.toLowerCase()) {
        case 'rejected':
            return 'text-red-500';
        case 'approved':
            return 'text-blue-500';
        case 'pending':
            return 'text-amber-500';
        case 'completed':
            return 'text-green-500';
        default:
            return 'text-gray-500';
    }
};

const translateStatus = (status: string) => {
    switch (status.toLowerCase()) {
        case 'pending':
            return 'Chờ duyệt';
        case 'approved':
            return 'Đã duyệt';
        case 'completed':
            return 'Hoàn tất';
        case 'rejected':
            return 'Từ chối';
        default:
            return status;
    }
};

function goToIndex() {
    router.visit('/admin/purchaseReturn');
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý phiếu trả hàng',
        href: '/admin/purchaseReturn',
    },
];
</script>

<template>
    <Head title="Chi tiết phiếu trả hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-screen flex-1 flex-col gap-6 rounded-2xl bg-gray-50 p-8">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-md">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="border-l-4 border-blue-500 pl-4 text-2xl font-bold text-gray-800">Chi tiết phiếu trả hàng</h1>
                    <button
                        @click="goToIndex"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-100"
                    >
                        🔙 Quay lại danh sách
                    </button>
                </div>

                <!-- General Info -->
                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2 rounded-xl border border-gray-200 bg-gray-50 p-4 shadow-sm">
                        <p><strong>Mã phiếu trả hàng:</strong> {{ purchaseReturn.return_number }}</p>
                        <p><strong>Mã đơn đặt hàng:</strong> {{ purchaseReturn.purchase_order_code }}</p>
                        <p><strong>Nhà cung cấp:</strong> {{ purchaseReturn.supplier_name }}</p>
                        <p><strong>Lý do trả hàng:</strong> {{ purchaseReturn.reason || 'Không có' }}</p>
                    </div>
                    <div class="space-y-2 rounded-xl border border-gray-200 bg-gray-50 p-4 shadow-sm">
                        <p><strong>Ngày trả hàng:</strong> {{ formatDate(purchaseReturn.return_date) }}</p>
                        <p>
                            <strong>Trạng thái:</strong>
                            <span class="inline-block rounded px-3 py-1 text-base font-medium" :class="statusTextClass(purchaseReturn.status)">
                                {{ translateStatus(purchaseReturn.status) }}
                            </span>
                        </p>
                        <p><strong>Người tạo phiếu:</strong> {{ purchaseReturn.created_by }}</p>
                    </div>
                </div>

                <!-- Product Table -->
                <div class="mb-8">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Danh sách sản phẩm</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-3 text-sm">
                            <thead class="bg-blue-50 font-semibold text-gray-700 uppercase">
                                <tr>
                                    <th class="px-6 py-4 text-left">Tên sản phẩm</th>
                                    <th class="px-6 py-4 text-left">Mã lô</th>
                                    <th class="px-6 py-4 text-left">Mã SKU</th>
                                    <th class="px-6 py-4 text-left">NSX</th>
                                    <th class="px-6 py-4 text-left">HSD</th>
                                    <th class="px-6 py-4 text-center">Số lượng</th>
                                    <th class="px-6 py-4 text-right">Đơn giá</th>
                                    <th class="px-6 py-4 text-right">Tổng tiền</th>
                                    <th class="px-6 py-4 text-left">Lý do trả</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in purchaseReturn.items"
                                    :key="item.product_sku"
                                    class="rounded-lg border border-gray-200 bg-white shadow-sm"
                                >
                                    <td class="rounded-l-lg px-6 py-4">{{ item.product_name }}</td>
                                    <td class="px-6 py-4">{{ item.batch_number }}</td>
                                    <td class="px-6 py-4">{{ item.product_sku }}</td>
                                    <td class="px-6 py-4">{{ item.manufacturing_date ? formatDate(item.manufacturing_date) : '—' }}</td>
                                    <td class="px-6 py-4">{{ item.expiry_date ? formatDate(item.expiry_date) : '—' }}</td>
                                    <td class="px-6 py-4 text-center">{{ item.quantity_returned }}</td>
                                    <td class="px-6 py-4 text-right">{{ item.unit_cost.toLocaleString() }} đ</td>
                                    <td class="px-6 py-4 text-right">{{ item.subtotal.toLocaleString() }} đ</td>
                                    <td class="rounded-r-lg px-6 py-4">{{ item.reason || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totals -->
                <div class="mb-6 flex flex-col justify-between font-semibold text-gray-800 md:flex-row">
                    <p>Tổng số sản phẩm trả: {{ purchaseReturn.total_items_returned }}</p>
                    <p>Tổng giá trị trả lại: {{ purchaseReturn.total_value_returned.toLocaleString() }} đ</p>
                </div>

                <!-- Actions -->
                <div class="flex gap-4">
                    <button class="rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">Gửi yêu cầu</button>
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-white transition hover:bg-red-700">Xoá</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
