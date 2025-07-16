<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{ batch: any }>();

function formatCurrency(value: number | null): string {
    if (value === null || isNaN(value)) return 'N/A';
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
}

function getStatusDisplayName(status: string): string {
    const map: Record<string, string> = {
        active: 'Còn hàng', low_stock: 'Sắp hết hàng', out_of_stock: 'Hết hàng', expired: 'Hết hạn',
        unpaid: 'Chưa thanh toán', partially_paid: 'Đã thanh toán một phần', paid: 'Đã thanh toán',
        pending: 'Đang chờ xử lý', partially_received: 'Đã nhận một phần', completed: 'Đã hoàn thành',
        canceled: 'Đã hủy',
    };
    return map[status] || 'Không xác định';
}

function formatDateTime(date: string | null): string {
    if (!date) return 'N/A';
    // Format to "DD/MM/YYYY - HH:MM"
    return new Intl.DateTimeFormat('vi-VN', {
        year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false,
    }).format(new Date(date)).replace(',', ' -');
}

function formatDateOnly(date: string | null): string {
    if (!date) return 'N/A';
    return new Intl.DateTimeFormat('vi-VN', {
        year: 'numeric', month: '2-digit', day: '2-digit'
    }).format(new Date(date));
}

function goBack() {
    router.visit('/admin/batches');
}
function goReturn() {
    router.visit(`/admin/purchaseReturn/create?batch_id=${props.batch.id}`);
}
function printOrder() {
    window.print();
}
</script>

