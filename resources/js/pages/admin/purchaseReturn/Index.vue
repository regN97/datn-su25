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
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Phần khung chính trắng, border giống bên sản phẩm -->
      <div class="border-sidebar-border/70 relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
        <div class="container mx-auto p-6">
          
          <!-- Tiêu đề và nút -->
          <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">Danh sách phiếu trả hàng</h1>
            <button class="rounded-3xl bg-green-600 px-6 py-2 text-white hover:bg-green-700">
              Thêm mới
            </button>
          </div>

          <!-- Bảng -->
          <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
            <table class="min-w-full table-auto border-separate border-spacing-y-3">
              <thead class="bg-gray-200 text-gray-700 text-sm font-semibold uppercase">
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
                      {{ 
                        item.status === 'pending' ? 'Chờ duyệt' :
                        item.status === 'approved' ? 'Đã duyệt' :
                        item.status === 'completed' ? 'Hoàn tất' :
                        item.status === 'rejected' ? 'Từ chối' :
                        item.status 
                      }}
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
                <tr v-if="purchaseReturns.length === 0">
                  <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                    Không có dữ liệu
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>
