<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import { PencilLine, Printer } from 'lucide-vue-next';
import { ref } from 'vue';

// Define the PurchaseReturn type, now with ref
interface PurchaseReturn {
    return_number: string
    id: number
    purchase_order_code: string
    supplier_name: string
    reason: string | null
    return_date: string
    status: 'pending' | 'approved' | 'completed' | 'rejected'
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

const props = defineProps<{
    purchaseReturn: PurchaseReturn
}>()

// Use ref to make the purchaseReturn object reactive
const currentPurchaseReturn = ref<PurchaseReturn>(props.purchaseReturn);

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
            return 'text-red-500 bg-red-100'
        case 'approved':
            return 'text-blue-500 bg-blue-100'
        case 'pending':
            return 'text-amber-500 bg-amber-100'
        case 'completed':
            return 'text-green-500 bg-green-100'
        default:
            return 'text-gray-500 bg-gray-100'
    }
}

const translateStatus = (status: string) => {
    switch (status.toLowerCase()) {
        case 'pending':
            return 'Chờ duyệt'
        case 'approved':
            return 'Đã duyệt'
        case 'completed':
            return 'Hoàn thành'
        case 'rejected':
            return 'Từ chối'
        default:
            return status
    }
}

function goToIndex() {
    router.visit('/admin/purchaseReturn')
}

function goToEdit(id: number) {
    router.visit(`/admin/purchaseReturn/${id}/edit`)
}

function printReturn() {
    window.print();
}

// New function to handle the status change
function completePurchaseReturn() {
    if (confirm('Bạn có chắc chắn muốn gửi yêu cầu và hoàn thành phiếu trả hàng này không?')) {
        // Now we make the actual API call
        router.patch(route('admin.purchaseReturn.complete', currentPurchaseReturn.value.id), {}, {
            onSuccess: () => {
                // Update the status on the frontend after successful API call
                currentPurchaseReturn.value.status = 'completed';
                alert('Phiếu trả hàng đã được câp nhật thành công.');
            },
            onError: (errors) => {
                console.error('Lỗi khi cập nhật trạng thái:', errors);
                alert('Có lỗi xảy ra khi cập nhật trạng thái phiếu trả hàng.');
            }
        });
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý phiếu trả hàng',
        href: '/admin/purchaseReturn',
    },
    {
        title: `Phiếu ${currentPurchaseReturn.value.return_number}`,
        href: '#',
    },
]
</script>