<template>

    <Head title="Chi tiết Phiếu nhập hàng" />
    <AppLayout>
        <div class="bg-gray-50 min-h-screen p-6 no-print">
            <div class="mx-auto max-w-7xl space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button @click="goBack"
                            class="h-9 w-9 rounded border border-gray-300 bg-white text-gray-700 hover:border-gray-400">
                            ←
                        </button>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800">{{ props.batch.batch_number }}</h1>
                            <p class="text-sm text-gray-500">Phiếu nhập hàng - {{
                                formatDateTime(props.batch.received_date) }}</p>
                        </div>
                        <span v-if="props.batch.payment_status === 'paid'"
                            class="ml-4 inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                            ✅ Đã thanh toán
                        </span>
                        <span v-else-if="props.batch.payment_status === 'partially_paid'"
                            class="ml-4 inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-700">
                            🟡 Đã thanh toán một phần
                        </span>
                        <span v-else
                            class="ml-4 inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700">
                            ⚠ Chưa thanh toán
                        </span>
                    </div>
                    <div class="flex items-center gap-2 pr-2">
                        <button
                            v-if="props.batch.receipt_status === 'completed' || props.batch.receipt_status === 'partially_received'"
                            @click="goReturn"
                            class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-800 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:border-blue-300 hover:shadow-md hover:scale-105 transition-all duration-200 ease-in-out"
                            title="Tạo đơn hoàn trả">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h3m-3 0v3m0-3v-3" />
                            </svg>
                            <span>Hoàn trả</span>
                        </button>
                        <button @click="printOrder"
                            class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-800 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 hover:border-gray-300 transition"
                            title="In đơn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 9V4a2 2 0 012-2h8a2 2 0 012 2v5M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-6 0h4" />
                            </svg>
                            <span>In đơn</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-4">
                        <div class="flex items-center gap-2 text-green-600 font-medium text-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Đã nhập kho
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Sản phẩm</th>
                                        <th class="px-4 py-3 text-center">Số lượng</th>
                                        <th class="px-4 py-3 text-center">Đơn giá</th>
                                        <th class="px-4 py-3 text-right">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in props.batch.batch_items" :key="item.id"
                                        class="border-b bg-white hover:bg-gray-50">
                                        <td class="px-4 py-3 flex items-center gap-3">
                                            <img :src="item.product?.image || '/placeholder.jpg'"
                                                class="h-10 w-10 rounded object-cover" />
                                            <div>
                                                <div class="font-medium text-gray-900">{{ item.product?.name }}</div>
                                                <div class="text-xs text-gray-500">SKU: {{ item.product?.sku }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            {{ item.received_quantity }}
                                            <div v-if="item.ordered_quantity && item.received_quantity !== item.ordered_quantity"
                                                class="text-xs text-red-600 font-semibold">
                                                {{ item.ordered_quantity - item.received_quantity }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">{{ formatCurrency(item.purchase_price) }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-800">{{
                                            formatCurrency(item.total_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="text-sm font-semibold mb-2">
                                <template v-if="props.batch.payment_status === 'paid'">
                                    <span class="text-green-600">✅ Đã thanh toán</span>
                                </template>
                                <template v-else-if="props.batch.payment_status === 'partially_paid'">
                                    <span class="text-yellow-600">🟡 Đã thanh toán một phần</span>
                                </template>
                                <template v-else>
                                    <span class="text-red-600">⚠ Chưa thanh toán</span>
                                </template>
                            </div>

                            <div class="text-sm space-y-1 text-gray-700">
                                <p>
                                    <strong>Tổng tiền:</strong>
                                    {{ formatCurrency(props.batch.total_amount) }}
                                    <span v-if="props.batch.total_quantity" class="text-gray-500">({{
                                        props.batch.total_quantity }} sản phẩm)</span>
                                </p>
                                <p>
                                    <strong>Giảm giá:</strong>
                                    {{ props.batch.discount_percentage ? props.batch.discount_percentage + '%' : '0%' }}
                                    <span v-if="props.batch.discount_amount">({{
                                        formatCurrency(props.batch.discount_amount) }})</span>
                                </p>
                                <p>
                                    <strong>Chi phí nhập hàng:</strong>
                                    {{ props.batch.shipping_fee !== null ? formatCurrency(props.batch.shipping_fee) :
                                        '0₫' }}
                                </p>
                                <p class="font-medium">
                                    <strong>Tiền cần trả NCC:</strong>
                                    {{ formatCurrency(props.batch.total_amount_after_discount) }}
                                </p>

                                <template
                                    v-if="props.batch.payment_status === 'paid' || props.batch.payment_status === 'partially_paid'">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 mt-3 font-medium text-sm">
                                        <div class="col-span-1">
                                            <strong>Tiền cần trả NCC:</strong><br />
                                            {{ formatCurrency(props.batch.total_amount_after_discount) }}
                                        </div>
                                        <div class="col-span-1">
                                            <strong>Đã trả:</strong><br />
                                            {{ formatCurrency(props.batch.paid_amount) }}
                                        </div>
                                        <div class="col-span-1">
                                            <strong>Còn phải trả:</strong><br />
                                            {{ formatCurrency(props.batch.total_amount_after_discount -
                                                props.batch.paid_amount) }}
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div v-if="props.batch.payment_status === 'unpaid'" class="mt-3">
                                <button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 text-sm">Xác
                                    nhận thanh toán</button>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <h2 class="text-base font-semibold mb-2">Lịch sử phiếu nhập hàng</h2>
                            <div class="space-y-1 text-sm">
                                <div class="flex items-start gap-2">
                                    <span class="text-blue-500 mt-1">●</span>
                                    <p class="text-gray-800">23:01 - {{ props.batch.created_by?.name || 'Người dùng' }}
                                        xác nhận nhập kho</p>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="text-blue-500 mt-1">●</span>
                                    <p class="text-gray-800">23:01 - {{ props.batch.created_by?.name || 'Người dùng' }}
                                        tạo phiếu nhập hàng</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-4 space-y-1.5 text-sm">
                            <h2 class="text-base font-semibold mb-2">Nhà cung cấp</h2>
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                                    👤
                                </div>
                                <div>
                                    <p class="font-medium text-blue-700">{{ props.batch.supplier?.name || 'N/A' }}</p>
                                    <p class="text-gray-600">{{ props.batch.supplier?.phone || 'N/A' }}</p>
                                </div>
                            </div>
                            <p class="text-gray-600">{{ props.batch.supplier?.address || 'N/A' }}</p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-4 text-sm">
                            <h2 class="text-base font-semibold mb-2">Chi nhánh nhập</h2>
                            <p class="text-gray-700">Cửa hàng chính</p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-4 text-sm space-y-1">
                            <h2 class="text-base font-semibold mb-2">Thông tin bổ sung</h2>
                            <p><strong>Mã phiếu:</strong> <span class="text-blue-600 underline">{{
                                props.batch.batch_number }}</span></p>
                            <p><strong>Nhân viên phụ trách:</strong> {{ props.batch.created_by?.name || 'N/A' }}</p>
                            <p><strong>Email:</strong> {{ props.batch.created_by?.email || 'N/A' }}</p>
                            <p><strong>Ngày nhập dự kiến:</strong> {{ formatDateTime(props.batch.received_date) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="print-only">
            <div class="receipt-container">
                <div class="receipt-header">
                    <h1 class="receipt-title">PHIẾU NHẬP HÀNG</h1>
                </div>

                <div class="receipt-details">
                    <p><strong>Mã phiếu:</strong> {{ props.batch.batch_number }}</p>
                    <p><strong>Ngày giờ:</strong> {{ formatDateTime(props.batch.received_date) }}</p>
                    <p><strong>Nhà cung cấp:</strong> {{ props.batch.supplier?.name || 'N/A' }}</p>
                    <p><strong>Người tạo phiếu:</strong> {{ props.batch.created_by?.name || 'N/A' }}</p>
                    <p><strong>Trạng thái TT:</strong> {{ getStatusDisplayName(props.batch.payment_status) }}</p>
                </div>

                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">STT</th>
                            <th style="width: 45%; text-align: left;">Tên sản phẩm</th>
                            <th style="width: 15%;">SL</th>
                            <th style="width: 20%;">Đơn giá</th>
                            <th style="width: 15%; text-align: right;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in props.batch.batch_items" :key="item.id">
                            <td style="text-align: center;">{{ index + 1 }}</td>
                            <td style="text-align: left;">{{ item.product?.name }}</td>
                            <td style="text-align: center;">{{ item.received_quantity }}</td>
                            <td style="text-align: right;">{{ formatCurrency(item.purchase_price) }}</td>
                            <td style="text-align: right;">{{ formatCurrency(item.total_amount) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="receipt-summary">
                    <p><strong>Tạm tính:</strong> <span>{{ formatCurrency(props.batch.total_amount) }}</span></p>
                    <p><strong>Giảm giá:</strong> <span>{{ formatCurrency(props.batch.discount_amount || 0) }}</span>
                    </p>
                    <p><strong>Phí vận chuyển:</strong> <span>{{ formatCurrency(props.batch.shipping_fee || 0) }}</span>
                    </p>
                    <p class="total-line"><strong>Tổng tiền cần trả:</strong> <span>{{
                        formatCurrency(props.batch.total_amount_after_discount) }}</span></p>
                    <p><strong>Đã trả:</strong> <span>{{ formatCurrency(props.batch.paid_amount) }}</span></p>
                    <p><strong>Còn phải trả:</strong> <span>{{ formatCurrency(props.batch.total_amount_after_discount -
                        props.batch.paid_amount) }}</span></p>
                </div>

                <div class="receipt-footer">
                    <p>Cảm ơn quý khách!</p>
                    <p>(Vui lòng kiểm tra kỹ trước khi rời đi)</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Base styles for screen (hide print-only content) */
.print-only {
    display: none;
}

/* Styles to hide main content when printing */
@media print {

    /* Đảm bảo chỉ nội dung in hiển thị */
    body {
        margin: 0;
        padding: 0;
        background: white;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        /* Font dễ đọc */
        -webkit-print-color-adjust: exact;
        /* Đảm bảo in màu chính xác */
    }

    body>* {
        display: none !important;
        /* Ẩn tất cả nội dung trên trang */
    }

    .no-print {
        display: none !important;
        /* Đảm bảo ẩn các phần tử có class no-print */
    }

    /* Hiển thị chỉ phần phiếu in */
    .print-only {
        display: block !important;
        visibility: visible !important;
        width: 100%;
        /* Chiếm toàn bộ chiều rộng trang in */
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        /* Đảm bảo padding không làm tràn width */
    }

    .receipt-container {
        width: 78mm;
        /* Chiều rộng giấy in nhiệt phổ biến */
        max-width: 78mm;
        /* Đảm bảo không vượt quá */
        margin: 0 auto;
        /* Căn giữa phiếu trên trang */
        padding: 8mm 5mm;
        /* Padding trên dưới nhiều hơn, hai bên vừa phải */
        background: white;
        color: #000;
        /* Đảm bảo màu chữ đen */
        font-size: 10pt;
        /* Kích thước chữ cơ bản */
        line-height: 1.4;
    }

    .receipt-header {
        text-align: center;
        margin-bottom: 18px;
        /* Tăng khoảng cách dưới header */
    }

    .receipt-logo {
        max-width: 80px;
        /* Tăng kích thước logo một chút cho dễ nhìn */
        height: auto;
        margin-bottom: 10px;
        /* Khoảng cách giữa logo và tiêu đề */
    }

    .receipt-title {
        font-size: 18pt;
        /* Kích thước tiêu đề lớn hơn */
        font-weight: bold;
        margin: 0;
        text-transform: uppercase;
    }

    .receipt-details {
        margin-bottom: 18px;
        /* Tăng khoảng cách dưới thông tin chi tiết chung */
        border-bottom: 1px dashed #aaa;
        /* Đường kẻ phân cách */
        padding-bottom: 12px;
        /* Tăng padding dưới */
    }

    .receipt-details p {
        margin-bottom: 5px;
        /* Tăng khoảng cách giữa các dòng thông tin */
    }

    .receipt-details strong {
        display: inline-block;
        width: 120px;
        /* Điều chỉnh độ rộng để căn chỉnh các nhãn */
    }

    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        /* Tăng khoảng cách trên bảng */
        margin-bottom: 15px;
        /* Tăng khoảng cách dưới bảng */
        font-size: 9pt;
        /* Kích thước chữ cho bảng */
    }

    .receipt-table th,
    .receipt-table td {
        border-bottom: 1px dashed #ddd;
        /* Đường kẻ mờ hơn */
        padding: 6px 0;
        /* Tăng padding để giãn cách các dòng trong bảng */
        vertical-align: top;
        /* Căn trên cho nội dung dài */
    }

    .receipt-table th {
        font-weight: bold;
        text-align: center;
        /* Căn giữa tiêu đề cột */
    }

    /* Điều chỉnh căn chỉnh cụ thể cho các cột trong bảng */
    .receipt-table th:nth-child(1),
    /* STT */
    .receipt-table td:nth-child(1) {
        text-align: center;
    }

    .receipt-table th:nth-child(2),
    /* Tên sản phẩm */
    .receipt-table td:nth-child(2) {
        text-align: left;
        padding-left: 2px;
        /* Thêm padding trái cho tên sản phẩm */
    }

    .receipt-table th:nth-child(3),
    /* SL */
    .receipt-table td:nth-child(3) {
        text-align: center;
    }

    .receipt-table th:nth-child(4),
    /* Đơn giá */
    .receipt-table td:nth-child(4) {
        text-align: right;
    }

    .receipt-table th:nth-child(5),
    /* Thành tiền */
    .receipt-table td:nth-child(5) {
        text-align: right;
        padding-right: 2px;
        /* Thêm padding phải cho thành tiền */
    }


    .receipt-summary {
        text-align: right;
        margin-top: 20px;
        /* Tăng khoảng cách trên phần tóm tắt */
        font-size: 10pt;
        border-top: 1px dashed #aaa;
        /* Đường kẻ phân cách */
        padding-top: 12px;
        /* Tăng padding trên */
    }

    .receipt-summary p {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        /* Tăng khoảng cách giữa các dòng tóm tắt */
    }

    .receipt-summary strong {
        flex-shrink: 0;
        padding-right: 20px;
        /* Khoảng cách giữa nhãn và giá trị */
    }

    .receipt-summary .total-line {
        font-weight: bold;
        font-size: 11pt;
        /* Tổng tiền nổi bật hơn */
        border-top: 1px dashed #aaa;
        padding-top: 8px;
        /* Tăng padding trên */
        margin-top: 10px;
        /* Tăng khoảng cách trên */
    }

    .receipt-footer {
        text-align: center;
        margin-top: 25px;
        /* Khoảng cách lớn hơn trước footer */
        border-top: 1px dashed #aaa;
        /* Đường kẻ phân cách */
        padding-top: 12px;
        /* Tăng padding trên */
        font-size: 9pt;
        /* Kích thước chữ cho footer */
        line-height: 1.3;
    }
}
</style>
