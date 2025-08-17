<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Search, ChevronLeft, ChevronRight, Eye, UploadCloud, Image, Printer, X } from 'lucide-vue-next';

const props = defineProps({
    bills: {
        type: Object,
        default: () => ({
            data: [],
            from: null,
            to: null,
            total: 0,
            prev_page_url: null,
            next_page_url: null,
            links: [],
            current_page: 1,
        }),
    },
    query: {
        type: String,
        default: '',
    },
});

const query = ref(props.query || '');
const previewUrls = ref({});

const isModalOpen = ref(false);
const selectedBill = ref(null);

const searchBills = () => {
    router.post(route('cashier.bill.lookup.search'), { query: query.value });
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

const changePage = (page) => {
    router.get(
        route('cashier.bill.lookup'),
        { page, query: query.value },
        { preserveState: true }
    );
};

const handleInlineUpload = (event, bill) => {
    const file = event.target.files[0];
    if (!file) return;

    previewUrls.value[bill.id] = URL.createObjectURL(file);

    const formData = new FormData();
    formData.append('payment_proof', file);

    router.post(
        route('cashier.bill.lookup.proof', { bill: bill.id }),
        formData,
        {
            forceFormData: true,
            onSuccess: () => {
                router.reload();
            },
            onError: (errors) => {
                console.error('Upload error:', errors);
                alert('Có lỗi xảy ra khi tải lên: ' + Object.values(errors).join(', '));
            },
        }
    );
};

const formatDateTime = (val) => {
  if (!val) return '-';
  // xử lý cả chuỗi "YYYY-MM-DD HH:mm:ss"
  const d = typeof val === 'string' ? new Date(val.replace(' ', 'T')) : new Date(val);
  if (isNaN(d)) return val;            // nếu vẫn không parse được thì trả về nguyên gốc
  return d.toLocaleString('vi-VN');
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

</script>

<template>
    <Head title="Tra cứu hóa đơn" />
    <CashierLayout>
        <div class="p-6 max-w-7xl mx-auto no-print">
            <div class="mb-8">
                <form @submit.prevent="searchBills" class="flex items-center gap-4 max-w-2xl mx-auto">
                    <div class="relative flex-1">
                        <Search class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500" />
                        <input v-model="query" type="text"
                            placeholder="Nhập số hóa đơn, tên khách hàng, số điện thoại hoặc số lô..."
                            class="w-full p-3 pl-12 pr-4 bg-white border border-gray-300 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" />
                    </div>
                    <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-full font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                        Tìm kiếm
                    </button>
                </form>
            </div>

            <div v-if="bills && bills.data && bills.data.length" class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Số hóa đơn</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Khách hàng</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Tổng tiền</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Phương thức</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Ngày tạo</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Minh chứng</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="bill in bills.data" :key="bill.id" class="border-t hover:bg-gray-50">
                            <td class="p-4">{{ bill.bill_number }}</td>
                            <td class="p-4">{{ bill.customer_name || '-' }}</td>
                            <td class="p-4">{{ formatCurrency(bill.total_amount) }}</td>
                            <td class="p-4">{{ translatePaymentMethod(bill.payment_method) }}</td>
                            <td class="p-4">{{ bill.created_at }}</td>
                            <td class="p-4">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                        <a v-if="bill.payment_proof_url" :href="bill.payment_proof_url" target="_blank"
                                            class="text-blue-600 hover:underline">
                                            <Image class="w-5 h-5 inline-block" />
                                        </a>
                                        <span v-else>-</span>
                                    </div>
                                    <input type="file" :id="'proof-' + bill.id"
                                        class="text-sm file:text-sm file:rounded file:border-0 file:bg-blue-100 file:text-blue-700"
                                        accept="image/*" @change="handleInlineUpload($event, bill)" />

                                </div>
                            </td>
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
                {{ query ? `Không tìm thấy hóa đơn nào khớp với "${query}".` : 'Không có hóa đơn nào để hiển thị.' }}
            </p>

            <div v-if="bills && bills.data && bills.data.length" class="mt-6 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Hiển thị {{ bills.from }} đến {{ bills.to }} của {{ bills.total }} hóa đơn
                </p>
                <div class="flex items-center gap-2">
                    <button :disabled="!bills.prev_page_url" @click="changePage(bills.current_page - 1)"
                        class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronLeft class="w-5 h-5 text-gray-600" />
                    </button>
                    <button :disabled="!bills.next_page_url" @click="changePage(bills.current_page + 1)"
                        class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronRight class="w-5 h-5 text-gray-600" />
                    </button>
                </div>
            </div>
        </div>
        
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 z-40 bg-gray-900 bg-opacity-70 backdrop-blur-sm no-print-bg" @click.self="closeModal"></div>
            <div id="invoice-modal-content" class="relative z-50 flex flex-col max-h-screen-90 overflow-y-auto w-full max-w-xl mx-4 my-4 rounded-lg shadow-xl print-container">
                <div class="bg-white p-8 font-mono text-sm">
                    <div class="flex justify-between mb-4 no-print">
                        <button @click="closeModal" class="flex items-center gap-1 rounded-md bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300">
                            <X class="w-4 h-4" />
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
                            <p>Khách hàng: <span class="font-semibold">{{ selectedBill.customer_name ?? 'Khách lẻ' }}</span></p>
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
                            <p class="mb-1">Giảm giá: <span class="font-semibold">{{ formatCurrency(selectedBill.discount_amount ?? 0) }}</span></p>
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