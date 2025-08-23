<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Bell, Check, X } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    stockRequests: Array,
});

const breadcrumbs = [
    { title: 'Thông báo nhập hàng', href: route('admin.stock.requests.index') },
];

const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

// Tính số lượng thông báo chưa đọc
const unreadCount = computed(() => {
    return props.stockRequests.filter(request => !request.read_at).length;
});

const total = computed(() => props.stockRequests.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedRequests = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return props.stockRequests.slice(start, start + perPage.value);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const changePerPage = (event) => {
    perPage.value = +event.target.value;
    currentPage.value = 1;
};

const markAsRead = async (notificationId) => {
    try {
        await axios.post(route('admin.stock.requests.read', notificationId));
        router.reload({
            only: ['stockRequests'],
            preserveState: true,
            onSuccess: () => {
                // Xử lý thành công
            }
        });
    } catch (error) {
        console.error('Lỗi khi đánh dấu đã đọc:', error);
        alert('Có lỗi xảy ra khi đánh dấu đã đọc.');
    }
};

const deleteRequest = async (notificationId) => {
    if (confirm('Bạn có chắc chắn muốn xóa yêu cầu này?')) {
        try {
            await axios.delete(route('admin.stock.requests.delete', notificationId));
            router.reload({
                only: ['stockRequests'],
                preserveState: true,
                onSuccess: () => {
                    // Xử lý thành công
                }
            });
        } catch (error) {
            console.error('Lỗi khi xóa yêu cầu:', error);
            alert('Có lỗi xảy ra khi xóa yêu cầu.');
        }
    }
};
</script>

<template>
    <Head title="Thông báo nhập hàng" />
    <AppLayout :breadcrumbs="breadcrumbs" :stockRequests="stockRequests">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-xl font-bold flex items-center gap-2">
                            <Bell class="h-6 w-6 text-gray-700" />
                            Thông báo nhập hàng
                            <span v-if="unreadCount > 0" class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                {{ unreadCount }}
                            </span>
                        </h1>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <table class="w-full border-collapse text-sm table-fixed">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="w-[20%] px-3 py-2 text-left font-semibold">Nhân viên</th>
                                    <th class="w-[45%] px-3 py-2 text-left font-semibold">Thông báo</th>
                                    <th class="w-[15%] px-3 py-2 text-left font-semibold">Thời gian</th>
                                    <th class="w-[20%] px-3 py-2 text-left font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="request in paginatedRequests" :key="request.id" class="border-t hover:bg-gray-50 transition" :class="{ 'bg-blue-50/50': !request.read_at }">
                                    <td class="px-3 py-2 text-left truncate">{{ request.data.cashier_name }}</td>
                                    <td class="px-3 py-2 text-left">
                                        <div class="line-clamp-2 min-h-[48px] overflow-hidden" :class="{ 'font-semibold': !request.read_at }">
                                            {{ request.data.message }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-left">
                                        {{ new Date(request.created_at).toLocaleString() }}
                                    </td>
                                    <td class="flex items-center justify-start space-x-2 p-3">
                                        <button 
                                            @click="markAsRead(request.id)"
                                            class="rounded-md bg-blue-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                                            :disabled="!!request.read_at"
                                            :class="{ 'opacity-50 cursor-not-allowed': !!request.read_at }"
                                            title="Đánh dấu đã đọc"
                                        >
                                            <Check class="h-4 w-4" />
                                        </button>
                                        <button 
                                            @click="deleteRequest(request.id)" 
                                            class="rounded-md bg-red-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                            title="Xóa yêu cầu"
                                        >
                                            <X class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!paginatedRequests.length">
                                    <td colspan="4" class="text-center py-4 text-gray-500">Không có thông báo nào.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-col items-start gap-2 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm">
                            Hiển thị từ <span class="font-medium">{{ (currentPage - 1) * perPage + 1 }}</span> đến
                            <span class="font-medium">{{ Math.min(currentPage * perPage, total) }}</span> trên
                            <span class="font-medium">{{ total }}</span> mục
                        </p>

                        <div class="flex items-center gap-2">
                            <button class="px-2 py-1 text-sm text-gray-600 hover:text-black" :disabled="currentPage === 1"
                                @click="prevPage">
                                ← Trước
                            </button>
                            <template v-for="page in totalPages" :key="page">
                                <button class="px-3 py-1 text-sm rounded"
                                    :class="page === currentPage ? 'bg-gray-300 font-bold' : 'text-gray-500 hover:text-black'"
                                    @click="goToPage(page)">
                                    {{ page }}
                                </button>
                            </template>
                            <button class="px-2 py-1 text-sm text-gray-600 hover:text-black" :disabled="currentPage === totalPages"
                                @click="nextPage">
                                Sau →
                            </button>
                        </div>

                        <div class="flex items-center gap-1">
                            <span class="text-sm">Hiển thị</span>
                            <select v-model="perPage" @change="changePerPage" class="border rounded p-1 text-sm">
                                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <span class="text-sm">kết quả</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>