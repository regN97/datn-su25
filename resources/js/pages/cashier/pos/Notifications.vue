<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
  recentActivities: Array,
  quickStats: Object,
  importantNotifications: Array,
  user: Object,
  recentReturns: Array, // Prop để nhận danh sách trả hàng
});
</script>

<template>
  <Head title="Thông báo Thu ngân" />
  <CashierLayout>
    <div class="container mx-auto px-6 sm:px-10 lg:px-16 py-8 max-w-[85rem]">
      <div class="flex flex-col lg:flex-row gap-8">
        <Sidebar />
        <main class="flex-1">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Hoạt động gần đây -->
            <div class="lg:col-span-2">
              <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-gray-700 text-white font-semibold text-xl px-6 py-4">
                  Hoạt động gần đây
                </div>
                <div class="p-6">
                  <div id="activity-list"
                    class="max-h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                    <div v-for="(activity, index) in recentActivities" :key="activity.time + '-' + index"
                      class="pb-4 border-b border-gray-100 last:border-b-0">
                      <div class="text-gray-600 text-sm font-medium" v-html="activity.description"></div>
                      <div class="text-xs text-gray-400 text-right">{{ activity.time }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Thống kê nhanh + Thông báo -->
            <div class="lg:col-span-1 space-y-6">
              <!-- Thống kê nhanh -->
              <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-gray-700 text-white font-semibold text-xl px-6 py-4">
                  Thống kê nhanh
                </div>
                <div class="p-6 space-y-4">
                  <p class="text-gray-600 text-sm flex justify-between items-center">
                    <strong class="font-semibold">Tổng giao dịch hôm nay:</strong>
                    <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded">
                      {{ quickStats.totalTransactions }}
                    </span>
                  </p>
                  <p class="text-gray-600 text-sm flex justify-between items-center">
                    <strong class="font-semibold">Tổng doanh thu:</strong>
                    <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded">
                      {{ quickStats.totalRevenue }} VNĐ
                    </span>
                  </p>
                  <p class="text-gray-600 text-sm flex justify-between items-center">
                    <strong class="font-semibold">Trả hàng/đổi hàng:</strong>
                    <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded">
                      {{ quickStats.totalReturns }}
                    </span>
                  </p>
                  <p class="text-gray-600 text-sm">
                    <strong class="font-semibold">Sản phẩm bán chạy:</strong>
                    {{ quickStats.bestSellingProduct }}
                  </p>
                </div>
              </div>

              <!-- Thông báo quan trọng -->
              <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-gray-700 text-white font-semibold text-xl px-6 py-4">
                  Thông báo quan trọng
                </div>
                <div class="p-6">
                  <div
                    :class="{ 'max-h-[28rem] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100': importantNotifications.length >= 10 }">
                    <ul class="space-y-4">
                      <li v-for="(notification, index) in importantNotifications" :key="notification.time + '-' + index"
                        class="pb-4 border-b border-gray-100 last:border-b-0">
                        <div class="flex items-center">
                          <span v-if="notification.isNew"
                            class="bg-red-100 text-red-600 text-xs font-medium px-1.5 py-0.5 rounded mr-2">
                            Mới
                          </span>
                          <span class="text-gray-600 text-sm font-medium">{{ notification.message }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ notification.details }}</div>
                        <div class="text-xs text-gray-400 text-right mt-1">{{ notification.time }}</div>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  </CashierLayout>
</template>

<style scoped>
button {
  transition: all 0.2s ease-in-out;
}

.shadow-md:hover {
  transform: translateY(-1px);
}

h1,
h2,
h3,
h4,
h5,
h6 {
  font-family: 'Inter', sans-serif;
}

.scrollbar-thin::-webkit-scrollbar {
  width: 8px;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: #d1d5db;
  border-radius: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background-color: #f3f4f6;
}
</style>