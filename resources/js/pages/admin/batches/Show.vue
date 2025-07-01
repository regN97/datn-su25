<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{ batch: any }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quản lý lô hàng', href: '/admin/batches' },
    { title: 'Chi tiết lô hàng', href: `/admin/batches/${props.batch.id}` },
];

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
    return new Intl.DateTimeFormat('vi-VN', {
        year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
    }).format(new Date(date));
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
</script>

<template>
  <Head title="Chi tiết Phiếu nhập hàng" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="bg-gray-50 min-h-screen p-6">
      <div class="mx-auto max-w-7xl space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button @click="goBack" class="h-9 w-9 rounded border border-gray-300 bg-white text-gray-700 hover:border-gray-400">
              ←
            </button>
            <div>
              <h1 class="text-xl font-bold text-gray-800">{{ props.batch.batch_number }}</h1>
              <p class="text-sm text-gray-500">Phiếu nhập hàng - {{ formatDateTime(props.batch.received_date) }}</p>
            </div>
            <span
              v-if="props.batch.payment_status === 'paid'"
              class="ml-4 inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800"
            >
              ✅ Đã thanh toán
            </span>
            <span
              v-else-if="props.batch.payment_status === 'partially_paid'"
              class="ml-4 inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-700"
            >
              🟡 Đã thanh toán một phần
            </span>
            <span
              v-else
              class="ml-4 inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700"
            >
              ⚠ Chưa thanh toán
            </span>
          </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left Section -->
          <div class="lg:col-span-2 space-y-4">
            <!-- Status -->
            <div class="flex items-center gap-2 text-green-600 font-medium text-sm">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              Đã nhập kho
            </div>

            <!-- Product Table -->
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
                  <tr v-for="item in props.batch.batch_items" :key="item.id" class="border-b bg-white hover:bg-gray-50">
                    <td class="px-4 py-3 flex items-center gap-3">
                      <img :src="item.product?.image || '/placeholder.jpg'" class="h-10 w-10 rounded object-cover" />
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
                    <td class="px-4 py-3 text-right font-medium text-gray-800">{{ formatCurrency(item.total_amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Payment Box -->
          <!-- Payment Box -->
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
      <span v-if="props.batch.total_quantity" class="text-gray-500">({{ props.batch.total_quantity }} sản phẩm)</span>
    </p>
    <p>
      <strong>Giảm giá:</strong>
      {{ props.batch.discount_percentage ? props.batch.discount_percentage + '%' : '0%' }}
    </p>
    <p>
      <strong>Chi phí nhập hàng:</strong>
      {{ props.batch.shipping_fee !== null ? formatCurrency(props.batch.shipping_fee) : '0₫' }}
    </p>
    <p class="font-medium">
      <strong>Tiền cần trả NCC:</strong>
      {{ formatCurrency(props.batch.total_amount_after_discount) }}
    </p>

    <!-- Nếu đã thanh toán hoặc thanh toán 1 phần -->
    <template v-if="props.batch.payment_status === 'paid' || props.batch.payment_status === 'partially_paid'">
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
          {{ formatCurrency(props.batch.total_amount_after_discount - props.batch.paid_amount) }}
        </div>
      </div>
    </template>
  </div>

  <!-- Nút nếu chưa thanh toán -->
  <div v-if="props.batch.payment_status === 'unpaid'" class="mt-3">
    <button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 text-sm">Xác nhận thanh toán</button>
  </div>
</div>


            <!-- Lịch sử -->
            <div class="rounded-lg border border-gray-200 bg-white p-4">
              <h2 class="text-base font-semibold mb-2">Lịch sử phiếu nhập hàng</h2>
              <div class="space-y-1 text-sm">
                <div class="flex items-start gap-2">
                  <span class="text-blue-500 mt-1">●</span>
                  <p class="text-gray-800">23:01 - {{ props.batch.created_by?.name || 'Người dùng' }} xác nhận nhập kho</p>
                </div>
                <div class="flex items-start gap-2">
                  <span class="text-blue-500 mt-1">●</span>
                  <p class="text-gray-800">23:01 - {{ props.batch.created_by?.name || 'Người dùng' }} tạo phiếu nhập hàng</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Section -->
          <div class="space-y-4">
            <!-- Supplier Info -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-4 space-y-1.5 text-sm">
              <h2 class="text-base font-semibold mb-2">Nhà cung cấp</h2>
              <div class="flex items-center gap-2">
                <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">👤</div>
                <div>
                  <p class="font-medium text-blue-700">{{ props.batch.supplier?.name || 'N/A' }}</p>
                  <p class="text-gray-600">{{ props.batch.supplier?.phone || 'N/A' }}</p>
                </div>
              </div>
              <p class="text-gray-600">{{ props.batch.supplier?.address || 'N/A' }}</p>
            </div>

            <!-- Chi nhánh -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-4 text-sm">
              <h2 class="text-base font-semibold mb-2">Chi nhánh nhập</h2>
              <p class="text-gray-700">Cửa hàng chính</p>
            </div>

            <!-- Extra Info -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-4 text-sm space-y-1">
              <h2 class="text-base font-semibold mb-2">Thông tin bổ sung</h2>
              <p><strong>Mã phiếu:</strong> <span class="text-blue-600 underline">P000002</span></p>
              <p><strong>Nhân viên phụ trách:</strong> {{ props.batch.created_by?.name || 'N/A' }}</p>
              <p><strong>Email:</strong> {{ props.batch.created_by?.email || 'N/A' }}</p>
              <p><strong>Ngày nhập dự kiến:</strong> {{ formatDateTime(props.batch.received_date) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>


