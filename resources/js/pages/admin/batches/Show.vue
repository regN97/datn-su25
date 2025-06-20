<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{
    batch: {
        id: number;
        batch_number: string;
        purchase_order_id: number;
        supplier_id: number | null;
        received_date: string;
        invoice_number: string | null;
        total_amount: number;
        payment_status: 'unpaid' | 'partially_paid' | 'paid';
        paid_amount: number;
        receipt_status: 'pending' | 'partially_received' | 'completed' | 'canceled';
        notes: string | null;
        created_at: string;
        updated_at: string;


        created_by: {
            id: number;
            name: string;
            email: string;
        };
        updated_by: {
            id: number;
            name: string;
            email: string;
        } | null;

        supplier?: {
            id: number;
            name: string;
            address?: string;
            phone?: string;
        };
        purchase_order?: {
            id: number;
            po_number: string;
            order_date?: string;
            total_amount?: number;
        };


        batch_items: Array<{
            id: number;
            batch_id: number;
            product_id: number;
            purchase_order_item_id: number;
            ordered_quantity: number;
            received_quantity: number;
            remaining_quantity: number;
            current_quantity: number;
            purchase_price: number;
            total_amount: number;
            manufacturing_date: string | null;
            expiry_date: string | null;
            inventory_status: 'active' | 'low_stock' | 'out_of_stock' | 'expired';
            created_at: string;
            updated_at: string;

            created_by: {
                id: number;
                name: string;
                email?: string;
            };
            updated_by: {
                id: number;
                name: string;
                email?: string;
            } | null;

            product?: {
                id: number;
                name: string;
                sku?: string;
                image_url?: string;
                unit?: {
                    id: number;
                    name: string;
                };
                description?: string;
            };
            purchaseOrderItem?: {
                id: number;
                quantity_ordered: number;
                unit_cost: number;
            };

        }>;
    };
}>();

console.log('Batch data in Vue:', props.batch);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý lô hàng',
        href: '/admin/batches',
    },
    {
        title: 'Chi tiết lô hàng',
        href: `/admin/batches/${props.batch.id}`,
    },
];

// Hàm định dạng tiền tệ sang VND
function formatCurrency(value: number | null): string {
    if (value === null || isNaN(value)) {
        return 'N/A';
    }
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(value);
}

function getStatusDisplayName(status: string): string {
    switch (status) {
        case 'active': return 'Còn hàng';
        case 'low_stock': return 'Sắp hết hàng';
        case 'out_of_stock': return 'Hết hàng';
        case 'expired': return 'Hết hạn';
        case 'unpaid': return 'Chưa thanh toán';
        case 'partially_paid': return 'Đã thanh toán một phần';
        case 'paid': return 'Đã thanh toán';
        case 'pending': return 'Đang chờ xử lý';
        case 'partially_received': return 'Đã nhận một phần';
        case 'completed': return 'Đã hoàn thành';
        case 'canceled': return 'Đã hủy';
        default:
            return 'Không xác định';
    }
}

function formatDateTime(dateString: string | null): string {
    if (!dateString) {
        return 'N/A';
    }
    try {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }).format(date);
    } catch (e) {
        console.error('Lỗi định dạng ngày/giờ:', dateString, e);
        return dateString;
    }
}

function formatDateOnly(dateString: string | null): string {
    if (!dateString) {
        return 'N/A';
    }
    try {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).format(date);
    } catch (e) {
        console.error('Lỗi định dạng ngày:', dateString, e);
        return dateString;
    }
}

function goBack() {
    router.visit('/admin/batches');
}
</script>

