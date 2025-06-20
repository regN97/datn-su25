<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
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
                        <button class="rounded-3xl bg-green-600 px-6 py-2 text-white hover:bg-green-700">Thêm mới</button>
                    </div>

                    <!-- Bảng -->
    <div class="overflow-x-auto rounded-lg bg-white shadow">
  <table class="min-w-full text-sm text-gray-700">
    <thead class="bg-gray-100 font-semibold text-left">
      <tr>
        <th class="px-4 py-3">Mã phiếu</th>
        <th class="px-4 py-3">Nhà cung cấp</th>
        <th class="px-4 py-3">Ngày trả</th>
        <th class="px-4 py-3">Trạng thái</th>
        <th class="px-4 py-3 text-right">Tổng tiền</th>
        <th class="px-4 py-3">Người tạo</th>
        <th class="px-4 py-3 text-center">Hành động</th>
      </tr>
    </thead>
    <tbody>
      <tr
        v-for="item in purchaseReturns"
        :key="item.id"
        class="border-b hover:bg-gray-50"
      >
        <td class="px-4 py-3">{{ item.return_number }}</td>
        <td class="px-4 py-3">{{ item.supplier_name }}</td>
        <td class="px-4 py-3">{{ item.return_date }}</td>
        <td class="px-4 py-3">
          <span
            class="rounded-full px-3 py-1 text-xs font-semibold"
            :class="{
              'bg-yellow-100 text-yellow-700': item.status === 'pending',
              'bg-blue-100 text-blue-700': item.status === 'approved',
              'bg-green-100 text-green-700': item.status === 'completed',
              'bg-red-100 text-red-700': item.status === 'rejected',
            }"
          >
            {{
              item.status === 'pending'
                ? 'Chờ duyệt'
                : item.status === 'approved'
                ? 'Đã duyệt'
                : item.status === 'completed'
                ? 'Hoàn tất'
                : item.status === 'rejected'
                ? 'Từ chối'
                : item.status
            }}
          </span>
        </td>
        <td class="px-4 py-3 text-right font-medium text-gray-800">
          {{ item.total_value_returned }} đ
        </td>
        <td class="px-4 py-3">{{ item.created_by }}</td>
        <td class="px-4 py-3 text-center space-x-1">
          <button @click="showPurchaseReturn(item.id)" class="inline-flex items-center justify-center rounded bg-gray-200 p-2 hover:bg-gray-300" title="Xem">
            <Eye class="h-4 w-4 text-gray-700" />
          </button>
          <!-- Nếu có chức năng sửa và xóa, thêm vào như sau: -->
          
         
    
        </td>
      </tr>
      <tr v-if="purchaseReturns.length === 0">
        <td colspan="7" class="px-4 py-3 text-center text-gray-500">Không có dữ liệu</td>
      </tr>
    </tbody>
  </table>
</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
