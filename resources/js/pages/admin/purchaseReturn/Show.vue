<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import { PencilLine } from 'lucide-vue-next';

defineProps<{
    purchaseReturn: {
        return_number: string
        id: number; // Add ID here as you use it in goToEdit(purchaseReturn.id)
        purchase_order_code: string
        supplier_name: string
        reason: string | null
        return_date: string
        status: string
        created_by: string
        total_items_returned: number
        total_value_returned: number
        items: {
            product_name: string
            batch_number: string
            product_sku: string
            manufacturing_date: string | null
            expiry_date: string | null
            quantity_returned: number
            unit_cost: number
            subtotal: number
            reason: string | null
        }[]
    }
}>()

const formatDate = (dateString: string) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('vi-VN')
}

const formatDateTimeForPrint = (dateString: string | null) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('vi-VN', {
        year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false,
    }).format(date).replace(',', ' -');
}

const formatCurrency = (value: number | null): string => {
    if (value === null || isNaN(value)) return '0 đ';
    return value.toLocaleString('vi-VN') + ' đ';
}

const statusTextClass = (status: string) => {
    switch (status.toLowerCase()) {
        case 'rejected':
            return 'text-red-500'
        case 'approved':
            return 'text-blue-500'
        case 'pending':
            return 'text-amber-500'
        case 'completed':
            return 'text-green-500'
        default:
            return 'text-gray-500'
    }
}

const translateStatus = (status: string) => {
    switch (status.toLowerCase()) {
        case 'pending':
            return 'Chờ duyệt'
        case 'approved':
            return 'Đã duyệt'
        case 'completed':
            return 'Hoàn tất'
        case 'rejected':
            return 'Từ chối'
        default:
            return status
    }
}

function goToIndex() {
    router.visit('/admin/purchaseReturn')
}
function goToEdit(id: number) { // Make sure ID is number as per your prop definition
    router.visit(`/admin/purchaseReturn/${id}/edit`)
}

function printReturn() {
    window.print();
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý phiếu trả hàng',
        href: '/admin/purchaseReturn',
    },
]
</script>

