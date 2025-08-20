<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, Printer, Eye, Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    customer: Object,
    // Thêm prop mới
    date: {
        type: String,
        default: '',
    },
});

const isModalOpen = ref(false);
const selectedBill = ref(null);
// Thêm biến ref mới cho ngày
const date = ref(props.date || '');

const goBack = () => {
    window.history.back();
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(value);
};

const subtotal_amount = (bill) => {
    if (!bill || !bill.details) return 0;
    return bill.details.reduce((sum, item) => sum + item.subtotal, 0);
};

const formatDateTime = (val) => {
    if (!val) return '-';
    const d = typeof val === 'string' ? new Date(val.replace(' ', 'T')) : new Date(val);
    if (isNaN(d)) return val;
    return d.toLocaleString('vi-VN');
};

const openBillDetailsModal = (bill) => {
    selectedBill.value = bill;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedBill.value = null;
};

const printBill = () => {
    window.print();
};

const translatePaymentMethod = (method) => {
    switch (method) {
        case 'credit_card':
            return 'Thẻ tín dụng';
        case 'bank_transfer':
            return 'Chuyển khoản';
        case 'cash':
            return 'Tiền mặt';
        default:
            return method || '-';
    }
};

const filterBillsByDate = () => {
    router.get(route('cashier.customer.show', {
        customer: props.customer.id,
        // Gửi tham số date duy nhất
        date: date.value,
    }), {
        preserveState: true,
        replace: true,
    });
};

</script>

