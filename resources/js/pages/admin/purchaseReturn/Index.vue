<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, ChevronLeft, ChevronRight, Eye, Plus, Search, TrendingUp, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface PurchaseReturn {
    id: number;
    return_number: string;
    supplier_name: string;
    return_date: string;
    status: 'pending' | 'approved' | 'completed' | 'rejected';
    payment_status: 'unpaid' | 'paid';
    total_value_returned: string;
    created_by: string;
}

const props = defineProps<{
    purchaseReturns: PurchaseReturn[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý phiếu trả hàng',
        href: '/admin/purchaseReturn',
    },
];

const searchQuery = ref('');
const paymentStatusFilter = ref('all');

const currentPage = ref(1);
const itemsPerPage = ref(10);

function showPurchaseReturn(id: number) {
    router.visit(`/admin/purchaseReturn/${id}`);
}

const formatCurrency = computed(() => (amount: string) => {
    const value = parseFloat(amount.replace(/[^0-9.-]+/g, '')) || 0;
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(value);
});

const getStatusDisplayName = computed(() => (status: string) => {
    switch (status) {
        case 'pending':
            return 'Chờ duyệt';
        case 'approved':
            return 'Đã duyệt';
        case 'completed':
            return 'Hoàn tất';
        case 'rejected':
            return 'Từ chối';
        default:
            return 'Không rõ';
    }
});

const getPaymentStatusDisplayName = computed(() => (paymentStatus: 'unpaid' | 'paid') => {
    switch (paymentStatus) {
        case 'unpaid':
            return 'Chưa nhận hoàn tiền';
        case 'paid':
            return 'Đã nhận hoàn tiền';
        default:
            return 'Không rõ';
    }
});

const filteredReturns = computed(() => {
    return props.purchaseReturns.filter((item) => {
        const matchesSearch =
            searchQuery.value === '' ||
            item.return_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            item.supplier_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            item.created_by.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesPaymentStatus = paymentStatusFilter.value === 'all' || item.payment_status === paymentStatusFilter.value;

        return matchesSearch && matchesPaymentStatus;
    });
});

const totalPages = computed(() => Math.ceil(filteredReturns.value.length / itemsPerPage.value));

const paginatedReturns = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredReturns.value.slice(start, end);
});

const prevPage = () => {
    if (currentPage.value > 1) currentPage.value--;
};
const nextPage = () => {
    if (currentPage.value < totalPages.value) currentPage.value++;
};
const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page;
};

const paginationRange = computed(() => {
    const range = [];
    const total = totalPages.value;
    const current = currentPage.value;
    const delta = 2;

    if (total <= 5) {
        for (let i = 1; i <= total; i++) range.push(i);
    } else {
        if (current > delta + 1) range.push(1, '...');
        for (let i = Math.max(1, current - delta); i <= Math.min(total, current + delta); i++) range.push(i);
        if (current < total - delta) range.push('...', total);
    }
    return range;
});

