<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Eye, Search, Plus, TrendingUp, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface PurchaseReturn {
    id: number;
    return_number: string;
    supplier_name: string;
    return_date: string;
    status: 'pending' | 'approved' | 'completed' | 'rejected';
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
const statusFilter = ref('all');

// --- THÊM PHÂN TRANG ---
const currentPage = ref(1);
const itemsPerPage = ref(10); // Đặt số mục trên mỗi trang
// ------------------------

function showPurchaseReturn(id: number) {
    router.visit(`/admin/purchaseReturn/${id}`);
}

const formatCurrency = computed(() => (amount: string) => {
    const value = parseFloat(amount.replace(/[^0-9.-]+/g, '')) || 0;
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
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

const filteredReturns = computed(() => {
    return props.purchaseReturns.filter(item => {
        const matchesSearch = searchQuery.value === '' ||
            item.return_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            item.supplier_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            item.created_by.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesStatus = statusFilter.value === 'all' || item.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});

// --- PHÂN TRANG: LOGIC MỚI ---
const totalPages = computed(() => Math.ceil(filteredReturns.value.length / itemsPerPage.value));

const paginatedReturns = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredReturns.value.slice(start, end);
});

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const goToPage = (page: number) => { if (page >= 1 && page <= totalPages.value) currentPage.value = page; };

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
// -----------------------------

// Statistics calculations
const stats = computed(() => {
    const total = props.purchaseReturns.length;
    const pending = props.purchaseReturns.filter(r => r.status === 'pending').length;
    const approved = props.purchaseReturns.filter(r => r.status === 'approved').length;
    const completed = props.purchaseReturns.filter(r => r.status === 'completed').length;
    const rejected = props.purchaseReturns.filter(r => r.status === 'rejected').length;

    const totalValue = props.purchaseReturns.reduce((sum, item) => {
        const value = parseFloat(item.total_value_returned.replace(/[^0-9.-]+/g, '')) || 0;
        return sum + value;
    }, 0);

    return { total, pending, approved, completed, rejected, totalValue };
});
</script>

<template>
    <Head title="Quản lý phiếu trả hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-white min-h-screen">
            <div class="px-4 sm:px-6 lg:px-8 py-8">
                <div class="mb-8">
                    <h1 class="text-3xl font-semibold text-gray-800">Quản lý phiếu trả hàng</h1>
                    <p class="mt-1 text-gray-500 text-sm">Theo dõi và quản lý các phiếu trả hàng nhà cung cấp</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <Plus class="text-blue-500 w-6 h-6" />
                            <div>
                                <p class="text-sm text-gray-500">Tổng phiếu</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.total }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <TrendingUp class="text-yellow-500 w-6 h-6" />
                            <div>
                                <p class="text-sm text-gray-500">Chờ duyệt</p>
                                <p class="text-xl font-bold text-yellow-600">{{ stats.pending }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <TrendingUp class="text-green-500 w-6 h-6" />
                            <div>
                                <p class="text-sm text-gray-500">Hoàn tất</p>
                                <p class="text-xl font-bold text-green-600">{{ stats.completed }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <TrendingUp class="text-indigo-500 w-6 h-6" />
                            <div>
                                <p class="text-sm text-gray-500">Tổng giá trị</p>
                                <p class="text-lg font-bold text-gray-800">{{ formatCurrency(stats.totalValue.toString()) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-6 flex flex-col sm:flex-row gap-4">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-3 text-gray-400 w-5 h-5" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:ring-blue-200 shadow-sm"
                            placeholder="Tìm theo mã phiếu, nhà cung cấp, người tạo..."
                        />
                    </div>
                    <div>
                        <select
                            v-model="statusFilter"
                            class="px-4 py-2 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring focus:ring-blue-200"
                        >
                            <option value="all">Tất cả trạng thái</option>
                            <option value="pending">Chờ duyệt</option>
                            <option value="completed">Hoàn tất</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-gray-600 font-semibold">
                                <tr>
                                    <th class="text-left px-6 py-3">Mã phiếu</th>
                                    <th class="text-left px-6 py-3">Nhà cung cấp</th>
                                    <th class="text-left px-6 py-3">Ngày trả</th>
                                    <th class="text-left px-6 py-3">Trạng thái</th>
                                    <th class="text-right px-6 py-3">Tổng tiền</th>
                                    <th class="text-left px-6 py-3">Người tạo</th>
                                    <th class="text-center px-6 py-3">Hành động</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="paginatedReturns.length === 0">
                                    <td colspan="7" class="text-center py-10 text-gray-500">Không có dữ liệu phù hợp</td>
                                </tr>
                                <tr
                                    v-for="item in paginatedReturns"
                                    :key="item.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 font-medium text-blue-700">{{ item.return_number }}</td>
                                    <td class="px-6 py-4">{{ item.supplier_name }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ item.return_date }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
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
                                    <td class="px-6 py-4 text-right text-gray-800 font-semibold">
                                        {{ formatCurrency(item.total_value_returned) }}
                                    </td>
                                    <td class="px-6 py-4">{{ item.created_by }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <button
                                            @click="showPurchaseReturn(item.id)"
                                            class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition text-gray-600 hover:text-black"
                                            title="Xem chi tiết"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                    <span class="text-sm text-gray-500">
                        Hiển thị {{ (currentPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPage * itemsPerPage, filteredReturns.length) }} trong {{ filteredReturns.length }} phiếu trả hàng
                    </span>
                    <div class="flex items-center space-x-2">
                        <button
                            @click="prevPage"
                            :disabled="currentPage === 1"
                            class="p-2 border rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </button>
                        <div class="flex space-x-1">
                            <button
                                v-for="(page, index) in paginationRange"
                                :key="index"
                                @click="typeof page === 'number' ? goToPage(page) : null"
                                :class="{
                                    'bg-blue-600 text-white': currentPage === page,
                                    'hover:bg-gray-200': currentPage !== page,
                                    'text-gray-500 cursor-not-allowed': page === '...',
                                    'bg-white text-gray-700': currentPage !== page && page !== '...'
                                }"
                                class="px-3 py-1 rounded-lg border text-sm"
                                :disabled="page === '...'"
                            >
                                {{ page }}
                            </button>
                        </div>
                        <button
                            @click="nextPage"
                            :disabled="currentPage === totalPages"
                            class="p-2 border rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </button>
                    </div>
                    <span class="text-sm text-gray-500 hidden sm:block">Trang {{ currentPage }} / {{ totalPages }}</span>
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