<template>

    <Head title="Chi tiết phiếu trả hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 rounded-2xl p-8 bg-gray-50 min-h-screen no-print">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 border-l-4 border-blue-500 pl-4">
                        Chi tiết phiếu trả hàng
                    </h1>
                    <div class="flex items-center gap-2 mt-4">
                        <button v-if="purchaseReturn.status.toLowerCase() !== 'completed'"
                            @click="goToEdit(purchaseReturn.id)"
                            class="flex items-center gap-1 text-sm text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-xl transition">
                            <PencilLine class="h-4 w-4" />
                            Sửa đơn
                        </button>
                        <button @click="printReturn"
                            class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-800 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 hover:border-gray-300 transition"
                            title="In đơn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 9V4a2 2 0 012-2h8a2 2 0 012 2v5M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-6 0h4" />
                            </svg>
                            <span>In phiếu</span>
                        </button>
                        <button @click="goToIndex"
                            class="flex items-center gap-1 px-4 py-2 text-sm text-gray-700 border border-gray-300 bg-white hover:bg-gray-100 rounded-lg transition">
                            🔙 Quay lại danh sách
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-sm space-y-2">
                        <p><strong>Mã phiếu trả hàng:</strong> {{ purchaseReturn.return_number }}</p>
                        <p><strong>Mã đơn đặt hàng:</strong> {{ purchaseReturn.purchase_order_code }}</p>
                        <p><strong>Nhà cung cấp:</strong> {{ purchaseReturn.supplier_name }}</p>
                        <p><strong>Lý do trả hàng:</strong> {{ purchaseReturn.reason || 'Không có' }}</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-sm space-y-2">
                        <p><strong>Ngày trả hàng:</strong> {{ formatDate(purchaseReturn.return_date) }}</p>
                        <p>
                            <strong>Trạng thái:</strong>
                            <span class="inline-block font-medium text-base px-3 py-1 rounded"
                                :class="statusTextClass(purchaseReturn.status)">
                                {{ translateStatus(purchaseReturn.status) }}
                            </span>
                        </p>
                        <p><strong>Người tạo phiếu:</strong> {{ purchaseReturn.created_by }}</p>
                    </div>
                </div>

                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Danh sách sản phẩm</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border-separate border-spacing-y-3">
                            <thead class="bg-blue-50 text-gray-700 uppercase font-semibold">
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
                                <tr v-for="item in purchaseReturn.items" :key="item.product_sku"
                                    class="bg-white border border-gray-200 rounded-lg shadow-sm">
                                    <td class="px-6 py-4 rounded-l-lg">{{ item.product_name }}</td>
                                    <td class="px-6 py-4">{{ item.batch_number }}</td>
                                    <td class="px-6 py-4">{{ item.product_sku }}</td>
                                    <td class="px-6 py-4">{{ item.manufacturing_date ?
                                        formatDate(item.manufacturing_date) : '—' }}</td>
                                    <td class="px-6 py-4">{{ item.expiry_date ? formatDate(item.expiry_date) : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">{{ item.quantity_returned }}</td>
                                    <td class="px-6 py-4 text-right">{{ formatCurrency(item.unit_cost) }}</td>
                                    <td class="px-6 py-4 text-right">{{ formatCurrency(item.subtotal) }}</td>
                                    <td class="px-6 py-4 rounded-r-lg">{{ item.reason || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row justify-between text-gray-800 font-semibold mb-6">
                    <p>Tổng số sản phẩm trả: {{ purchaseReturn.total_items_returned }}</p>
                    <p>Tổng giá trị trả lại: {{ formatCurrency(purchaseReturn.total_value_returned) }}</p>
                </div>

                <div class="flex gap-4">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        Gửi yêu cầu
                    </button>
                    <button class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        Xoá
                    </button>
                </div>
            </div>
        </div>

        <div class="print-only">
            <div class="receipt-container">
                <div class="receipt-header">
                    <h1 class="receipt-title">PHIẾU TRẢ HÀNG</h1>
                </div>
                <div class="receipt-details">
                    <p><strong>Mã phiếu trả:</strong> {{ purchaseReturn.return_number }}</p>
                    <p><strong>Mã đơn ĐH:</strong> {{ purchaseReturn.purchase_order_code }}</p>
                    <p><strong>Ngày giờ trả:</strong> {{ formatDateTimeForPrint(purchaseReturn.return_date) }}</p>
                    <p><strong>Nhà cung cấp:</strong> {{ purchaseReturn.supplier_name }}</p>
                    <p><strong>Người tạo phiếu:</strong> {{ purchaseReturn.created_by }}</p>
                    <p><strong>Trạng thái:</strong> {{ translateStatus(purchaseReturn.status) }}</p>
                    <p><strong>Lý do trả:</strong> {{ purchaseReturn.reason || 'Không có' }}</p>
                </div>

                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">STT</th>
                            <th style="width: 40%; text-align: left;">Tên SP</th>
                            <th style="width: 15%;">SL</th>
                            <th style="width: 20%;">Đơn giá</th>
                            <th style="width: 20%; text-align: right;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in purchaseReturn.items" :key="item.product_sku">
                            <td style="text-align: center;">{{ index + 1 }}</td>
                            <td style="text-align: left;">{{ item.product_name }}</td>
                            <td style="text-align: center;">{{ item.quantity_returned }}</td>
                            <td style="text-align: right;">{{ formatCurrency(item.unit_cost) }}</td>
                            <td style="text-align: right;">{{ formatCurrency(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="receipt-summary">
                    <p><strong>Tổng SL trả:</strong> <span>{{ purchaseReturn.total_items_returned }}</span></p>
                    <p class="total-line"><strong>Tổng giá trị trả:</strong> <span>{{
                        formatCurrency(purchaseReturn.total_value_returned) }}</span></p>
                </div>

                <div class="receipt-footer">
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Base styles for screen (hide print-only content) */
.no-print {
    display: block;
    /* Mặc định hiển thị trên màn hình */
}

.print-only {
    display: none;
    /* Mặc định ẩn trên màn hình */
}

/* Styles to hide main content when printing and show print-only content */
@media print {
    body {
        margin: 0;
        padding: 0;
        background: white;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        -webkit-print-color-adjust: exact;
    }

    /* Ẩn tất cả nội dung không phải là print-only */
    body>*:not(.print-only) {
        display: none !important;
    }

    /* Ẩn cụ thể phần breadcrumbs nếu cần (điều chỉnh selector nếu nó không phải là con trực tiếp của body) */
    /* Giả định breadcrumbs nằm trong một nav bên trong AppLayout. Bạn có thể cần điều chỉnh selector này */
    /* Dựa trên cấu trúc HTML thực tế của AppLayout của bạn để đảm bảo nó được ẩn */
    .app-layout-main-content nav {
        /* Đây là một selector giả định, bạn cần kiểm tra HTML của AppLayout.vue */
        display: none !important;
    }

    .no-print {
        display: none !important;
    }

    .print-only {
        display: block !important;
        visibility: visible !important;
        width: 100%;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .receipt-container {
        width: 78mm;
        /* Chiều rộng giấy in nhiệt phổ biến */
        max-width: 78mm;
        margin: 0 auto;
        padding: 8mm 5mm;
        background: white;
        color: #000;
        font-size: 10pt;
        line-height: 1.4;
    }

    .receipt-header {
        text-align: center;
        margin-bottom: 18px;
    }

    .receipt-logo {
        max-width: 80px;
        height: auto;
        margin-bottom: 10px;
    }

    .receipt-title {
        font-size: 18pt;
        font-weight: bold;
        margin: 0;
        text-transform: uppercase;
    }

    .receipt-details {
        margin-bottom: 18px;
        border-bottom: 1px dashed #aaa;
        padding-bottom: 12px;
    }

    .receipt-details p {
        margin-bottom: 5px;
    }

    .receipt-details strong {
        display: inline-block;
        width: 120px;
    }

    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        margin-bottom: 15px;
        font-size: 9pt;
    }

    .receipt-table th,
    .receipt-table td {
        border-bottom: 1px dashed #ddd;
        padding: 6px 0;
        vertical-align: top;
    }

    .receipt-table th {
        font-weight: bold;
        text-align: center;
    }

    /* Column specific alignments for receipt table */
    .receipt-table th:nth-child(1),
    .receipt-table td:nth-child(1) {
        /* STT */
        text-align: center;
    }

    .receipt-table th:nth-child(2),
    .receipt-table td:nth-child(2) {
        /* Tên SP */
        text-align: left;
        padding-left: 2px;
    }

    .receipt-table th:nth-child(3),
    .receipt-table td:nth-child(3) {
        /* SL */
        text-align: center;
    }

    .receipt-table th:nth-child(4),
    .receipt-table td:nth-child(4) {
        /* Đơn giá */
        text-align: right;
    }

    .receipt-table th:nth-child(5),
    .receipt-table td:nth-child(5) {
        /* Thành tiền */
        text-align: right;
        padding-right: 2px;
    }


    .receipt-summary {
        text-align: right;
        margin-top: 20px;
        font-size: 10pt;
        border-top: 1px dashed #aaa;
        padding-top: 12px;
    }

    .receipt-summary p {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .receipt-summary strong {
        flex-shrink: 0;
        padding-right: 20px;
    }

    .receipt-summary .total-line {
        font-weight: bold;
        font-size: 11pt;
        border-top: 1px dashed #aaa;
        padding-top: 8px;
        margin-top: 10px;
    }

    .receipt-footer {
        text-align: center;
        margin-top: 25px;
        border-top: 1px dashed #aaa;
        padding-top: 12px;
        font-size: 9pt;
        line-height: 1.3;
    }
}
</style>
