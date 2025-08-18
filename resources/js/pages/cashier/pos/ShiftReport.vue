<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    ArrowDownLeft,
    Banknote,
    BarChart,
    Calendar,
    CalendarClock,
    CalendarX,
    CheckCircle,
    ClipboardList,
    Clock4,
    CreditCard,
    FileText,
    Info,
    Mail,
    Printer,
    ReceiptText,
    StickyNote,
    TimerReset,
    User,
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = usePage().props;
const shiftData = ref(props.shiftReport);
const showCloseShiftModal = ref(false);
const closingAmount = ref(0);
const closeShiftNotes = ref('');

const formatCurrency = (value) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Math.abs(value));

const formatDateTime = (date) => {
    if (!date || date === 'N/A') return 'N/A';
    try {
        if (/^\d{2}:\d{2}\s[AP]M$/.test(date)) return date;
        return new Date(date).toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: true });
    } catch (e) {
        return 'N/A';
    }
};

const formatFullDateTime = (date) =>
    date
        ? new Date(date).toLocaleString('vi-VN', {
              year: 'numeric',
              month: '2-digit',
              day: '2-digit',
              hour: '2-digit',
              minute: '2-digit',
              second: '2-digit',
              hour12: false,
          })
        : 'Chưa kết thúc';

const printReport = () => window.print();

const endShift = async () => {
    try {
        await axios.post('/cashier/shift-report/end', {
            closing_amount: closingAmount.value,
            notes: closeShiftNotes.value,
        });
        alert('Ca làm việc đã kết thúc thành công!');
        window.location.href = '/cashier/dashboard';
    } catch (error) {
        alert('Lỗi khi kết thúc ca: ' + (error.response?.data?.message || error.message));
    }
};

const openCloseShiftModal = () => {
    showCloseShiftModal.value = true;
};

const closeShiftModal = () => {
    showCloseShiftModal.value = false;
    closingAmount.value = 0;
    closeShiftNotes.value = '';
};

const notes = ref(shiftData.value?.shift?.notes || '');
const saveNotes = async (value) => {
    try {
        await axios.post('/cashier/shift-report/save-notes', { notes: value.value });
        shiftData.value.shift.notes = value.value;
        alert('Lưu ghi chú thành công!');
    } catch (error) {
        alert('Lỗi khi lưu ghi chú: ' + (error.response?.data?.message || error.message));
    }
};

const toggleDetails = (index) => {
    if (!expandedRows.value.includes(index)) {
        expandedRows.value.push(index);
    } else {
        expandedRows.value = expandedRows.value.filter((i) => i !== index);
    }
};

const expandedRows = ref([]);
</script>

