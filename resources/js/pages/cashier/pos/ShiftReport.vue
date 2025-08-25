<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import {
  ClipboardList,
  Clock4,
  User,
  FileText,
  CalendarClock,
  CalendarX,
  TimerReset,
  StickyNote,
  BarChart,
  CreditCard,
  Banknote,
  ArrowDownLeft,
  ReceiptText,
  Printer,
  CheckCircle,
  Mail,
  Info,
  Calendar,
  Package,
  AlertCircle
} from 'lucide-vue-next';

const props = usePage().props;
const shiftData = ref(props.shiftReport);
const showCloseShiftModal = ref(false);
const closingAmount = ref(0);
const closeShiftNotes = ref('');

const formatCurrency = (value) => {
  const absValue = Math.abs(value);
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(absValue);
};

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
  date ? new Date(date).toLocaleString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  }) : 'Chưa kết thúc';

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
    expandedRows.value = expandedRows.value.filter(i => i !== index);
  }
};

const expandedRows = ref([]);
</script>

<template>
  <Head title="Báo cáo ca làm việc" />
  <CashierLayout>
    <div class="p-6 bg-gray-50 min-h-screen">
      <div class="mb-6 flex items-center gap-2">
        <ClipboardList class="w-6 h-6 text-gray-700" />
        <h1 class="text-2xl font-bold text-gray-800">Báo cáo ca làm việc</h1>
      </div>

      <div v-if="props.error" class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">
        {{ props.error }}
      </div>

      <div v-else>
        <div class="bg-white rounded-xl shadow p-6 mb-6">
          <div class="flex items-center gap-2 mb-4">
            <Clock4 class="w-5 h-5 text-blue-600" />
            <h2 class="text-lg font-semibold">Thông tin ca làm việc</h2>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <p><FileText class="inline w-4 h-4 mr-1" /> <strong>Mã ca:</strong> {{ shiftData.shift.id }}</p>
            <p><User class="inline w-4 h-4 mr-1" /> <strong>Nhân viên:</strong> {{ shiftData.shift.user_name }}</p>
            <p><Mail class="inline w-4 h-4 mr-1" /> <strong>Email:</strong> {{ shiftData.shift.user_email }}</p>
            <p><Info class="inline w-4 h-4 mr-1" /> <strong>Tên ca:</strong> {{ shiftData.shift.shift_name }}</p>
            <p><Info class="inline w-4 h-4 mr-1" /> <strong>Mô tả:</strong> {{ shiftData.shift.shift_description || 'Không có mô tả' }}</p>
            <p><Calendar class="inline w-4 h-4 mr-1" /> <strong>Ngày:</strong> {{ shiftData.shift.date }}</p>
            <p><CalendarClock class="inline w-4 h-4 mr-1" /> <strong>Bắt đầu:</strong> {{ formatFullDateTime(shiftData.shift.start_time) }}</p>
            <p><CalendarX class="inline w-4 h-4 mr-1" /> <strong>Kết thúc:</strong> {{ formatFullDateTime(shiftData.shift.end_time) }}</p>
            <p><TimerReset class="inline w-4 h-4 mr-1" /> <strong>Thời lượng:</strong> {{ shiftData.shift.duration }}</p>
            <p><Info class="inline w-4 h-4 mr-1" /> <strong>Trạng thái:</strong> {{ shiftData.shift.status }}</p>
          </div>
          <div class="mt-4">
            <label for="notes" class="font-medium text-gray-700 flex items-center gap-1">
              <StickyNote class="w-4 h-4" /> Ghi chú ca làm việc
            </label>
            <textarea id="notes" v-model="notes" @blur="saveNotes(notes)"
              class="mt-2 w-full rounded-lg border border-gray-300 focus:ring focus:ring-blue-200" rows="4"
              placeholder="Nhập ghi chú về ca làm việc (nếu có)..." />
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6 mb-6">
          <div class="flex items-center gap-2 mb-4">
            <BarChart class="w-5 h-5 text-gray-600" />
            <h2 class="text-lg font-semibold">Tổng quan doanh thu</h2>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
            <div class="p-4 bg-green-50 rounded shadow-sm">
              <p class="text-gray-600 flex items-center gap-1">
                <ReceiptText class="w-4 h-4" /> Tổng doanh thu (Đã thanh toán)
              </p>
              <p class="text-lg font-bold text-green-600">{{ formatCurrency(shiftData.summary.total_revenue) }}</p>
            </div>
            <div class="p-4 bg-blue-50 rounded shadow-sm">
              <p class="text-gray-600 flex items-center gap-1">
                <ReceiptText class="w-4 h-4" /> Tổng giao dịch
              </p>
              <p class="text-lg font-bold text-blue-600">{{ shiftData.summary.total_transactions }}</p>
            </div>
            <div class="p-4 bg-yellow-50 rounded shadow-sm">
              <p class="text-gray-600 flex items-center gap-1">
                <Banknote class="w-4 h-4" /> Doanh thu tiền mặt
              </p>
              <p class="text-lg font-bold text-yellow-600">{{ formatCurrency(shiftData.summary.cash_revenue) }}</p>
            </div>
            <div class="p-4 bg-cyan-50 rounded shadow-sm">
              <p class="text-gray-600 flex items-center gap-1">
                <CreditCard class="w-4 h-4" /> Doanh thu chuyển khoản
              </p>
              <p class="text-lg font-bold text-cyan-600">{{ formatCurrency(shiftData.summary.bank_revenue) }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded shadow-sm">
              <p class="text-gray-600 flex items-center gap-1">
                <ArrowDownLeft class="w-4 h-4" /> Giá trị trả hàng
              </p>
              <p class="text-lg font-bold text-red-600">−{{ formatCurrency(shiftData.summary.return_value) }}</p>
            </div>
            <div class="p-4 bg-emerald-50 rounded shadow-sm">
              <p class="text-gray-600 flex items-center gap-1">
                <ReceiptText class="w-4 h-4" /> Doanh thu ròng
              </p>
              <p class="text-lg font-bold text-emerald-600">{{ formatCurrency(shiftData.summary.net_revenue) }}</p>
            </div>
            <div class="p-4 bg-blue-50 rounded shadow-sm">
              <p class="text-gray-600 flex items-center gap-1">
                <ReceiptText class="w-4 h-4" /> Giao dịch hoàn tất
              </p>
              <p class="text-lg font-bold text-blue-600">{{ shiftData.summary.completed_transactions }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded shadow-sm">
              <p class="text-gray-600 flex items-center gap-1">
                <ArrowDownLeft class="w-4 h-4" /> Giao dịch trả hàng
              </p>
              <p class="text-lg font-bold text-red-600">{{ shiftData.summary.refunded_transactions }}</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center gap-2 mb-4">
              <BarChart class="w-5 h-5 text-gray-600" />
              <h2 class="text-lg font-semibold">Sản phẩm bán chạy</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full table-auto text-sm border">
                <thead class="bg-gray-100">
                  <tr>
                    <th class="p-3 border text-left">Sản phẩm</th>
                    <th class="p-3 border text-right">Tổng số lượng</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(product, index) in shiftData.summary.top_products" :key="index" class="hover:bg-gray-50">
                    <td class="p-3 border">{{ product.product_name }}</td>
                    <td class="p-3 border text-right">{{ product.total_quantity }}</td>
                  </tr>
                  <tr v-if="!shiftData.summary.top_products.length" class="text-gray-500">
                    <td colspan="2" class="p-3 text-center">Không có sản phẩm nào được bán.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center gap-2 mb-4">
              <User class="w-5 h-5 text-gray-600" />
              <h2 class="text-lg font-semibold">Khách hàng mua nhiều nhất</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full table-auto text-sm border">
                <thead class="bg-gray-100">
                  <tr>
                    <th class="p-3 border text-left">Khách hàng</th>
                    <th class="p-3 border text-right">Tổng số tiền</th>
                    <th class="p-3 border text-right">Số hóa đơn</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(customer, index) in shiftData.summary.top_customers" :key="index" class="hover:bg-gray-50">
                    <td class="p-3 border">{{ customer.customer_name }}</td>
                    <td class="p-3 border text-right">{{ formatCurrency(customer.total_amount) }}</td>
                    <td class="p-3 border text-right">{{ customer.bill_count }}</td>
                  </tr>
                  <tr v-if="!shiftData.summary.top_customers.length" class="text-gray-500">
                    <td colspan="3" class="p-3 text-center">Không có dữ liệu khách hàng.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6 mt-6">
          <div class="flex items-center gap-2 mb-4">
            <ArrowDownLeft class="w-5 h-5 text-red-600" />
            <h2 class="text-lg font-semibold">Chi tiết giao dịch trả hàng</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm border">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-3 border text-left">Mã phiếu trả</th>
                  <th class="p-3 border text-left">Mã hóa đơn gốc</th>
                  <th class="p-3 border text-left">Thời gian</th>
                  <th class="p-3 border text-right">Số tiền</th>
                  <th class="p-3 border text-left">Lý do</th>
                  <th class="p-3 border text-left">Chi tiết</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(transaction, index) in shiftData.transactions.filter(t => t.type === 'Trả hàng')" :key="index" class="hover:bg-gray-50">
                  <td class="p-3 border">{{ transaction.return_bill_number }}</td>
                  <td class="p-3 border">{{ transaction.bill_id }}</td>
                  <td class="p-3 border">{{ transaction.time }}</td>
                  <td class="p-3 border text-right text-red-600">−{{ formatCurrency(transaction.amount) }}</td>
                  <td class="p-3 border">{{ transaction.return_reason }}</td>
                  <td class="p-3 border">
                    <button @click="toggleDetails(index)" class="text-blue-600 hover:underline">
                      {{ expandedRows.includes(index) ? 'Ẩn' : 'Xem' }}
                    </button>
                    <div v-if="expandedRows.includes(index)" class="mt-2">
                      <table class="w-full text-sm border">
                        <thead>
                          <tr class="bg-gray-50">
                            <th class="p-2 border text-left">Sản phẩm</th>
                            <th class="p-2 border text-right">Số lượng trả</th>
                            <th class="p-2 border text-right">Đơn giá</th>
                            <th class="p-2 border text-right">Tổng</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(detail, i) in transaction.details" :key="i">
                            <td class="p-2 border">{{ detail.product_name }}</td>
                            <td class="p-2 border text-right">{{ detail.quantity }}</td>
                            <td class="p-2 border text-right">{{ formatCurrency(detail.unit_price) }}</td>
                            <td class="p-2 border text-right">{{ formatCurrency(detail.total) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
                <tr v-if="!shiftData.transactions.filter(t => t.type === 'Trả hàng').length" class="text-gray-500">
                  <td colspan="6" class="p-3 text-center">Không có giao dịch trả hàng.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 print:hidden">
          <button @click="printReport" class="btn btn-secondary shadow inline-flex items-center gap-1">
            <Printer class="w-4 h-4" /> In báo cáo
          </button>
          <button @click="openCloseShiftModal" class="btn btn-success shadow inline-flex items-center gap-1">
            <CheckCircle class="w-4 h-4" /> Kết thúc ca
          </button>
        </div>

        <div v-if="showCloseShiftModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-4">Đóng ca làm việc</h2>
            <div class="mb-4">
              <label for="closing_amount" class="block text-sm font-medium text-gray-700">Số tiền đóng ca (VND)</label>
              <input id="closing_amount" v-model.number="closingAmount" type="number" min="0"
                class="mt-1 w-full rounded-lg border border-gray-300 focus:ring focus:ring-blue-200"
                placeholder="Nhập số tiền đóng ca" />
            </div>
            <div class="mb-4">
              <label for="close_shift_notes" class="block text-sm font-medium text-gray-700">Ghi chú</label>
              <textarea id="close_shift_notes" v-model="closeShiftNotes"
                class="mt-1 w-full rounded-lg border border-gray-300 focus:ring focus:ring-blue-200" rows="4"
                placeholder="Nhập ghi chú (nếu có)" />
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