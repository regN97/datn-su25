<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ArrowDownWideNarrow, ArrowUpNarrowWide, Eye, FileDown, Search } from 'lucide-vue-next'; // Added FileDown icon
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý lô hàng',
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
    manufacturing_date: string | null;
    expiry_date: string | null;
    status: 'active' | 'low_stock' | 'out_of_stock' | 'expired';
    supplier_id: number | null;
    supplier?: Supplier | null;
    received_date: string | null;
    invoice_number: string | null;
    notes: string | null;
};

const page = usePage<SharedData>();

const batches = ref([...(page.props.batches as Batch[])]);
const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

const sortKey = ref<keyof Batch | null>(null);
const sortOrder = ref<'asc' | 'desc'>('asc');

const searchTerm = ref('');
const filterStatus = ref('');

const total = computed(() => filteredAndSortedBatches.value.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const filteredAndSortedBatches = computed(() => {
    let currentBatches = [...batches.value];

    const trimmedSearch = searchTerm.value.trim().toLowerCase();
    if (trimmedSearch) {
        currentBatches = currentBatches.filter(
            (batch) =>
                batch.batch_number.toLowerCase().includes(trimmedSearch) ||
                (batch.supplier?.name && batch.supplier.name.toLowerCase().includes(trimmedSearch)) ||
                (batch.invoice_number && batch.invoice_number.toLowerCase().includes(trimmedSearch)),
        );
    }

    if (filterStatus.value) {
        currentBatches = currentBatches.filter((batch) => batch.status === filterStatus.value);
    }

    if (sortKey.value) {
        currentBatches.sort((a, b) => {
            let valA: any = a[sortKey.value!];
            let valB: any = b[sortKey.value!];

            if (sortKey.value === 'supplier') {
                valA = a.supplier?.name || '';
                valB = b.supplier?.name || '';
            }

            if (typeof valA === 'string' && typeof valB === 'string') {
                return sortOrder.value === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
            } else {
                if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1;
                if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1;
                return 0;
            }
        });
    }
    return currentBatches;
});

const paginatedBatches = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredAndSortedBatches.value.slice(start, start + perPage.value);
});

function goToPage(page: number) {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
}

function prevPage() {
    if (currentPage.value > 1) currentPage.value--;
}

function nextPage() {
    if (currentPage.value < totalPages.value) currentPage.value++;
}

function changePerPage(event: Event) {
    perPage.value = +(event.target as HTMLSelectElement).value;
    currentPage.value = 1;
}

function goToBatchDetails(id: number) {
    router.visit(`/admin/batches/${id}`);
}

function getStatusDisplayName(status: string) {
    switch (status) {
        case 'active':
            return 'Còn hàng';
        case 'low_stock':
            return 'Sắp hết hàng';
        case 'out_of_stock':
            return 'Hết hàng';
        case 'expired':
            return 'Hết hạn';
        default:
            return status;
    }
}

function sortBy(key: keyof Batch | 'supplier') {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortOrder.value = 'asc';
    }
    currentPage.value = 1;
}

