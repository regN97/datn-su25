<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Printer } from 'lucide-vue-next';

type BillDetail = {
    id: number;
    p_name: string;
    quantity: number;
    unit_price: number;
    subtotal: number;
};

type Bill = {
    bill_number: string;
    customer: {
        customer_name: string;
    };
    cashier: {
        name: string;
    };
    total_amount: number;
    payment_status: {
        name: string;
    };
    created_at: string;
    details: BillDetail[];
    discount_amount: number;
    payment_method: string;
    customer_paid: number;
    change_due: number;
    note: string;
};

const page = usePage<{ bill: Bill }>();
const bill = page.props.bill;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý hóa đơn',
        href: '/admin/bills',
    },
    {
        title: `Chi tiết Hóa đơn ${bill.bill_number}`,
        href: `/admin/bills/${bill.bill_number}`,
    },
];

function getPaymentMethodName(method: string) {
    switch (method) {
        case 'cash':
            return 'Tiền mặt';
        case 'credit_card':
            return 'Thẻ ngân hàng';
        case 'bank_transfer':
            return 'Chuyển khoản';
        case 'wallet':
            return 'Ví khách hàng';
        default:
            return 'Khác';
    }
}

function formatCurrency(amount: number) {
    const formatted = new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
    return formatted;
}

const subtotal_amount = bill.details.reduce((sum, item) => sum + item.subtotal, 0);

function printInvoice() {
    window.print();
}
</script>

<template>
    <Head :title="`Chi tiết Hóa đơn #${bill.bill_number}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div id="content-to-print" class="p-6 main-container">
            <div id="invoice-content" class="max-w-xl mx-auto bg-white shadow-lg p-8 rounded-lg font-mono text-sm">
                <div class="flex justify-end mb-4 print:hidden">
                    <button @click="printInvoice" class="flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                        <Printer class="w-4 h-4" />
                        <span>In</span>
                    </button>
                </div>
                
                <div class="text-center mb-6">
                    <h1 class="text-xl font-bold">Hóa Đơn Bán Hàng</h1>
                    <p class="mt-1">Cửa hàng bán lẻ G7 Mart</p>
                    <p>Trịnh Văn Bô, Hà Nội</p>
                    <p>Hotline: 4444 6666</p>
                </div>
                
                <hr class="border-t-2 border-dashed border-gray-400 my-4">

                <div class="mb-4">
                    <p>Mã hóa đơn: <span class="font-semibold">{{ bill.bill_number }}</span></p>
                    <p>Ngày giờ: <span class="font-semibold">{{ new Date(bill.created_at).toLocaleString('vi-VN') }}</span></p>
                    <p>Nhân viên: <span class="font-semibold">{{ bill.cashier?.name ?? 'N/A' }}</span></p>
                    <p>Khách hàng: <span class="font-semibold">{{ bill.customer?.customer_name ?? 'Khách lẻ' }}</span></p>
                </div>

                <hr class="border-t-2 border-dashed border-gray-400 my-4">

                <div class="mb-4">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left font-bold">Tên hàng</th>
                                <th class="text-center font-bold">Số lượng</th>
                                <th class="text-right font-bold">Đơn giá</th>
                                <th class="text-right font-bold">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in bill.details" :key="index">
                                <td class="py-1">{{ item.p_name }}</td>
                                <td class="py-1 text-center">{{ item.quantity }}</td>
                                <td class="py-1 text-right">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="py-1 text-right">{{ formatCurrency(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr class="border-t border-gray-400 my-4">

                <div class="text-right">
                    <p class="mb-1">Tổng tiền hàng: <span class="font-semibold">{{ formatCurrency(subtotal_amount) }}</span></p>
                    <p class="mb-1">Giảm giá: <span class="font-semibold">{{ formatCurrency(bill.discount_amount ?? 0) }}</span></p>
                    <p class="text-lg font-bold">Tổng thanh toán: <span class="font-bold">{{ formatCurrency(bill.total_amount) }}</span></p>
                </div>
                
                <hr class="border-t-2 border-dashed border-gray-400 my-4">

                <div class="text-right mb-4">
                    <p>Phương thức: <span class="font-semibold">{{ getPaymentMethodName(bill.payment_method) }}</span></p>
                    <p>Khách đưa: <span class="font-semibold">{{ formatCurrency(bill.customer_paid ?? bill.total_amount) }}</span></p>
                    <p>Tiền thối: <span class="font-semibold">{{ formatCurrency(bill.change_due ?? 0) }}</span></p>
                    <p>Ghi chú: <span class="font-semibold">{{ bill.note ?? 'Không có' }}</span></p>
                </div>
                
                <hr class="border-t-2 border-dashed border-gray-400 my-4">

                <div class="text-center text-xs">
                    <p>Cảm ơn quý khách!</p>
                    <p class="mt-1">Vui lòng kiểm tra hàng trước khi rời đi.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    @page {
        margin: 0;
    }
    body {
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Ẩn tất cả các thành phần không cần thiết */
    body > * {
        visibility: hidden;
    }

    /* Hiển thị phần in */
    #content-to-print {
        visibility: visible;
        position: absolute;
        left: 0;
        top: 0;
        width: 100vw;
        height: 100vh; /* Đảm bảo chiếm toàn bộ chiều cao */
        margin: 0;
        padding: 0;
        background-color: white;
    }

    #invoice-content {
        /* Thiết lập khổ giấy cho hóa đơn in */
        width: 300px !important; /* Chiều rộng tương đương giấy K80 */
        margin: 0 auto !important; /* Căn giữa trên trang in */
        padding: 10px !important; /* Giảm padding */
        
        /* Bỏ các hiệu ứng không cần thiết khi in */
        box-shadow: none !important;
        border: none !important;
        background-color: white !important;

        /* Font chữ in */
        font-family: 'Arial', sans-serif !important;
        font-size: 10pt !important; /* Cỡ chữ nhỏ hơn cho hóa đơn */
    }

    hr {
        border-top: 1px dashed #000 !important;
        border-bottom: none !important;
        margin: 10px 0 !important;
    }

    /* Ẩn nút in và các thành phần không liên quan */
    .print\:hidden {
        display: none !important;
    }
}
</style>