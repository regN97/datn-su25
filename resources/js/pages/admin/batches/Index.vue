<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Eye, FileDown, PackagePlus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý đơn nhập hàng',
        href: '/admin/batches',
    },
];

type Supplier = {
    id: number;
    name: string;
};

type Batch = {
    id: number;
    batch_number: string;
    purchase_order_id: number;
    supplier_id: number | null;
    supplier?: Supplier | null;
    received_date: string;
    invoice_number: string | null;
    total_amount: number;
    payment_status: 'unpaid' | 'partially_paid' | 'paid';
    paid_amount: number;
    receipt_status: 'pending' | 'partially_received' | 'completed' | 'cancelled';
    notes: string | null;
    created_by: number;
    updated_by: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
};

const page = usePage<SharedData>();

const batches = ref([...(page.props.batches as Batch[])]);

const perPage = ref(10);
const currentPage = ref(1);

const searchTerm = ref('');
const filterStatus = ref('');
const filterPaymentStatus = ref('');
const filterReceiptStatus = ref('');
const filterStartDate = ref('');
const filterEndDate = ref('');
const filterMinAmount = ref<number | null>(null);
const filterMaxAmount = ref<number | null>(null);
const filterInvoiceNumber = ref('');

const isFiltersCollapsed = ref(true);

const filteredAndSortedBatches = computed(() => {
    let currentBatches = [...batches.value];

    const trimmedSearch = searchTerm.value.trim().toLowerCase();
    if (trimmedSearch) {
        currentBatches = currentBatches.filter(
            (batch) =>
                batch.batch_number.toLowerCase().includes(trimmedSearch) ||
                (batch.supplier?.name && batch.supplier.name.toLowerCase().includes(trimmedSearch)) ||
                (batch.notes && batch.notes.toLowerCase().includes(trimmedSearch)),
        );
    }

    const trimmedInvoiceSearch = filterInvoiceNumber.value.trim().toLowerCase();
    if (trimmedInvoiceSearch) {
        currentBatches = currentBatches.filter((batch) => batch.invoice_number && batch.invoice_number.toLowerCase().includes(trimmedInvoiceSearch));
    }

    if (filterPaymentStatus.value) {
        currentBatches = currentBatches.filter((batch) => batch.payment_status === filterPaymentStatus.value);
    }

    if (filterReceiptStatus.value) {
        currentBatches = currentBatches.filter((batch) => batch.receipt_status === filterReceiptStatus.value);
    }

    if (filterStartDate.value) {
        const start = new Date(filterStartDate.value + 'T00:00:00');
        currentBatches = currentBatches.filter((batch) => {
            const batchDate = new Date(batch.received_date);
            return batchDate >= start;
        });
    }
    if (filterEndDate.value) {
        const end = new Date(filterEndDate.value + 'T23:59:59');
        currentBatches = currentBatches.filter((batch) => {
            const batchDate = new Date(batch.received_date);
            return batchDate <= end;
        });
    }

    if (filterMinAmount.value !== null && !isNaN(filterMinAmount.value)) {
        currentBatches = currentBatches.filter((batch) => batch.total_amount >= filterMinAmount.value!);
    }
    if (filterMaxAmount.value !== null && !isNaN(filterMaxAmount.value)) {
        currentBatches = currentBatches.filter((batch) => batch.total_amount <= filterMaxAmount.value!);
    }

    return currentBatches;
});

const paginatedBatches = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredAndSortedBatches.value.slice(start, start + perPage.value);
});

function goToBatchDetails(id: number) {
    router.visit(`/admin/batches/${id}`);
}

function resetPagination() {
    currentPage.value = 1;
}

function resetAllFilters() {
    searchTerm.value = '';
    filterPaymentStatus.value = '';
    filterReceiptStatus.value = '';
    filterStartDate.value = '';
    filterEndDate.value = '';
    filterMinAmount.value = null;
    filterMaxAmount.value = null;
    filterInvoiceNumber.value = '';
    resetPagination();
}

function exportBatchesToExcel() {
    window.location.href = '/admin/batches/export';
}

function getPaymentStatusDisplayName(status: 'unpaid' | 'partially_paid' | 'paid') {
    switch (status) {
        case 'unpaid':
            return 'Chưa thanh toán';
        case 'partially_paid':
            return 'Đã thanh toán một phần';
        case 'paid':
            return 'Đã thanh toán';
        default:
            return 'N/A';
    }
}

function getReceiptStatusDisplayName(status: 'pending' | 'partially_received' | 'completed' | 'cancelled') {
    switch (status) {
        case 'pending':
            return 'Đang chờ';
        case 'partially_received':
            return 'Đã nhận một phần';
        case 'completed':
            return 'Đã nhận đủ';
        case 'cancelled':
            return 'Đã hủy';
        default:
            return 'N/A';
    }
}

function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    return date.toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        minimumFractionDigits: 0,
    }).format(amount);
}
</script>

