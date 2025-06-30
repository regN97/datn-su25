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
<Head title="Chi tiết Lô hàng" />
<AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-7xl">
      <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center">
          <button @click="goBack" class="flex h-10 w-10 items-center justify-center rounded border border-gray-300 bg-white text-gray-600 hover:border-gray-400 hover:text-gray-800">
            ←
          </button>
          <h1 class="ml-4 text-3xl font-bold text-gray-900">{{ props.batch.batch_number }}</h1>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Left Content -->
        <div class="flex flex-col gap-6 lg:col-span-2">
          <!-- Product Table -->
          <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-4">
              <h2 class="text-lg font-semibold">Sản phẩm trong lô hàng</h2>
            </div>
            <div class="p-4 overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Sản phẩm</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">SKU</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Ngày SX</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Hạn SD</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Đơn vị</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Giá nhập</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">SL đặt</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">SL nhận</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">SL hiện tại</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Tồn kho</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Tổng tiền</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in props.batch.batch_items" :key="item.id" class="bg-white hover:bg-gray-50">
                    <td class="px-4 py-2">{{ item.product?.name }}</td>
                    <td class="px-4 py-2">{{ item.product?.sku || 'N/A' }}</td>
                    <td class="px-4 py-2">{{ formatDateOnly(item.manufacturing_date) }}</td>
                    <td class="px-4 py-2">
                      <span :class="{
                        'text-red-600 font-medium': item.expiry_date && new Date(item.expiry_date) < new Date(),
                        'text-orange-500 font-medium': item.expiry_date && new Date(item.expiry_date) <= new Date(new Date().setMonth(new Date().getMonth() + 1))
                      }">
                        {{ formatDateOnly(item.expiry_date) }}
                      </span>
                    </td>
                    <td class="px-4 py-2">{{ item.product?.unit?.name || 'N/A' }}</td>
                    <td class="px-4 py-2">{{ formatCurrency(item.purchase_price) }}</td>
                    <td class="px-4 py-2">{{ item.ordered_quantity }}</td>
                    <td class="px-4 py-2">{{ item.received_quantity }}</td>
                    <td class="px-4 py-2">{{ item.current_quantity }}</td>
                    <td class="px-4 py-2">
                      <span :class="{
                        'text-green-600 font-medium': item.inventory_status === 'active',
                        'text-yellow-600 font-medium': item.inventory_status === 'low_stock',
                        'text-red-600 font-medium': item.inventory_status === 'out_of_stock' || item.inventory_status === 'expired'
                      }">
                        {{ getStatusDisplayName(item.inventory_status) }}
                      </span>
                    </td>
                    <td class="px-4 py-2">{{ formatCurrency(item.total_amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="props.batch.notes" class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-4">
              <h2 class="text-lg font-semibold">Ghi chú</h2>
            </div>
            <div class="p-4">
              <textarea disabled :value="props.batch.notes" class="w-full min-h-[80px] rounded-md border border-gray-300 p-2 text-sm"></textarea>
            </div>
          </div>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
          <!-- Supplier Info -->
          <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-4">
              <h2 class="text-lg font-semibold">Nhà cung cấp</h2>
            </div>
            <div class="p-4 space-y-1.5 text-sm">
              <p><strong>Tên:</strong> {{ props.batch.supplier?.name || 'N/A' }}</p>
              <p><strong>Địa chỉ:</strong> {{ props.batch.supplier?.address || 'N/A' }}</p>
              <p><strong>SĐT:</strong> {{ props.batch.supplier?.phone || 'N/A' }}</p>
            </div>
          </div>

          <!-- Payment Info -->
          <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-4">
              <h2 class="text-lg font-semibold">Thanh toán</h2>
            </div>
            <div class="p-4 space-y-2 text-sm">
              <p><strong>Tổng tiền:</strong> {{ formatCurrency(props.batch.total_amount) }}</p>
              <p><strong>Đã thanh toán:</strong> {{ formatCurrency(props.batch.paid_amount) }}</p>
              <p><strong>Trạng thái:</strong> {{ getStatusDisplayName(props.batch.payment_status) }}</p>
              <p><strong>Nhập hàng:</strong> {{ getStatusDisplayName(props.batch.receipt_status) }}</p>
              <p><strong>Ngày nhận:</strong> {{ formatDateOnly(props.batch.received_date) }}</p>
              <p><strong>Số hóa đơn:</strong> {{ props.batch.invoice_number || 'N/A' }}</p>
            </div>
          </div>

          <!-- Info -->
          <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-4">
              <h2 class="text-lg font-semibold">Thông tin bổ sung</h2>
            </div>
            <div class="p-4 space-y-1.5 text-sm">
              <p><strong>Tạo bởi:</strong> {{ props.batch.created_by?.name || 'N/A' }}</p>
              <p><strong>Email:</strong> {{ props.batch.created_by?.email || 'N/A' }}</p>
              <p><strong>Ngày tạo:</strong> {{ formatDateTime(props.batch.created_at) }}</p>
              <p><strong>Cập nhật bởi:</strong> {{ props.batch.updated_by?.name || 'N/A' }}</p>
              <p><strong>Email:</strong> {{ props.batch.updated_by?.email || 'N/A' }}</p>
              <p><strong>Ngày cập nhật:</strong> {{ formatDateTime(props.batch.updated_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</AppLayout>
</template>