<template>

    <Head title="Chi tiết Lô hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="container mx-auto p-6">
                <div
                    class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min bg-white shadow-lg">
                    <div class="container mx-auto p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-800">
                                Thông tin lô hàng - {{ props.batch.batch_number }}
                            </h2>
                            <button @click="goBack"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Quay lại
                            </button>
                        </div>

                        <div
                            class="grid grid-cols-1 md:grid-cols-3 gap-6 text-gray-700 mb-8 p-4 border border-gray-200 rounded-md bg-white text-sm font-sans leading-relaxed">

                            <!-- Cột 1 -->
                            <div class="space-y-1.5">
                                <p><strong>Mã lô hàng:</strong> <span class="text-gray-900 font-semibold">{{
                                        props.batch.batch_number }}</span></p>
                                <p><strong>Nhà cung cấp:</strong> {{ props.batch.supplier?.name || 'N/A' }}</p>
                                <p class="ml-4 text-gray-600" v-if="props.batch.supplier?.address">Địa chỉ: {{
                                    props.batch.supplier.address }}</p>
                                <p class="ml-4 text-gray-600" v-if="props.batch.supplier?.phone">SĐT: {{
                                    props.batch.supplier.phone }}</p>
                                <p><strong>Đơn hàng mua:</strong> {{ props.batch.purchase_order?.po_number || 'N/A' }}
                                </p>
                                <p class="ml-4 text-gray-600" v-if="props.batch.purchase_order?.order_date">Ngày đặt: {{
                                    formatDateOnly(props.batch.purchase_order.order_date) }}</p>
                                <p class="ml-4 text-gray-600" v-if="props.batch.purchase_order?.total_amount">Tổng đơn:
                                    {{ formatCurrency(props.batch.purchase_order.total_amount) }}</p>
                                <p><strong>Ngày nhận hàng:</strong> {{ formatDateOnly(props.batch.received_date) }}</p>
                                <p><strong>Số hóa đơn:</strong> {{ props.batch.invoice_number || 'N/A' }}</p>
                            </div>

                            <!-- Cột 2 -->
                            <div class="space-y-1.5">
                                <p><strong>Tổng giá trị lô hàng:</strong> <span class="text-indigo-700 font-semibold">{{
                                        formatCurrency(props.batch.total_amount) }}</span></p>
                                <p><strong>Trạng thái thanh toán:</strong>
                                    <span :class="{
                                        'text-green-600 font-medium': props.batch.payment_status === 'paid',
                                        'text-yellow-600 font-medium': props.batch.payment_status === 'partially_paid',
                                        'text-red-600 font-medium': props.batch.payment_status === 'unpaid',
                                    }">
                                        {{ getStatusDisplayName(props.batch.payment_status) }}
                                    </span>
                                </p>
                                <p><strong>Số tiền đã thanh toán:</strong> {{ formatCurrency(props.batch.paid_amount) }}
                                </p>
                                <p><strong>Trạng thái nhập hàng:</strong>
                                    <span :class="{
                                        'text-green-600 font-medium': props.batch.receipt_status === 'completed',
                                        'text-yellow-600 font-medium': props.batch.receipt_status === 'partially_received',
                                        'text-blue-600 font-medium': props.batch.receipt_status === 'pending',
                                        'text-red-600 font-medium': props.batch.receipt_status === 'canceled',
                                    }">
                                        {{ getStatusDisplayName(props.batch.receipt_status) }}
                                    </span>
                                </p>
                            </div>

                            <!-- Cột 3 -->
                            <div class="space-y-1.5">
                                <p><strong>Tạo bởi:</strong> {{ props.batch.created_by?.name || 'N/A' }}</p>
                                <p class="ml-4 text-gray-600" v-if="props.batch.created_by?.email">Email: {{
                                    props.batch.created_by.email }}</p>
                                <p><strong>Ngày tạo:</strong> {{ formatDateTime(props.batch.created_at) }}</p>
                                <p><strong>Cập nhật bởi:</strong> {{ props.batch.updated_by?.name || 'N/A' }}</p>
                                <p class="ml-4 text-gray-600" v-if="props.batch.updated_by?.email">Email: {{
                                    props.batch.updated_by.email }}</p>
                                <p><strong>Ngày cập nhật:</strong> {{ formatDateTime(props.batch.updated_at) }}</p>
                            </div>
                        </div>


                        <div v-if="props.batch.notes"
                            class="bg-blue-50 p-6 rounded-lg shadow-sm mb-8 mt-4 border border-blue-200">
                            <p class="text-blue-800"><strong>Ghi chú:</strong> {{ props.batch.notes }}</p>
                        </div>

                        <h3 class="text-xl font-bold text-gray-800 mb-4">Thông tin sản phẩm trong lô</h3>

                        <div class="bg-white p-6 rounded-lg shadow-sm overflow-x-auto border border-gray-200">
                            <table class="min-w-full table-auto border-collapse">
                                <thead class="bg-gray-100 text-gray-700 text-xs uppercase">
                                    <tr>
                                        <th class="border border-gray-200 px-4 py-3 text-left">Tên sản phẩm</th>
                                        <th class="border border-gray-200 px-4 py-3 text-left">Mã SKU</th>
                                        <th class="border border-gray-200 px-4 py-3 text-left">Ngày SX</th>
                                        <th class="border border-gray-200 px-4 py-3 text-left">Hạn SD</th>
                                        <th class="border border-gray-200 px-4 py-3 text-left">Đơn vị</th>
                                        <th class="border border-gray-200 px-4 py-3 text-left">Giá nhập</th>
                                        <th class="border border-gray-200 px-4 py-3 text-left">SL đặt (Batch)</th>
                                        <th class="border border-gray-200 px-4 py-3 text-left">SL đã nhận</th>
                                        <th class="border border-gray-200 px-4 py-3 text-left">SL hiện tại</th>
                                        <th class="border border-gray-200 px-4 py-3 text-left">Trạng thái tồn kho</th>
                                        <th class="border border-gray-200 px-4 py-3 text-left">Tổng tiền SP</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="props.batch.batch_items.length > 0">
                                        <tr v-for="item in props.batch.batch_items" :key="item.id"
                                            class="text-xs text-gray-800 hover:bg-gray-50 transition-colors duration-150">
                                            <td class="border border-gray-200 px-4 py-3">{{ item.product?.name }}</td>
                                            <td class="border border-gray-200 px-4 py-3">{{ item.product?.sku || 'N/A'
                                                }}</td>
                                            <td class="border border-gray-200 px-4 py-3">{{
                                                formatDateOnly(item.manufacturing_date) }}</td>
                                            <td class="border border-gray-200 px-4 py-3">
                                                <span :class="{
                                                    'text-red-600 font-medium': item.expiry_date && new Date(item.expiry_date) < new Date(),
                                                    'text-orange-500 font-medium': item.expiry_date && new Date(item.expiry_date) >= new Date() && new Date(item.expiry_date) <= new Date(new Date().setMonth(new Date().getMonth() + 1)),
                                                }">
                                                    {{ formatDateOnly(item.expiry_date) }}
                                                </span>
                                            </td>
                                            <td class="border border-gray-200 px-4 py-3">{{ item.product?.unit?.name ||
                                                'N/A' }}</td>
                                            <td class="border border-gray-200 px-4 py-3">
                                                {{ formatCurrency(item.purchase_price) }}
                                            </td>
                                            <td class="border border-gray-200 px-4 py-3">{{ item.ordered_quantity }}
                                            </td>
                                            <td class="border border-gray-200 px-4 py-3">{{ item.received_quantity }}
                                            </td>
                                            <td class="border border-gray-200 px-4 py-3">{{ item.current_quantity }}
                                            </td>
                                            <td class="border border-gray-200 px-4 py-3">
                                                <span :class="{
                                                    'text-green-600 font-medium': item.inventory_status === 'active',
                                                    'text-yellow-600 font-medium': item.inventory_status === 'low_stock',
                                                    'text-red-600 font-medium': item.inventory_status === 'out_of_stock' || item.inventory_status === 'expired',
                                                }">
                                                    {{ getStatusDisplayName(item.inventory_status) }}
                                                </span>
                                            </td>
                                            <td class="border border-gray-200 px-4 py-3">
                                                {{ formatCurrency(item.total_amount) }}
                                            </td>

                                        </tr>
                                    </template>
                                    <template v-else>
                                        <tr>
                                            <td colspan="13"
                                                class="border border-gray-200 px-4 py-3 text-center text-gray-500 italic">
                                                Lô hàng này không có sản phẩm nào được liên kết.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>