<template>
    <Head title="Chi tiết phiếu trả hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 rounded-2xl p-8 bg-gray-50 min-h-screen no-print">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">
                    Chi tiết phiếu trả hàng
                    <span class="text-gray-500 font-medium">{{ currentPurchaseReturn.return_number }}</span>
                </h1>
                <div class="flex items-center gap-2">
                    <button
                        class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-100 transition"
                        title="In đơn" @click="printReturn">
                        <Printer class="w-4 h-4" />
                        <span>In phiếu</span>
                    </button>
                    <button v-if="currentPurchaseReturn.status === 'pending'"
                        @click="goToEdit(currentPurchaseReturn.id)"
                        class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-100 transition">
                        <PencilLine class="h-4 w-4" />
                        <span>Sửa đơn</span>
                    </button>
                    <button @click="goToIndex"
                        class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-100 transition">
                        <span>Quay lại</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4">Thông tin chung</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                            <div>
                                <p class="mb-1"><strong>Mã phiếu trả hàng:</strong> <span class="font-medium">{{
                                    currentPurchaseReturn.return_number }}</span></p>
                                <p class="mb-1"><strong>Mã đơn đặt hàng:</strong> <span class="font-medium">{{
                                    currentPurchaseReturn.purchase_order_code }}</span></p>
                                <p class="mb-1"><strong>Nhà cung cấp:</strong> <span class="font-medium">{{
                                    currentPurchaseReturn.supplier_name }}</span></p>
                                <p class="mb-1"><strong>Lý do trả hàng:</strong> <span class="font-medium">{{
                                    currentPurchaseReturn.reason || 'Không có' }}</span></p>
                            </div>
                            <div>
                                <p class="mb-1"><strong>Ngày trả hàng:</strong> <span class="font-medium">{{
                                    formatDate(currentPurchaseReturn.return_date) }}</span></p>
                                <p class="mb-1"><strong>Trạng thái:</strong>
                                    <span class="inline-block font-medium px-3 py-1 rounded-full text-xs"
                                        :class="statusTextClass(currentPurchaseReturn.status)">
                                        {{ translateStatus(currentPurchaseReturn.status) }}
                                    </span>
                                </p>
                                <p class="mb-1"><strong>Người tạo phiếu:</strong> <span class="font-medium">{{
                                    currentPurchaseReturn.created_by }}</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4">Danh sách sản phẩm</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600 uppercase font-semibold">
                                    <tr class="border-b">
                                        <th class="px-4 py-3 text-left">Tên sản phẩm</th>
                                        <th class="px-4 py-3 text-left">Mã lô</th>
                                        <th class="px-4 py-3 text-left">Mã SKU</th>
                                        <th class="px-4 py-3 text-left">NSX</th>
                                        <th class="px-4 py-3 text-left">HSD</th>
                                        <th class="px-4 py-3 text-center">SL</th>
                                        <th class="px-4 py-3 text-right">Đơn giá</th>
                                        <th class="px-4 py-3 text-right">Tổng tiền</th>
                                        <th class="px-4 py-3 text-left">Lý do</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="item in currentPurchaseReturn.items" :key="item.product_sku"
                                        class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 font-medium">{{ item.product_name }}</td>
                                        <td class="px-4 py-3">{{ item.batch_number }}</td>
                                        <td class="px-4 py-3">{{ item.product_sku }}</td>
                                        <td class="px-4 py-3">{{ item.manufacturing_date ?
                                            formatDate(item.manufacturing_date) : '—' }}</td>
                                        <td class="px-4 py-3">{{ item.expiry_date ? formatDate(item.expiry_date) : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">{{ item.quantity_returned }}</td>
                                        <td class="px-4 py-3 text-right">{{ formatCurrency(item.unit_cost) }}</td>
                                        <td class="px-4 py-3 text-right">{{ formatCurrency(item.subtotal) }}</td>
                                        <td class="px-4 py-3">{{ item.reason || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 space-y-6">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4">Tổng kết</h2>
                        <div class="space-y-2 text-sm">
                            <p class="flex justify-between items-center text-gray-600">
                                <span>Tổng số sản phẩm trả:</span>
                                <span class="font-medium text-gray-800">{{ currentPurchaseReturn.total_items_returned
                                    }}</span>
                            </p>
                            <p class="flex justify-between items-center font-bold text-lg text-blue-600 border-t pt-4 mt-4">
                                <span>Tổng giá trị trả lại:</span>
                                <span>{{ formatCurrency(currentPurchaseReturn.total_value_returned) }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6" v-if="currentPurchaseReturn.status === 'pending'">
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4">Hành động</h2>
                        <div class="flex flex-col gap-3">
                            <button @click="completePurchaseReturn"
                                class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition">
                                Gửi yêu cầu và hoàn thành
                            </button>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="print-only">
            <div class="receipt-container">
                <div class="receipt-header">
                    <h1 class="receipt-title">PHIẾU TRẢ HÀNG</h1>
                </div>
                <div class="receipt-details">
                    <p><strong>Mã phiếu trả:</strong> {{ currentPurchaseReturn.return_number }}</p>
                    <p><strong>Mã đơn ĐH:</strong> {{ currentPurchaseReturn.purchase_order_code }}</p>
                    <p><strong>Ngày giờ trả:</strong> {{ formatDateTimeForPrint(currentPurchaseReturn.return_date) }}</p>
                    <p><strong>Nhà cung cấp:</strong> {{ currentPurchaseReturn.supplier_name }}</p>
                    <p><strong>Người tạo phiếu:</strong> {{ currentPurchaseReturn.created_by }}</p>
                    <p><strong>Trạng thái:</strong> {{ translateStatus(currentPurchaseReturn.status) }}</p>
                    <p><strong>Lý do trả:</strong> {{ currentPurchaseReturn.reason || 'Không có' }}</p>
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
                        <tr v-for="(item, index) in currentPurchaseReturn.items" :key="item.product_sku">
                            <td style="text-align: center;">{{ index + 1 }}</td>
                            <td style="text-align: left;">{{ item.product_name }}</td>
                            <td style="text-align: center;">{{ item.quantity_returned }}</td>
                            <td style="text-align: right;">{{ formatCurrency(item.unit_cost) }}</td>
                            <td style="text-align: right;">{{ formatCurrency(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="receipt-summary">
                    <p><strong>Tổng SL trả:</strong> <span>{{ currentPurchaseReturn.total_items_returned }}</span></p>
                    <p class="total-line"><strong>Tổng giá trị trả:</strong> <span>{{
                        formatCurrency(currentPurchaseReturn.total_value_returned) }}</span></p>
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
}

.print-only {
    display: none;
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

    body>*:not(.print-only) {
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

    .receipt-table th:nth-child(1),
    .receipt-table td:nth-child(1) {
        text-align: center;
    }

    .receipt-table th:nth-child(2),
    .receipt-table td:nth-child(2) {
        text-align: left;
        padding-left: 2px;
    }

    .receipt-table th:nth-child(3),
    .receipt-table td:nth-child(3) {
        text-align: center;
    }

    .receipt-table th:nth-child(4),
    .receipt-table td:nth-child(4) {
        text-align: right;
    }

    .receipt-table th:nth-child(5),
    .receipt-table td:nth-child(5) {
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
