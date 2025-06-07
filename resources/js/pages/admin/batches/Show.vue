<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{
    batch: {
        id: number;
        batch_number: string;
        product_id: number;
        manufacturing_date: string | null;
        expiry_date: string | null;
        purchase_price: number;
        initial_quantity: number;
        current_quantity: number;
        status: 'active' | 'low_stock' | 'out_of_stock' | 'expired';
        supplier_id: number | null;
        received_date: string | null;
        invoice_number: string | null;
        notes: string | null;
        supplier?: {
            name: string;
        };
        product: {
            name: string;
            image_url?: string;
            purchase_price: number;
        };
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý lô hàng',
        href: '/admin/product-batches',
    },
    {
        title: 'Chi tiết lô hàng',
        href: `/admin/batches/${props.batch.id}`,
    },
];

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(value);
}

function getProductItemStatusDisplayName(status: string): string {
    switch (status) {
        case 'active':
            return 'Còn hàng';
        case 'low_stock':
            return 'Sắp hết hàng';
        case 'out_of_stock':
            return 'Hết hàng';
        case 'expired':
            return 'Hết hạn';
        default:
            return 'Không xác định';
    }
}

function formatDate(dateString: string | null): string {
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
        console.error('Error formatting date:', dateString, e);
        return dateString;
    }
}

function goBack() {
    router.visit('/admin/product-batches');
}
</script>

<template>

    <Head title="Batch Details" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
           
                <div class="container mx-auto p-6">
                        <div
                            class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min bg-white shadow-lg">
                            <div class="container mx-auto p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h2 class="text-2xl font-bold text-gray-800">Thông tin lô hàng - {{
                                        props.batch.batch_number }}</h2>
                                    <button @click="goBack"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm.707-10.293a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L9.414 11H13a1 1 0 100-2H9.414l1.293-1.293z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Quay lại
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                                    <div>
                                        <p class="mb-2"><strong>Mã lô hàng:</strong> {{ props.batch.batch_number }}</p>
                                        <p class="mb-2"><strong>Nhà cung cấp:</strong> {{ props.batch.supplier?.name ||
                                            'N/A' }}
                                        </p>
                                        <p class="mb-2"><strong>Ngày nhận hàng:</strong> {{
                                            formatDate(props.batch.received_date) }}</p>
                                        <p class="mb-2"><strong>Số hóa đơn:</strong> {{ props.batch.invoice_number ||
                                            'N/A' }}
                                        </p>
                                    </div>
                                    <div>
                                        <!-- <p class="mb-2"><strong>Giá nhập:</strong> {{ formatCurrency(props.batch.purchase_price) }}</p> -->
                                        <p class="mb-2"><strong>Số lượng ban đầu:</strong> {{
                                            props.batch.initial_quantity }}
                                        </p>
                                        <p class="mb-2"><strong>Số lượng hiện tại:</strong> {{
                                            props.batch.current_quantity }}
                                        </p>
                                        <p class="mb-2"><strong>Trạng thái:</strong> <span :class="{
                                            'text-green-600 font-medium': props.batch.status === 'active',
                                            'text-yellow-600 font-medium': props.batch.status === 'low_stock',
                                            'text-red-600 font-medium': props.batch.status === 'out_of_stock' || props.batch.status === 'expired',
                                        }">
                                                {{ getProductItemStatusDisplayName(props.batch.status) }}
                                            </span></p>
                                    </div>
                                </div>


                                <div v-if="props.batch.notes" class="bg-gray-50 p-6 rounded-lg shadow-sm mb-8">
                                    <p class="text-gray-700"><strong>Ghi chú:</strong> {{ props.batch.notes }}</p>
                                </div>

                                <h3 class="text-xl font-bold text-gray-800 mb-4">Thông tin sản phẩm trong lô</h3>

                                <div class="bg-white p-6 rounded-lg shadow-sm overflow-x-auto">
                                    <table class="min-w-full table-auto border-collapse border border-gray-200">
                                        <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                                            <tr>
                                                <th class="border border-gray-200 px-4 py-3 text-left">Tên sản phẩm</th>
                                                <th class="border border-gray-200 px-4 py-3 text-left">Hình ảnh</th>
                                                <th class="border border-gray-200 px-4 py-3 text-left">Ngày sản xuất
                                                </th>
                                                <th class="border border-gray-200 px-4 py-3 text-left">Hạn sử dụng</th>
                                                <th class="border border-gray-200 px-4 py-3 text-left">Giá nhập</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="text-sm text-gray-800">
                                                <td class="border border-gray-200 px-4 py-3">{{ props.batch.product.name
                                                    }}</td>
                                                <td class="border border-gray-200 px-4 py-3">
                                                    <img v-if="props.batch.product.image_url"
                                                        :src="props.batch.product.image_url" alt="Product image"
                                                        class="w-20 h-20 object-cover rounded shadow" />
                                                    <span v-else class="text-gray-400 italic">Không có ảnh</span>
                                                </td>
                                                <td class="border border-gray-200 px-4 py-3">{{
                                                    formatDate(props.batch.manufacturing_date)
                                                    }}</td>
                                                <td class="border border-gray-200 px-4 py-3">{{
                                                    formatDate(props.batch.expiry_date) }}</td>
                                                <td class="border border-gray-200 px-4 py-3">{{
                                                    formatCurrency(props.batch.product.purchase_price) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    
    </AppLayout>
</template>

<style scoped>
</style>