function resetPagination() {
    currentPage.value = 1;
}
function exportBatchesToExcel() {
    window.location.href = '/admin/batches';
}
function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    return date.toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        // hour: '2-digit',
        // minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Batches" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border bg-white shadow-lg md:min-h-min"
            >
                <div class="container mx-auto p-6">
                    <div class="mb-6 flex flex-col items-center justify-between gap-4 md:flex-row">
                        <h1 class="text-3xl font-semibold text-gray-800">Quản lý lô hàng</h1>

                        <div class="flex w-full flex-col items-center space-y-3 md:w-auto md:flex-row md:space-y-0 md:space-x-4">
                            <div class="relative w-full md:w-64">
                                <input
                                    type="text"
                                    v-model="searchTerm"
                                    @input="resetPagination"
                                    placeholder="Tìm kiếm mã lô, NCC, hóa đơn..."
                                    class="w-full rounded-lg border border-gray-300 py-2 pr-4 pl-10 shadow-sm transition duration-150 ease-in-out focus:border-blue-500 focus:ring-blue-500"
                                />
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                    <Search class="h-5 w-5 text-gray-400" />
                                </span>
                            </div>

                            <select
                                v-model="filterStatus"
                                @change="resetPagination"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 shadow-sm transition duration-150 ease-in-out focus:border-blue-500 focus:ring-blue-500 md:w-48"
                            >
                                <option value="">Tất cả trạng thái</option>
                                <option value="active">Còn hàng</option>
                                <option value="low_stock">Sắp hết hàng</option>
                                <option value="out_of_stock">Hết hàng</option>
                                <option value="expired">Hết hạn</option>
                            </select>

                            <button
                                @click="exportBatchesToExcel"
                                class="inline-flex w-full items-center justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition duration-150 ease-in-out hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none md:w-auto"
                            >
                                <FileDown class="mr-2 h-5 w-5" />
                                Xuất Excel
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-md">
                        <table class="w-full bg-white text-left">
                            <thead class="border-b border-gray-200 bg-gray-100">
                                <tr>
                                    <th
                                        class="w-[15%] cursor-pointer p-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase select-none"
                                        @click="sortBy('batch_number')"
                                    >
                                        Mã lô hàng
                                        <span v-if="sortKey === 'batch_number'" class="ml-2 inline-flex align-middle">
                                            <ArrowUpNarrowWide v-if="sortOrder === 'asc'" class="h-4 w-4 text-gray-500" />
                                            <ArrowDownWideNarrow v-else class="h-4 w-4 text-gray-500" />
                                        </span>
                                    </th>
                                    <th
                                        class="w-[25%] cursor-pointer p-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase select-none"
                                        @click="sortBy('supplier')"
                                    >
                                        Nhà cung cấp
                                        <span v-if="sortKey === 'supplier'" class="ml-2 inline-flex align-middle">
                                            <ArrowUpNarrowWide v-if="sortOrder === 'asc'" class="h-4 w-4 text-gray-500" />
                                            <ArrowDownWideNarrow v-else class="h-4 w-4 text-gray-500" />
                                        </span>
                                    </th>
                                    <th
                                        class="w-[15%] cursor-pointer p-4 text-center text-xs font-semibold tracking-wider text-gray-600 uppercase select-none"
                                        @click="sortBy('received_date')"
                                    >
                                        Ngày nhận hàng
                                        <span v-if="sortKey === 'received_date'" class="ml-2 inline-flex align-middle">
                                            <ArrowUpNarrowWide v-if="sortOrder === 'asc'" class="h-4 w-4 text-gray-500" />
                                            <ArrowDownWideNarrow v-else class="h-4 w-4 text-gray-500" />
                                        </span>
                                    </th>
                                    <th
                                        class="w-[15%] cursor-pointer p-4 text-center text-xs font-semibold tracking-wider text-gray-600 uppercase select-none"
                                        @click="sortBy('status')"
                                    >
                                        Trạng thái
                                        <span v-if="sortKey === 'status'" class="ml-2 inline-flex align-middle">
                                            <ArrowUpNarrowWide v-if="sortOrder === 'asc'" class="h-4 w-4 text-gray-500" />
                                            <ArrowDownWideNarrow v-else class="h-4 w-4 text-gray-500" />
                                        </span>
                                    </th>
                                    <th class="w-[10%] p-4 text-center text-xs font-semibold tracking-wider text-gray-600 uppercase">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="batch in paginatedBatches"
                                    :key="batch.id"
                                    class="border-b border-gray-100 transition duration-100 ease-in-out hover:bg-gray-50"
                                >
                                    <td class="w-[15%] p-4 text-sm text-gray-700">
                                        {{ batch.batch_number }}
                                    </td>
                                    <td class="w-[25%] p-4 text-sm text-gray-700">
                                        {{ batch.supplier?.name || 'N/A' }}
                                    </td>
                                    <td class="w-[15%] p-4 text-center text-sm text-gray-700">
                                        {{ formatDateTime(batch.received_date) || 'N/A' }}
                                    </td>
                                    <td class="w-[15%] p-4 text-center text-sm">
                                        <span
                                            :class="{
                                                'rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 shadow-sm':
                                                    batch.status === 'active',
                                                'rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800 shadow-sm':
                                                    batch.status === 'low_stock',
                                                'rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 shadow-sm':
                                                    batch.status === 'out_of_stock' || batch.status === 'expired',
                                            }"
                                        >
                                            {{ getStatusDisplayName(batch.status) }}
                                        </span>
                                    </td>
                                    <td class="w-[10%] p-4 text-center text-sm">
                                        <button
                                            class="inline-flex items-center justify-center rounded-md bg-blue-600 p-2 text-white shadow-sm transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                                            @click="goToBatchDetails(batch.id)"
                                            title="Xem chi tiết"
                                        >
                                            <Eye class="h-5 w-5" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="paginatedBatches.length === 0">
                                    <td colspan="6" class="p-4 text-center text-sm text-gray-500">Không có dữ liệu phù hợp với tiêu chí tìm kiếm.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm">
                            Hiển thị kết quả từ
                            <span class="font-semibold">{{ (currentPage - 1) * perPage + 1 }}</span>
                            -
                            <span class="font-semibold">{{ Math.min(currentPage * perPage, total) }}</span>
                            trên tổng <span class="font-semibold">{{ total }}</span>
                        </p>
                        <div class="flex items-center space-x-2">
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700" :disabled="currentPage === 1" @click="prevPage">
                                &larr; Trang trước
                            </button>
                            <template v-for="page in totalPages" :key="page">
                                <button
                                    class="rounded px-3 py-1 text-sm"
                                    :class="page === currentPage ? 'bg-gray-200 font-bold' : 'text-gray-500 hover:text-gray-700'"
                                    @click="goToPage(page)"
                                >
                                    {{ page }}
                                </button>
                            </template>
                            <button
                                class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700"
                                :disabled="currentPage === totalPages"
                                @click="nextPage"
                            >
                                Trang sau &rarr;
                            </button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <p class="text-sm">Hiển thị</p>
                            <select class="rounded border p-1 text-sm" v-model="perPage" @change="changePerPage">
                                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <p class="text-sm">kết quả</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<style lang="css" scoped>
.table-wrapper table {
    min-width: 100%;
    table-layout: auto;
}
</style>
