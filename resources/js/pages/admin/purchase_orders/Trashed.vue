<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Trash2, Undo2 } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quản lý đơn đặt hàng', href: '/admin/purchase-orders' },
    { title: 'Thùng rác', href: '/admin/purchase-orders/trashed' },
];

type PurchaseOrder = {
    id: number;
    po_number: string;
    supplier: { name: string } | null;
    total_amount: number;
    deleted_at: string;
    payment_status: string;
    received_status: string;
    status: { code: string; name: string } | null;
    order_date: string;
    expected_delivery_date: string;
};

const page = usePage<SharedData>();
const orders = ref<PurchaseOrder[]>(page.props.purchaseOrders as PurchaseOrder[]);

function restoreOrder(id: number) {
    router.post(
        `/admin/purchase-orders/${id}/restore`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                orders.value = orders.value.filter((o) => o.id !== id);
                alert('Khôi phục thành công!');
            },
            onError: () => {
                alert('Khôi phục thất bại!');
            },
        },
    );
}

function forceDeleteOrder(id: number) {
    if (confirm('Bạn có chắc muốn xóa vĩnh viễn đơn đặt hàng này?')) {
        router.delete(`/admin/purchase-orders/${id}/force-delete`, {
            preserveScroll: true,
            onSuccess: () => {
                orders.value = orders.value.filter((o) => o.id !== id);
                alert('Xóa vĩnh viễn thành công!');
            },
            onError: () => {
                alert('Xóa vĩnh viễn thất bại!');
            },
        });
    }
}

function comeback() {
    router.visit('/admin/purchase-orders');
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        return date.toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch (e) {
        return dateString;
    }
}

function translatePaymentStatus(status: PurchaseOrder['payment_status']): string {
    switch (status) {
        case 'unpaid':
            return 'Chưa thanh toán';
        case 'partially_paid':
            return 'Đã thanh toán một phần';
        case 'paid':
            return 'Đã thanh toán đủ';
        case 'overdue':
            return 'Quá hạn thanh toán';
        default:
            return status;
    }
}

function getPaymentStatusClass(status: PurchaseOrder['payment_status']): string {
    switch (status) {
        case 'unpaid':
        case 'overdue':
            return 'bg-red-100 text-red-800';
        case 'partially_paid':
            return 'bg-yellow-100 text-yellow-800';
        case 'paid':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// function translatePaymentMethod(method: PurchaseOrder['payment_method']): string {
//     switch (method) {
//         case 'cash': return 'Tiền mặt';
//         case 'bank_transfer': return 'Chuyển khoản ngân hàng';
//         case 'credit': return 'Thẻ tín dụng';
//         case 'check': return 'Séc';
//         default: return method;
//     }
// }

function translateReceivedStatus(status: PurchaseOrder['received_status']): string {
    switch (status) {
        case 'pending':
            return 'Đang chờ nhận';
        case 'partial':
            return 'Đã nhận một phần';
        case 'fully':
            return 'Đã nhận đủ';
        default:
            return status;
    }
}

function getReceivedStatusClass(status: PurchaseOrder['received_status']): string {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'partial':
            return 'bg-blue-100 text-blue-800';
        case 'fully':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function getOrderStatusClass(statusCode: string | undefined): string {
    if (!statusCode) return 'bg-gray-100 text-gray-800';

    switch (statusCode.toLowerCase()) {
        case 'pending':
        case 'draft':
            return 'bg-yellow-100 text-yellow-800';
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'sent':
            return 'bg-indigo-100 text-indigo-800'; // Thêm màu cho 'sent'
        case 'rejected':
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        case 'processing':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
</script>
<template>
    <Head title="Thùng rác đơn đặt hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <h1 class="text-2xl font-bold">Thùng rác đơn đặt hàng</h1>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-100 font-semibold text-gray-800">
                        <tr>
                            <th class="p-3 text-sm font-semibold">Mã PO</th>
                            <th class="p-3 text-sm font-semibold">Nhà cung cấp</th>
                            <th class="p-3 text-sm font-semibold">Ngày đặt</th>
                            <th class="p-3 text-sm font-semibold">Ngày giao dự kiến</th>
                            <th class="p-3 text-sm font-semibold">Trạng thái</th>
                            <th class="p-3 text-sm font-semibold">Tổng tiền</th>
                            <th class="p-3 text-sm font-semibold">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders" :key="order.id" class="rounded-md bg-white shadow-sm">
                            <td class="p-3 text-sm">{{ order.po_number }}</td>
                            <td class="p-3 text-sm">
                                {{ order.supplier ? order.supplier.name : 'N/A' }}
                            </td>
                            <td class="p-3 text-sm">{{ formatDate(order.order_date) }}</td>
                            <td class="p-3 text-sm">{{ formatDate(order.expected_delivery_date) }}</td>

                            <td class="p-3 text-sm">
                                <span :class="getOrderStatusClass(order.status?.code)">
                                    {{ order.status ? order.status.name : 'N/A' }}
                                </span>
                            </td>
                            <td class="p-3 text-sm">{{ order.total_amount?.toLocaleString('vi-VN') }} đ</td>
                            <td class="p-3 text-center text-sm">
                                <div class="flex items-center justify-center space-x-2">
                                    <button
                                        @click="restoreOrder(order.id)"
                                        class="rounded-md bg-green-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none"
                                    >
                                        <Undo2 class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="forceDeleteOrder(order.id)"
                                        class="rounded-md bg-red-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="orders.length === 0">
                            <td colspan="9" class="p-3 text-center text-sm">Không có đơn đặt hàng nào trong thùng rác</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button @click="comeback()" class="text-primary-700 rounded bg-gray-200 px-6 py-2 hover:bg-gray-300">Quay lại</button>
            </div>
        </div>
    </AppLayout>
</template>
