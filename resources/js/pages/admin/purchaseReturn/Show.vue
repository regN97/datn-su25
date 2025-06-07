<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head ,router } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types';


defineProps<{
  purchaseReturn: {
    return_number: string
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
  return date.toLocaleDateString('vi-VN') // hiển thị theo định dạng: dd/mm/yyyy
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
function goToIndex(){
  router.visit(`/admin/purchaseReturn`);
}
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Quản lý phiếu trả hàng',
    href: '/admin/purchaseReturn',
  },
];
</script>

<template>
  <Head title="Chi tiết phiếu trả hàng" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- Khoảng cách viền ngoài to hơn -->
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-8 bg-gray-50">
      <!-- Khung trắng chứa nội dung chính -->
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        
        <!-- Tiêu đề và nút -->
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-2xl font-bold border-l-4 border-blue-500 pl-4 text-gray-800">
            Chi tiết phiếu trả hàng
          </h1>
          <button
            @click="goToIndex"
            class="bg-gray-100 text-gray-800 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-200 transition"
          >
            🔙 Quay lại danh sách
          </button>
        </div>

        <!-- Thông tin chung -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-2 shadow-sm">
            <p><strong>Mã phiếu trả hàng:</strong> {{ purchaseReturn.return_number }}</p>
            <p><strong>Mã đơn đặt hàng:</strong> {{ purchaseReturn.purchase_order_code }}</p>
            <p><strong>Nhà cung cấp:</strong> {{ purchaseReturn.supplier_name }}</p>
            <p><strong>Lý do trả hàng:</strong> {{ purchaseReturn.reason || 'Không có' }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-2 shadow-sm">
            <p><strong>Ngày trả hàng:</strong> {{ formatDate(purchaseReturn.return_date) }}</p>
            <p>
              <strong>Trạng thái:</strong>
              <span
                class="inline-block font-semibold text-base px-3 py-1 rounded"
                :class="statusTextClass(purchaseReturn.status)"
              >
                {{ purchaseReturn.status }}
              </span>
            </p>
            <p><strong>Người tạo phiếu:</strong> {{ purchaseReturn.created_by }}</p>
          </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="mb-8">
          <h2 class="text-lg font-semibold mb-3 text-gray-800">Danh sách sản phẩm</h2>
          <div class="overflow-x-auto">
            <table class="min-w-full table-auto border-separate border-spacing-y-3 text-sm">
              <thead class="bg-blue-50 text-gray-700 font-semibold uppercase">
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
                <tr
                  v-for="item in purchaseReturn.items"
                  :key="item.product_sku"
                  class="bg-white border border-gray-200 rounded-lg shadow-sm"
                >
                  <td class="px-6 py-4 rounded-l-lg">{{ item.product_name }}</td>
                  <td class="px-6 py-4">{{ item.batch_number }}</td>
                  <td class="px-6 py-4">{{ item.product_sku }}</td>
                  <td class="px-6 py-4">{{ item.manufacturing_date || '—' }}</td>
                  <td class="px-6 py-4">{{ item.expiry_date || '—' }}</td>
                  <td class="px-6 py-4 text-center">{{ item.quantity_returned }}</td>
                  <td class="px-6 py-4 text-right">{{ item.unit_cost.toLocaleString() }} đ</td>
                  <td class="px-6 py-4 text-right">{{ item.subtotal.toLocaleString() }} đ</td>
                  <td class="px-6 py-4 rounded-r-lg">{{ item.reason || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tổng cộng -->
        <div class="flex flex-col md:flex-row justify-between text-gray-800 font-semibold mb-6">
          <p>Tổng số sản phẩm trả: {{ purchaseReturn.total_items_returned }}</p>
          <p>Tổng giá trị trả lại: {{ purchaseReturn.total_value_returned.toLocaleString() }} đ</p>
        </div>

        <!-- Hành động -->
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
  </AppLayout>
</template>