const stats = computed(() => {
    const total = props.purchaseReturns.length;
    const pending = props.purchaseReturns.filter((r) => r.status === 'pending').length;
    const approved = props.purchaseReturns.filter((r) => r.status === 'approved').length;
    const completed = props.purchaseReturns.filter((r) => r.status === 'completed').length;
    const rejected = props.purchaseReturns.filter((r) => r.status === 'rejected').length;

    const paid = props.purchaseReturns.filter((r) => r.payment_status === 'paid').length;
    const unpaid = props.purchaseReturns.filter((r) => r.payment_status === 'unpaid').length;

    const totalValue = props.purchaseReturns.reduce((sum, item) => {
        const value = parseFloat(item.total_value_returned.replace(/[^0-9.-]+/g, '')) || 0;
        return sum + value;
    }, 0);

    return { total, pending, approved, completed, rejected, paid, unpaid, totalValue };
});
</script>
<template>
    <Head title="Quản lý phiếu trả hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white">
            <div class="px-4 py-8 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <h1 class="text-3xl font-semibold text-gray-800">Quản lý phiếu trả hàng</h1>
                    <p class="mt-1 text-sm text-gray-500">Theo dõi và quản lý các phiếu trả hàng nhà cung cấp</p>
                </div>

                <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <Plus class="h-6 w-6 text-blue-500" />
                            <div>
                                <p class="text-sm text-gray-500">Tổng phiếu</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.total }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <CheckCircle2 class="h-6 w-6 text-green-500" />
                            <div>
                                <p class="text-sm text-gray-500">Đã nhận hoàn tiền</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.paid }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <XCircle class="h-6 w-6 text-red-500" />
                            <div>
                                <p class="text-sm text-gray-500">Chưa nhận hoàn tiền</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.unpaid }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <TrendingUp class="h-6 w-6 text-indigo-500" />
                            <div>
                                <p class="text-sm text-gray-500">Tổng giá trị</p>
                                <p class="text-lg font-bold text-gray-800">{{ formatCurrency(stats.totalValue.toString()) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-6 flex flex-col gap-4 sm:flex-row">
                    <div class="relative flex-1">
                        <Search class="absolute top-3 left-3 h-5 w-5 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="w-full rounded-xl border border-gray-300 py-2 pr-4 pl-10 shadow-sm focus:ring focus:ring-blue-200 focus:outline-none"
                            placeholder="Tìm theo mã phiếu, nhà cung cấp, người tạo..."
                        />
                    </div>
                    <div>
                        <select
                            v-model="paymentStatusFilter"
                            class="rounded-xl border border-gray-300 px-4 py-2 shadow-sm focus:ring focus:ring-blue-200 focus:outline-none"
                        >
                            <option value="all">Tất cả trạng thái thanh toán</option>
                            <option value="paid">Đã nhận hoàn tiền</option>
                            <option value="unpaid">Chưa nhận hoàn tiền</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 font-semibold text-gray-600">
                                <tr>
                                    <th class="px-6 py-3 text-left">Mã phiếu</th>
                                    <th class="px-6 py-3 text-left">Nhà cung cấp</th>
                                    <th class="px-6 py-3 text-left">Ngày trả</th>
                                    <th class="px-6 py-3 text-left">Trạng thái</th>
                                    <th class="px-6 py-3 text-left">Thanh toán</th>
                                    <th class="px-6 py-3 text-right">Tổng tiền</th>
                                    <th class="px-6 py-3 text-left">Người tạo</th>
                                    <th class="px-6 py-3 text-center">Hành động</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="paginatedReturns.length === 0">
                                    <td colspan="8" class="py-10 text-center text-gray-500">Không có dữ liệu phù hợp</td>
                                </tr>
                                <tr v-for="item in paginatedReturns" :key="item.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-blue-700">{{ item.return_number }}</td>
                                    <td class="px-6 py-4">{{ item.supplier_name }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ item.return_date }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
                                            :class="{
                                                'bg-yellow-50 text-yellow-700': item.status === 'pending',
                                                'bg-blue-50 text-blue-700': item.status === 'approved',
                                                'bg-green-50 text-green-700': item.status === 'completed',
                                                'bg-red-50 text-red-700': item.status === 'rejected',
                                            }"
                                        >
                                            ● {{ getStatusDisplayName(item.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
                                            :class="{
                                                'bg-red-50 text-red-700': item.payment_status === 'unpaid',
                                                'bg-green-50 text-green-700': item.payment_status === 'paid',
                                            }"
                                        >
                                            ● {{ getPaymentStatusDisplayName(item.payment_status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-800">
                                        {{ formatCurrency(item.total_value_returned) }}
                                    </td>
                                    <td class="px-6 py-4">{{ item.created_by }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <button
                                            @click="showPurchaseReturn(item.id)"
                                            class="rounded-lg bg-gray-100 p-2 text-gray-600 transition hover:bg-gray-200 hover:text-black"
                                            title="Xem chi tiết"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex flex-col items-center justify-between space-y-4 sm:flex-row sm:space-y-0">
                    <span class="text-sm text-gray-500">
                        Hiển thị {{ (currentPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPage * itemsPerPage, filteredReturns.length) }} trong
                        {{ filteredReturns.length }} phiếu trả hàng
                    </span>
                    <div class="flex items-center space-x-2">
                        <button
                            @click="prevPage"
                            :disabled="currentPage === 1"
                            class="rounded-lg border p-2 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                        <div class="flex space-x-1">
                            <button
                                v-for="(page, index) in paginationRange"
                                :key="index"
                                @click="typeof page === 'number' ? goToPage(page) : null"
                                :class="{
                                    'bg-blue-600 text-white': currentPage === page,
                                    'hover:bg-gray-200': currentPage !== page,
                                    'cursor-not-allowed text-gray-500': page === '...',
                                    'bg-white text-gray-700': currentPage !== page && page !== '...',
                                }"
                                class="rounded-lg border px-3 py-1 text-sm"
                                :disabled="page === '...'"
                            >
                                {{ page }}
                            </button>
                        </div>
                        <button
                            @click="nextPage"
                            :disabled="currentPage === totalPages"
                            class="rounded-lg border p-2 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                    <span class="hidden text-sm text-gray-500 sm:block">Trang {{ currentPage }} / {{ totalPages }}</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
}
</style>
