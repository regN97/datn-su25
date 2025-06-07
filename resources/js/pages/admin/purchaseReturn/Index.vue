<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head , router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Eye } from 'lucide-vue-next';

interface PurchaseReturn {
  id: number;
  return_number: string;
  supplier_name: string;
  return_date: string;
  status: string;
  total_value_returned: string;
  created_by: string;
}

defineProps<{
  purchaseReturns: PurchaseReturn[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Quản lý phiếu trả hàng',
    href: '/admin/purchaseReturn',
  },
];
 function showPurchaseReturn(id: number) {
    router.visit(`/admin/purchaseReturn/${id}`);
}
</script>

<template>
  <Head title="Purchase Return" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- Khoảng cách viền ngoài to hơn -->
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-8 bg-gray-50">
      <!-- Khung trắng chứa nội dung chính -->
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        
        <!-- Tiêu đề và nút -->
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-2xl font-bold border-l-4 border-blue-500 pl-4 text-gray-800">
            Quản lý phiếu trả hàng
          </h1>
          <button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
            Thêm mới
          </button>
        </div>

        <!-- Bảng danh sách -->
        <div class="overflow-x-auto">
          <table class="min-w-full table-auto border-separate border-spacing-y-3">
            <!-- Tiêu đề bảng -->
            <thead class="bg-blue-50 text-gray-700 text-sm font-semibold uppercase">
              <tr>
                <th class="px-6 py-4 text-left">Mã phiếu</th>
                <th class="px-6 py-4 text-left">Nhà cung cấp</th>
                <th class="px-6 py-4 text-left">Ngày trả</th>
                <th class="px-6 py-4 text-left">Trạng thái</th>
                <th class="px-6 py-4 text-right">Tổng tiền</th>
                <th class="px-6 py-4 text-left">Người tạo</th>
                <th class="px-6 py-4 text-center">Hành động</th>
              </tr>
            </thead>

            <!-- Dữ liệu bảng -->
            <tbody>
              <tr
                v-for="item in purchaseReturns"
                :key="item.id"
                class="bg-white hover:bg-gray-100 transition border border-gray-200 rounded-lg shadow-sm"
              >
                <td class="px-6 py-4 rounded-l-lg">{{ item.return_number }}</td>
                <td class="px-6 py-4">{{ item.supplier_name }}</td>
                <td class="px-6 py-4">{{ item.return_date }}</td>
                <td class="px-6 py-4 capitalize">
                  <span
                    :class="{
                      'text-yellow-600': item.status === 'pending',
                      'text-blue-600': item.status === 'approved',
                      'text-green-600': item.status === 'completed',
                      'text-red-600': item.status === 'rejected'
                    }"
                  >
                    {{ item.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right font-semibold text-gray-700">
                  {{ item.total_value_returned }} đ
                </td>
                <td class="px-6 py-4">{{ item.created_by }}</td>
                <td class="px-6 py-4 text-center rounded-r-lg">
                  <button
                    @click="showPurchaseReturn(item.id)"
                    class="text-blue-600 hover:text-blue-800 transition p-1"
                    title="Xem chi tiết"
                  >
                    <Eye class="w-5 h-5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </AppLayout>
</template>


<style scoped>
</style>