<template>
    <Head title="Báo cáo ca làm việc" />
    <CashierLayout>
        <div class="min-h-screen bg-gray-50 p-6">
            <div class="mb-6 flex items-center gap-2">
                <ClipboardList class="h-6 w-6 text-gray-700" />
                <h1 class="text-2xl font-bold text-gray-800">Báo cáo ca làm việc</h1>
            </div>

            <div v-if="props.error" class="mb-6 rounded-lg bg-red-100 p-4 text-red-700">
                {{ props.error }}
            </div>

            <div v-else>
                <div class="mb-6 rounded-xl bg-white p-6 shadow">
                    <div class="mb-4 flex items-center gap-2">
                        <Clock4 class="h-5 w-5 text-blue-600" />
                        <h2 class="text-lg font-semibold">Thông tin ca làm việc</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                        <p><FileText class="mr-1 inline h-4 w-4" /> <strong>Mã ca:</strong> {{ shiftData.shift.id }}</p>
                        <p><User class="mr-1 inline h-4 w-4" /> <strong>Nhân viên:</strong> {{ shiftData.shift.user_name }}</p>
                        <p><Mail class="mr-1 inline h-4 w-4" /> <strong>Email:</strong> {{ shiftData.shift.user_email }}</p>
                        <p><Info class="mr-1 inline h-4 w-4" /> <strong>Tên ca:</strong> {{ shiftData.shift.shift_name }}</p>
                        <p><Info class="mr-1 inline h-4 w-4" /> <strong>Mô tả ca:</strong> {{ shiftData.shift.shift_description }}</p>
                        <p><Calendar class="mr-1 inline h-4 w-4" /> <strong>Ngày:</strong> {{ shiftData.shift.date }}</p>
                        <p>
                            <CalendarClock class="mr-1 inline h-4 w-4" /> <strong>Bắt đầu:</strong>
                            {{ formatFullDateTime(shiftData.shift.start_time) }}
                        </p>
                        <p><CalendarX class="mr-1 inline h-4 w-4" /> <strong>Kết thúc:</strong> {{ formatFullDateTime(shiftData.shift.end_time) }}</p>
                        <p><TimerReset class="mr-1 inline h-4 w-4" /> <strong>Thời lượng:</strong> {{ shiftData.shift.duration }}</p>
                        <p><Info class="mr-1 inline h-4 w-4" /> <strong>Trạng thái:</strong> {{ shiftData.shift.status }}</p>
                    </div>
                    <div class="mt-4">
                        <label for="notes" class="flex items-center gap-1 font-medium text-gray-700">
                            <StickyNote class="h-4 w-4" /> Ghi chú ca làm việc
                        </label>
                        <textarea
                            id="notes"
                            v-model="notes"
                            @blur="saveNotes(notes)"
                            class="mt-2 w-full rounded-lg border border-gray-300 focus:ring focus:ring-blue-200"
                            rows="4"
                            placeholder="Nhập ghi chú về ca làm việc (nếu có)..."
                        />
                    </div>
                </div>

                <div class="mb-6 rounded-xl bg-white p-6 shadow">
                    <div class="mb-4 flex items-center gap-2">
                        <BarChart class="h-5 w-5 text-gray-600" />
                        <h2 class="text-lg font-semibold">Tổng quan doanh thu</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        <div class="rounded bg-green-50 p-4 shadow-sm">
                            <p class="flex items-center gap-1 text-gray-600"><ReceiptText class="h-4 w-4" /> Tổng doanh thu (Đã thanh toán)</p>
                            <p class="text-lg font-bold text-green-600">{{ formatCurrency(shiftData.summary.total_revenue) }}</p>
                        </div>
                        <div class="rounded bg-blue-50 p-4 shadow-sm">
                            <p class="flex items-center gap-1 text-gray-600"><ReceiptText class="h-4 w-4" /> Tổng giao dịch</p>
                            <p class="text-lg font-bold text-blue-600">{{ shiftData.summary.total_transactions }}</p>
                        </div>
                        <div class="rounded bg-yellow-50 p-4 shadow-sm">
                            <p class="flex items-center gap-1 text-gray-600"><Banknote class="h-4 w-4" /> Doanh thu tiền mặt</p>
                            <p class="text-lg font-bold text-yellow-600">{{ formatCurrency(shiftData.summary.cash_revenue) }}</p>
                        </div>
                        <div class="rounded bg-cyan-50 p-4 shadow-sm">
                            <p class="flex items-center gap-1 text-gray-600"><CreditCard class="h-4 w-4" /> Doanh thu chuyển khoản</p>
                            <p class="text-lg font-bold text-cyan-600">{{ formatCurrency(shiftData.summary.bank_revenue) }}</p>
                        </div>
                        <div class="rounded bg-red-50 p-4 shadow-sm">
                            <p class="flex items-center gap-1 text-gray-600"><ArrowDownLeft class="h-4 w-4" /> Giá trị trả hàng</p>
                            <p class="text-lg font-bold text-red-600">{{ formatCurrency(shiftData.summary.return_value) }}</p>
                        </div>
                        <div class="rounded bg-orange-50 p-4 shadow-sm">
                            <p class="flex items-center gap-1 text-gray-600"><ReceiptText class="h-4 w-4" /> Doanh thu chờ thanh toán</p>
                            <p class="text-lg font-bold text-orange-600">{{ formatCurrency(shiftData.summary.pending_revenue) }}</p>
                        </div>
                        <div class="rounded bg-emerald-50 p-4 shadow-sm">
                            <p class="flex items-center gap-1 text-gray-600"><ReceiptText class="h-4 w-4" /> Doanh thu ròng</p>
                            <p class="text-lg font-bold text-emerald-600">{{ formatCurrency(shiftData.summary.net_revenue) }}</p>
                        </div>
                        <div class="rounded bg-purple-50 p-4 shadow-sm">
                            <p class="flex items-center gap-1 text-gray-600"><ReceiptText class="h-4 w-4" /> Giao dịch chờ thanh toán</p>
                            <p class="text-lg font-bold text-purple-600">{{ shiftData.summary.pending_transactions }}</p>
                        </div>
                        <div class="rounded bg-blue-50 p-4 shadow-sm">
                            <p class="flex items-center gap-1 text-gray-600"><ReceiptText class="h-4 w-4" /> Giao dịch hoàn tất</p>
                            <p class="text-lg font-bold text-blue-600">{{ shiftData.summary.completed_transactions }}</p>
                        </div>
                        <div class="rounded bg-red-50 p-4 shadow-sm">
                            <p class="flex items-center gap-1 text-gray-600"><ReceiptText class="h-4 w-4" /> Giao dịch trả hàng</p>
                            <p class="text-lg font-bold text-red-600">{{ shiftData.summary.refunded_transactions }}</p>
                        </div>
                    </div>
                </div>

                <div class="mb-6 rounded-xl bg-white p-6 shadow">
                    <div class="mb-4 flex items-center gap-2">
                        <ReceiptText class="h-5 w-5 text-gray-600" />
                        <h2 class="text-lg font-semibold">Danh sách giao dịch</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border p-3 text-left">Mã hóa đơn</th>
                                    <th class="border p-3 text-left">Thời gian</th>
                                    <th class="border p-3 text-right">Tổng tiền</th>
                                    <th class="border p-3 text-left">Phương thức thanh toán</th>
                                    <th class="border p-3 text-left">Loại</th>
                                    <th class="border p-3 text-left">Trạng thái thanh toán</th>
                                    <th class="border p-3 text-left">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(transaction, index) in shiftData.transactions" :key="index" class="hover:bg-gray-50">
                                    <td class="border p-3">{{ transaction.bill_id }}</td>
                                    <td class="border p-3">{{ formatDateTime(transaction.time) }}</td>
                                    <td
                                        :class="[
                                            'border p-3 text-right',
                                            transaction.type === 'Trả hàng'
                                                ? 'text-red-600'
                                                : transaction.type === 'Chờ thanh toán'
                                                  ? 'text-orange-600'
                                                  : 'text-green-600',
                                        ]"
                                    >
                                        {{ formatCurrency(transaction.amount) }}
                                    </td>
                                    <td class="border p-3">{{ transaction.payment_method }}</td>
                                    <td class="border p-3">{{ transaction.type }}</td>
                                    <td class="border p-3">{{ transaction.payment_status }}</td>
                                    <td class="border p-3">
                                        <button @click="toggleDetails(index)" class="text-blue-600 hover:underline">
                                            {{ expandedRows.includes(index) ? 'Ẩn' : 'Xem' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-if="expandedRows.length"
                                    v-for="(transaction, index) in shiftData.transactions"
                                    :key="'details-' + index"
                                    v-show="expandedRows.includes(index)"
                                >
                                    <td colspan="7" class="border bg-gray-50 p-3">
                                        <div class="text-sm">
                                            <p class="mb-2 font-semibold">Chi tiết hóa đơn {{ transaction.bill_id }}:</p>
                                            <table class="w-full border text-sm">
                                                <thead>
                                                    <tr class="bg-gray-200">
                                                        <th class="border p-2 text-left">Sản phẩm</th>
                                                        <th class="border p-2 text-right">Số lượng</th>
                                                        <th class="border p-2 text-right">Đơn giá</th>
                                                        <th class="border p-2 text-right">Thành tiền</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(detail, dIndex) in transaction.details" :key="dIndex">
                                                        <td class="border p-2">{{ detail.product_name }}</td>
                                                        <td class="border p-2 text-right">{{ detail.quantity }}</td>
                                                        <td class="border p-2 text-right">{{ formatCurrency(detail.unit_price) }}</td>
                                                        <td class="border p-2 text-right">{{ formatCurrency(detail.total) }}</td>
                                                    </tr>
                                                    <tr v-if="!transaction.details.length">
                                                        <td colspan="4" class="p-2 text-center text-gray-500">Không có chi tiết hóa đơn.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!shiftData.transactions.length" class="text-gray-500">
                                    <td colspan="7" class="p-3 text-center">Không có giao dịch trong ca này.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 print:hidden">
                    <button @click="printReport" class="btn btn-secondary inline-flex items-center gap-1 shadow">
                        <Printer class="h-4 w-4" /> In báo cáo
                    </button>
                    <button @click="openCloseShiftModal" class="btn btn-success inline-flex items-center gap-1 shadow">
                        <CheckCircle class="h-4 w-4" /> Kết thúc ca
                    </button>
                </div>

                <!-- Modal đóng ca -->
                <div v-if="showCloseShiftModal" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black">
                    <div class="w-full max-w-md rounded-lg bg-white p-6">
                        <h2 class="mb-4 text-lg font-semibold">Đóng ca làm việc</h2>
                        <div class="mb-4">
                            <label for="closing_amount" class="block text-sm font-medium text-gray-700">Số tiền đóng ca (VND)</label>
                            <input
                                id="closing_amount"
                                v-model.number="closingAmount"
                                type="number"
                                min="0"
                                class="mt-1 w-full rounded-lg border border-gray-300 focus:ring focus:ring-blue-200"
                                placeholder="Nhập số tiền đóng ca"
                            />
                        </div>
                        <div class="mb-4">
                            <label for="close_shift_notes" class="block text-sm font-medium text-gray-700">Ghi chú</label>
                            <textarea
                                id="close_shift_notes"
                                v-model="closeShiftNotes"
                                class="mt-1 w-full rounded-lg border border-gray-300 focus:ring focus:ring-blue-200"
                                rows="4"
                                placeholder="Nhập ghi chú (nếu có)"
                            />
                        </div>
                        <div class="flex justify-end gap-3">
                            <button @click="closeShiftModal" class="btn btn-secondary">Hủy</button>
                            <button @click="endShift" class="btn btn-success">Xác nhận đóng ca</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CashierLayout>
</template>

<style scoped></style>
