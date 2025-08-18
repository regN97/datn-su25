<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    recentActivities: Array,
    quickStats: Object,
    importantNotifications: Array,
    user: Object,
});
</script>

<template>
    <Head title="Thông báo Thu ngân" />
    <CashierLayout>
        <div class="container mx-auto max-w-[85rem] px-6 py-8 sm:px-10 lg:px-16">
            <div class="flex flex-col gap-8 lg:flex-row">
                <Sidebar />
                <main class="flex-1">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div class="lg:col-span-2">
                            <div class="overflow-hidden rounded-lg bg-white shadow-md">
                                <div class="bg-gray-700 px-6 py-4 text-xl font-semibold text-white">Hoạt động gần đây</div>
                                <div class="p-6">
                                    <div
                                        id="activity-list"
                                        class="scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 max-h-96 overflow-y-auto"
                                    >
                                        <div
                                            v-for="activity in recentActivities"
                                            :key="activity.time"
                                            class="border-b border-gray-100 pb-4 last:border-b-0"
                                        >
                                            <div class="text-sm font-medium text-gray-600" v-html="activity.description"></div>
                                            <div class="text-right text-xs text-gray-400">{{ activity.time }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 lg:col-span-1">
                            <div class="overflow-hidden rounded-lg bg-white shadow-md">
                                <div class="bg-gray-700 px-6 py-4 text-xl font-semibold text-white">Thống kê nhanh</div>
                                <div class="space-y-4 p-6">
                                    <p class="flex items-center justify-between text-sm text-gray-600">
                                        <strong class="font-semibold">Tổng giao dịch hôm nay:</strong>
                                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{
                                            quickStats.totalTransactions
                                        }}</span>
                                    </p>
                                    <p class="flex items-center justify-between text-sm text-gray-600">
                                        <strong class="font-semibold">Tổng doanh thu:</strong>
                                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700"
                                            >{{ quickStats.totalRevenue }} VNĐ</span
                                        >
                                    </p>
                                    <p class="flex items-center justify-between text-sm text-gray-600">
                                        <strong class="font-semibold">Trả hàng/đổi hàng:</strong>
                                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{
                                            quickStats.totalReturns
                                        }}</span>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <strong class="font-semibold">Sản phẩm bán chạy:</strong> {{ quickStats.bestSellingProduct }}
                                    </p>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-lg bg-white shadow-md">
                                <div class="bg-gray-700 px-6 py-4 text-xl font-semibold text-white">Thông báo quan trọng</div>
                                <div class="p-6">
                                    <div
                                        :class="{
                                            'scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 max-h-[28rem] overflow-y-auto':
                                                importantNotifications.length >= 10,
                                        }"
                                    >
                                        <ul class="space-y-4">
                                            <li
                                                v-for="notification in importantNotifications"
                                                :key="notification.time"
                                                class="border-b border-gray-100 pb-4 last:border-b-0"
                                            >
                                                <div class="flex items-center">
                                                    <span
                                                        v-if="notification.isNew"
                                                        class="mr-2 rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-600"
                                                        >Mới</span
                                                    >
                                                    <span class="text-sm font-medium text-gray-600">{{ notification.message }}</span>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">{{ notification.details }}</div>
                                                <div class="mt-1 text-right text-xs text-gray-400">{{ notification.time }}</div>
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
