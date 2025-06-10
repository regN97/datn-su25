<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3'; // Import router từ Inertiajs

// Định nghĩa props để nhận dữ liệu purchaseOrder từ controller
const props = defineProps({
  purchaseOrder: Object, // Đối tượng PurchaseOrder đã load đầy đủ mối quan hệ
});

// Breadcrumbs cho trang
const breadcrumbs = [
  { title: 'Quản lý đơn đặt hàng', href: route('admin.purchase-orders.index') },
  { title: `Chi tiết đơn đặt hàng - ${props.purchaseOrder.po_number || 'N/A'}`, href: null },
];

// Hàm quay lại danh sách đơn hàng
const goToIndex = () => {
  router.visit(route('admin.purchase-orders.index'));
};

// Hàm định dạng ngày tháng (DD/MM/YYYY)
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  return `${day}/${month}/${year}`;
};

// Hàm trả về class CSS cho màu nền/chữ của trạng thái đơn hàng
const statusTextClass = (status) => {
  switch (status) {
    case 'Đã duyệt': return 'bg-green-100 text-green-800';
    case 'Đã từ chối': return 'bg-red-100 text-red-800';
    case 'Đã hủy': return 'bg-gray-100 text-gray-800';
    case 'Đang chờ': return 'bg-yellow-100 text-yellow-800';
    case 'Nháp': return 'bg-blue-100 text-blue-800';
    case 'Đã gửi': return 'bg-purple-100 text-purple-800';
    case 'Đã nhận hàng': return 'bg-indigo-100 text-indigo-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

// Hàm dịch trạng thái thanh toán từ tiếng Anh sang tiếng Việt
const translatePaymentStatus = (status) => {
  switch (status) {
    case 'unpaid': return 'Chưa thanh toán';
    case 'partially_paid': return 'Đã thanh toán một phần';
    case 'paid': return 'Đã thanh toán';
    case 'overdue': return 'Quá hạn';
    default: return status;
  }
};

// Hàm trả về class CSS cho màu nền/chữ của trạng thái thanh toán
const paymentStatusClass = (status) => {
  switch (status) {
    case 'paid': return 'bg-green-100 text-green-800';
    case 'unpaid': return 'bg-red-100 text-red-800';
    case 'partially_paid': return 'bg-yellow-100 text-yellow-800';
    case 'overdue': return 'bg-purple-100 text-purple-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

// Hàm dịch trạng thái nhận hàng từ tiếng Anh sang tiếng Việt
const translateReceivedStatus = (status) => {
  switch (status) {
    case 'pending': return 'Đang chờ nhận';
    case 'partial': return 'Đã nhận một phần';
    case 'fully': return 'Đã nhận đủ';
    default: return status;
  }
};

// Hàm trả về class CSS cho màu nền/chữ của trạng thái nhận hàng
const receivedStatusClass = (status) => {
  switch (status) {
    case 'fully': return 'bg-green-100 text-green-800';
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'partial': return 'bg-orange-100 text-orange-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

// Hàm dịch phương thức thanh toán từ tiếng Anh sang tiếng Việt
const translatePaymentMethod = (method) => {
  switch (method) {
    case 'cash': return 'Tiền mặt';
    case 'bank_transfer': return 'Chuyển khoản ngân hàng';
    case 'credit': return 'Tín dụng';
    case 'check': return 'Séc';
    default: return method;
  }
};

</script>

<template>
  <Head :title="`Chi tiết đơn đặt hàng - ${purchaseOrder.po_number || 'N/A'}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-8 bg-gray-50">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

        <div class="flex items-center justify-between mb-6">
          <h1 class="text-2xl font-bold border-l-4 border-blue-500 pl-4 text-gray-800">
            Chi tiết đơn đặt hàng - {{ purchaseOrder.po_number || 'Không có' }}
          </h1>
          <button
            @click="goToIndex"
            class="bg-gray-100 text-gray-800 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-200 transition"
          >
            🔙 Quay lại danh sách
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-2 shadow-sm">
            <p><strong>Mã PO:</strong> {{ purchaseOrder.po_number || 'Không có' }}</p>
            <p><strong>Nhà cung cấp:</strong> {{ purchaseOrder.supplier ? purchaseOrder.supplier.name : 'N/A' }}</p>
            <p><strong>Ngày đặt hàng:</strong> {{ formatDate(purchaseOrder.order_date) || 'N/A' }}</p>
            <p><strong>Ngày giao dự kiến:</strong> {{ formatDate(purchaseOrder.expected_delivery_date) || 'N/A' }}</p>
            <p><strong>Ngày giao thực tế:</strong> {{ formatDate(purchaseOrder.actual_delivery_date) || 'Chưa giao' }}</p>
            <p><strong>Ghi chú:</strong> {{ purchaseOrder.notes || 'Không có' }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-2 shadow-sm">
            <p>
              <strong>Trạng thái:</strong>
              <span
                class="inline-block font-semibold text-base px-3 py-1 rounded"
                :class="statusTextClass(purchaseOrder.status.name )"
              >
                {{ purchaseOrder.status.name }}
              </span>
            </p>
            <p>
              <strong>Trạng thái TT:</strong>
              <span
                class="inline-block font-semibold text-base px-3 py-1 rounded"
                :class="paymentStatusClass(purchaseOrder.payment_status)"
              >
                {{ translatePaymentStatus(purchaseOrder.payment_status) }}
              </span>
            </p>
              <p>
              <strong>Trạng thái nhận hàng:</strong>
              <span
                class="inline-block font-semibold text-base px-3 py-1 rounded"
                :class="receivedStatusClass(purchaseOrder.received_status)"
              >
                {{ translateReceivedStatus(purchaseOrder.received_status) }}
              </span>
            </p>
            <p><strong>Điều khoản thanh toán:</strong> {{ purchaseOrder.payment_terms || 'Không có' }}</p>
            <p><strong>Phương thức thanh toán:</strong> {{ translatePaymentMethod(purchaseOrder.payment_method) || 'N/A' }}</p>
            <p><strong>Ngày đáo hạn thanh toán:</strong> {{ formatDate(purchaseOrder.payment_due_date) || 'N/A' }}</p>
            <p><strong>Người tạo:</strong> {{ purchaseOrder.creator ? purchaseOrder.creator.name : 'N/A' }}</p>
            <p><strong>Người duyệt:</strong> {{ purchaseOrder.approver ? purchaseOrder.approver.name : 'Chưa duyệt' }}</p>
            <p><strong>Thời gian duyệt:</strong> {{ formatDate(purchaseOrder.approved_at) || 'Chưa duyệt' }}</p>
          </div>
        </div>

        <div class="mb-8">
          <h2 class="text-lg font-semibold mb-3 text-gray-800">Danh sách sản phẩm</h2>
          <div class="overflow-x-auto">
            <table class="min-w-full table-fixed border-collapse text-sm border border-gray-200 rounded-lg overflow-hidden">
              <thead class="bg-blue-50 text-gray-700 font-semibold uppercase">
                <tr>
                  <th class="px-6 py-4 text-left">Tên sản phẩm</th>
                  <th class="px-6 py-4 text-left">Mã SKU</th>
                  <th class="px-6 py-4 text-center">Số lượng đặt</th>
                  <th class="px-6 py-4 text-center">Số lượng nhận</th>
                  <th class="px-6 py-4 text-right">Đơn giá</th>
                  <th class="px-6 py-4 text-right">Tổng phụ</th>
                  <th class="px-6 py-4 text-right">Thuế</th>
                  <th class="px-6 py-4 text-right">Chiết khấu</th>
                  <th class="px-6 py-4 text-left">Ghi chú</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in purchaseOrder.items"
                  :key="item.id"
                  class="bg-white border-b border-gray-200 last:border-b-0" >
                  <td class="px-6 py-4">{{ item.product ? item.product.name : item.product_name }}</td>
                  <td class="px-6 py-4">{{ item.product ? item.product.sku : item.product_sku }}</td>
                  <td class="px-6 py-4 text-center">{{ item.quantity_ordered }}</td>
                  <td class="px-6 py-4 text-center">{{ item.quantity_received }}</td>
                  <td class="px-6 py-4 text-right">{{ item.unit_cost ? item.unit_cost.toLocaleString('vi-VN') + '₫' : '0₫' }}</td>
                  <td class="px-6 py-4 text-right">{{ item.subtotal ? item.subtotal.toLocaleString('vi-VN') + '₫' : '0₫' }}</td>
                  <td class="px-6 py-4 text-right">{{ item.tax_amount ? item.tax_amount.toLocaleString('vi-VN') + '₫' : '0₫' }}</td>
                  <td class="px-6 py-4 text-right">{{ item.discount_amount ? item.discount_amount.toLocaleString('vi-VN') + '₫' : '0₫' }}</td>
                  <td class="px-6 py-4">{{ item.notes || '—' }}</td>
                </tr>
                <tr v-if="!purchaseOrder.items || purchaseOrder.items.length === 0">
                    <td colspan="9" class="text-center py-4 text-gray-500">Không có sản phẩm nào trong đơn hàng này.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="flex flex-col items-end gap-2 text-gray-800 mb-6">
            <h2 class="text-lg font-semibold mb-3 text-gray-800 w-full text-right">Tổng kết đơn hàng</h2>
            <div class="grid gap-y-2 gap-x-4" style="grid-template-columns: max-content auto;">
                <div class="text-left"><strong>Tổng phụ:</strong></div>
                <div class="text-right">{{ purchaseOrder.subtotal_amount ? purchaseOrder.subtotal_amount.toLocaleString('vi-VN') + '₫' : '0₫' }}</div>

                <div class="text-left"><strong>Tổng thuế:</strong></div>
                <div class="text-right">{{ purchaseOrder.tax_amount ? purchaseOrder.tax_amount.toLocaleString('vi-VN') + '₫' : '0₫' }}</div>

                <div class="text-left"><strong>Tổng chiết khấu:</strong></div>
                <div class="text-right">{{ purchaseOrder.discount_amount ? purchaseOrder.discount_amount.toLocaleString('vi-VN') + '₫' : '0₫' }}</div>

                <div class="text-left"><strong>Chi phí vận chuyển:</strong></div>
                <div class="text-right">{{ purchaseOrder.shipping_cost ? purchaseOrder.shipping_cost.toLocaleString('vi-VN') + '₫' : '0₫' }}</div>

                <div class="text-left text-xl"><strong>Tổng tiền:</strong></div>
                <div class="text-right text-xl font-bold">{{ purchaseOrder.total_amount ? purchaseOrder.total_amount.toLocaleString('vi-VN') + '₫' : '0₫' }}</div>

                <div class="text-left"><strong>Số tiền đã trả:</strong></div>
                <div class="text-right">{{ purchaseOrder.amount_paid ? purchaseOrder.amount_paid.toLocaleString('vi-VN') + '₫' : '0₫' }}</div>

                <div class="text-left"><strong>Số tiền còn lại:</strong></div>
                <div class="text-right">{{ purchaseOrder.balance_due ? purchaseOrder.balance_due.toLocaleString('vi-VN') + '₫' : '0₫' }}</div>
            </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
<style scoped>
</style>