<template>
    <Head :title="`Khách hàng: ${customer.customer_name}`" />
    <CashierLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex items-center gap-4 mb-6">
                <button @click="goBack" class="rounded-full p-2 bg-gray-100 hover:bg-gray-200 transition">
                    <ChevronLeft class="w-6 h-6 text-gray-600" />
                </button>
                <h1 class="text-2xl font-bold text-gray-800">Thông tin khách hàng</h1>
            </div>

            <div v-if="customer" class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="font-semibold text-gray-700">Tên khách hàng:</p>
                        <p class="text-gray-900">{{ customer.customer_name }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700">Email:</p>
                        <p class="text-gray-900">{{ customer.email || '-' }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700">Số điện thoại:</p>
                        <p class="text-gray-900">{{ customer.phone || '-' }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700">Số dư ví:</p>
                        <p class="text-green-600 font-bold">{{ formatCurrency(customer.wallet) }}</p>
                    </div>
                </div>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-4">Lịch sử mua hàng</h2>
            
            <form @submit.prevent="filterBillsByDate" class="flex items-center gap-4 mb-6">
                <input v-model="date" type="date"
                    class="p-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200" />
                
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                    <Search class="w-5 h-5 inline-block mr-2" />Lọc theo ngày
                </button>
            </form>

            <div v-if="customer.bills.length > 0" class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Số hóa đơn</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Tổng tiền</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Ngày tạo</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Nhân viên</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="bill in customer.bills" :key="bill.id" class="border-t hover:bg-gray-50">
                            <td class="p-4">{{ bill.bill_number }}</td>
                            <td class="p-4">{{ formatCurrency(bill.total_amount) }}</td>
                            <td class="p-4">{{ bill.created_at }}</td>
                            <td class="p-4">{{ bill.cashier_name ?? 'N/A' }}</td>
                            <td class="p-4">
                                <button @click="openBillDetailsModal(bill)"
                                    class="text-blue-600 hover:underline focus:outline-none flex items-center gap-1">
                                    <Eye class="w-5 h-5 inline-block" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-else class="text-gray-500 text-center mt-6">
                Không tìm thấy hóa đơn nào trong ngày đã chọn.
            </p>
                    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 z-40 bg-gray-900 bg-opacity-70 backdrop-blur-sm no-print-bg" @click.self="closeModal"></div>
            <div id="invoice-modal-content" class="relative z-50 flex flex-col max-h-screen-90 overflow-y-auto w-full max-w-xl mx-4 my-4 rounded-lg shadow-xl print-container">
                <div class="bg-white p-8 font-mono text-sm">
                    <div class="flex justify-between mb-4 no-print">
                        <button @click="closeModal" class="flex items-center gap-1 rounded-md bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300">
                            <span>Đóng</span>
                        </button>
                        <button @click="printBill" class="flex items-center gap-1 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                            <Printer class="w-4 h-4" />
                            <span>In</span>
                        </button>
                    </div>

                    <div v-if="selectedBill" id="printable-invoice">
                        <div class="text-center mb-6">
                            <h1 class="text-xl font-bold">Hóa Đơn Bán Hàng</h1>
                            <p class="mt-1">Cửa hàng bán lẻ G7 Mart</p>
                            <p>Trịnh Văn Bô, Hà Nội</p>
                            <p>Hotline: 4444 6666</p>
                        </div>
                        <hr class="border-t-2 border-dashed border-gray-400 my-4">
                        <div class="mb-4">
                            <p>Mã hóa đơn: <span class="font-semibold">{{ selectedBill.bill_number }}</span></p>
                            <p>Ngày giờ: <span class="font-semibold">{{ formatDateTime(selectedBill.created_at) }}</span></p>
                            <p>Nhân viên: <span class="font-semibold"><td>{{ selectedBill.cashier_name ?? 'N/A' }}</td></span></p>
                            <p>Khách hàng: <span class="font-semibold">{{ customer.customer_name ?? 'Khách lẻ' }}</span></p>
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
                                    <tr v-for="(item, index) in selectedBill.details" :key="index">
                                        <td class="py-1">{{ item.product_name }}</td>
                                        <td class="py-1 text-center">{{ item.quantity }}</td>
                                        <td class="py-1 text-right">{{ formatCurrency(item.unit_price) }}</td>
                                        <td class="py-1 text-right">{{ formatCurrency(item.subtotal) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <hr class="border-t border-gray-400 my-4">
                        <div class="text-right">
                            <p class="mb-1">Tổng tiền hàng: <span class="font-semibold">{{ formatCurrency(subtotal_amount(selectedBill)) }}</span></p>
                            <p class="text-lg font-bold">Tổng thanh toán: <span class="font-bold">{{ formatCurrency(selectedBill.total_amount) }}</span></p>
                        </div>
                        <hr class="border-t-2 border-dashed border-gray-400 my-4">
                        <div class="text-right mb-4">
                            <p>Phương thức: <span class="font-semibold">{{ translatePaymentMethod(selectedBill.payment_method) }}</span></p>
                            <p>Khách đưa: <span class="font-semibold">{{ formatCurrency(selectedBill.customer_paid ?? selectedBill.total_amount) }}</span></p>
                            <p>Tiền thối: <span class="font-semibold">{{ formatCurrency(selectedBill.change_due ?? 0) }}</span></p>
                            <p>Ghi chú: <span class="font-semibold">{{ selectedBill.note ?? 'Không có' }}</span></p>
                        </div>
                        <hr class="border-t-2 border-dashed border-gray-400 my-4">
                        <div class="text-center text-xs">
                            <p>Cảm ơn quý khách!</p>
                            <p class="mt-1">Vui lòng kiểm tra hàng trước khi rời đi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div>

        </CashierLayout>
</template>
<style>
/* Đặt khối <style> ra ngoài phần <template> */
@media (min-height: 500px) {
    .max-h-screen-90 {
        max-height: 90vh;
    }
}
@media print {
  /* Ẩn tất cả phần không cần in */
  .no-print, .no-print-bg { display: none !important; }
  body * { visibility: hidden !important; }

  /* Chỉ hiện phần hóa đơn */
  #printable-invoice, #printable-invoice * { visibility: visible !important; }

  /* Đặt hóa đơn ra khỏi khung modal, phủ đúng vùng trang in */
  #printable-invoice {
    position: fixed !important;         /* quan trọng: không còn bám theo modal */
    top: 0 !important; left: 0 !important; right: 0 !important;
    margin: 0 auto !important; /* Căn giữa trên trang in */
    padding: 0 !important;
    width: 100% !important;             /* chiếm toàn bộ bề ngang vùng in */
    background: #fff !important;
    font-family: Arial, Helvetica, sans-serif !important;
    font-size: 12pt !important;
    -webkit-print-color-adjust: exact;  /* giữ màu border/dashed nếu có */
    print-color-adjust: exact;
  }

  body {
    margin: 0 !important;
    font-size: 12px !important;
    transform: scale(0.95); /* nếu muốn co nhỏ */
    transform-origin: top center !important; /* căn giữa theo trục ngang */
  }

  #printable-invoice {
    width: 80mm !important;         /* khổ giấy hóa đơn (80mm ~ A4 co nhỏ) */
    margin: 0 auto !important;      /* căn giữa */
    background: #fff !important;
    padding: 0 !important;
  }

  /* Bảng gọn gàng, căng đủ chiều ngang */
  table { width: 100% !important; border-collapse: collapse !important; }
  th, td { padding: 4px 6px !important; }

  /* Khổ giấy & lề in */
    @page {
        size: auto;
        margin: 5mm;
    }

    /* Ẩn những phần không cần in */
    header, footer, .no-print, 
    #header, #footer {
        display: none !important;
    }

}


</style>