<template>

    <Head title="Quản lý đơn nhập hàng" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border bg-white shadow-lg md:min-h-min">
                <div class="container mx-auto p-6">
                    <div class="mb-6 flex flex-col items-center justify-between gap-4 md:flex-row">
                        <h1 class="text-3xl font-semibold text-gray-800">Quản lý đơn nhập hàng</h1>
                        <div class="ml-auto flex gap-4">
                            <button
                                class="inline-flex items-center rounded-3xl bg-green-500 px-4 py-2 text-white hover:bg-green-600">
                                <PackagePlus class="h-5 w-5" />
                                <span class="ml-2 hidden md:inline">Tạo Đơn Nhập Hàng</span>
                            </button>
                            <button class="rounded-3xl bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">Thùng
                                rác</button>
                        </div>
                    </div>

                    <div class="mb-6 flex flex-wrap items-center gap-4">
                        <div
                            class="flex w-full flex-col items-center space-y-3 md:w-auto md:flex-row md:space-y-0 md:space-x-4">
                            <div class="relative w-full md:w-64">
                                <input type="text" v-model="searchTerm" @input="resetPagination"
                                    placeholder="Tìm kiếm mã lô, NCC, hóa đơn..."
                                    class="w-full rounded-lg border border-gray-300 py-2 pr-4 pl-10 shadow-sm transition duration-150 ease-in-out focus:border-blue-500 focus:ring-blue-500" />
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                    <Search class="h-5 w-5 text-gray-400" />
                                </span>
                            </div>
                            <select v-model="filterStatus" @change="resetPagination"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 shadow-sm transition duration-150 ease-in-out focus:border-blue-500 focus:ring-blue-500 md:w-48">
                                <option value="">Tất cả trạng thái</option>
                                <option value="active">Còn hàng</option>
                                <option value="low_stock">Sắp hết hàng</option>
                                <option value="out_of_stock">Hết hàng</option>
                                <option value="expired">Hết hạn</option>
                            </select>

                            <button @click="exportBatchesToExcel"
                                class="inline-flex w-full items-center justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition duration-150 ease-in-out hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none md:w-auto">
                                <FileDown class="mr-2 h-5 w-5" />
                                Xuất Excel
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 overflow-x-auto rounded-lg border border-gray-200 shadow-md">
                        <table class="w-full bg-white text-left">
                            <thead class="border-b border-gray-200 bg-gray-100">
                                <tr>
                                    <th
                                        class="w-[10%] p-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase select-none">
                                        Mã lô hàng
                                    </th>
                                    <th
                                        class="w-[15%] p-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase select-none">
                                        Nhà cung cấp
                                    </th>
                                    <th
                                        class="w-[15%] p-4 text-center text-xs font-semibold tracking-wider text-gray-600 uppercase select-none">
                                        Ngày nhận hàng
                                    </th>
                                    <th
                                        class="w-[10%] p-4 text-right text-xs font-semibold tracking-wider text-gray-600 uppercase select-none">
                                        Tổng tiền
                                    </th>
                                    <th
                                        class="w-[15%] p-4 text-center text-xs font-semibold tracking-wider text-gray-600 uppercase select-none">
                                        Trạng thái thanh toán
                                    </th>
                                    <th
                                        class="w-[10%] p-4 text-center text-xs font-semibold tracking-wider text-gray-600 uppercase select-none">
                                        Trạng thái nhận hàng
                                    </th>
                                    <th
                                        class="w-[10%] p-4 text-center text-xs font-semibold tracking-wider text-gray-600 uppercase">
                                        Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="batch in paginatedBatches" :key="batch.id"
                                    class="border-b border-gray-100 transition duration-100 ease-in-out hover:bg-gray-50">
                                    <td class="w-[10%] p-4 text-sm text-gray-700">
                                        {{ batch.batch_number }}
                                    </td>
                                    <td class="w-[25%] p-4 text-sm text-gray-700">
                                        {{ batch.supplier?.name || 'N/A' }}
                                    </td>
                                    <td class="w-[15%] p-4 text-center text-sm text-gray-700">
                                        {{ formatDateTime(batch.received_date) }}
                                    </td>
                                    <td class="w-[10%] p-4 text-right text-sm text-gray-700">
                                       {{  formatCurrency(batch.total_amount) }}
                                    </td>
                                    <td class="w-[15%] p-4 text-center text-sm">
                                        <span :class="{
                                            'rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 shadow-sm':
                                                batch.payment_status === 'paid',
                                            'rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800 shadow-sm':
                                                batch.payment_status === 'partially_paid',
                                            'rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 shadow-sm':
                                                batch.payment_status === 'unpaid',
                                        }">
                                            {{ getPaymentStatusDisplayName(batch.payment_status) }}
                                        </span>
                                    </td>
                                    <td class="w-[15%] p-4 text-center text-sm">
                                        <span :class="{
                                            'rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 shadow-sm':
                                                batch.receipt_status === 'completed',
                                            'rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800 shadow-sm':
                                                batch.receipt_status === 'partially_received',
                                            'rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-800 shadow-sm':
                                                batch.receipt_status === 'pending',
                                            'rounded-full bg-red-200 px-3 py-1 text-xs font-semibold text-red-900 shadow-sm':
                                                batch.receipt_status === 'cancelled',
                                        }">
                                            {{ getReceiptStatusDisplayName(batch.receipt_status) }}
                                        </span>
                                    </td>
                                    <td class="w-[5%] p-4 text-center text-sm">
                                        <button
                                            class="inline-flex items-center justify-center rounded-md bg-blue-600 p-2 text-white shadow-sm transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                                            @click="goToBatchDetails(batch.id)" title="Xem chi tiết">
                                            <Eye class="h-5 w-5" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="paginatedBatches.length === 0">
                                    <td colspan="7" class="p-4 text-center text-sm text-gray-500">Không có dữ liệu phù
                                        hợp với tiêu chí tìm kiếm.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <Pagination :total-items="filteredAndSortedBatches.length" :items-per-page="perPage"
                        :current-page="currentPage" @update:currentPage="currentPage = $event"
                        @update:itemsPerPage="perPage = $event" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
