<script setup lang="ts">
import DeleteModal from '@/components/DeleteModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PackagePlus, Pencil, Trash2, ArrowUpNarrowWide, ArrowDownWideNarrow, Search, FileDown } from 'lucide-vue-next'; // Added FileDown icon
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý lô hàng',
        href: '/admin/product-batches',
    },
];
type Supplier = {
    id: number;
    name: string;
};
type Batch = {
    id: number;
    batch_number: string;
    product_id: number;
    manufacturing_date: string | null;
    expiry_date: string | null;
    purchase_price: number;
    initial_quantity: number;
    current_quantity: number;
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

        currentBatches = currentBatches.filter(batch =>
            batch.batch_number.toLowerCase().includes(trimmedSearch) ||
            (batch.supplier?.name && batch.supplier.name.toLowerCase().includes(trimmedSearch)) ||
            (batch.invoice_number && batch.invoice_number.toLowerCase().includes(trimmedSearch))
        );

    }

    if (filterStatus.value) {
        currentBatches = currentBatches.filter(batch => batch.status === filterStatus.value);
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
    router.visit(`/admin/product-batches/${id}`);
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
    window.location.href = '/admin/product-batches';
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
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min bg-white shadow-lg">
                <div class="container mx-auto p-6">
                    <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
                        <h1 class="text-3xl font-semibold text-gray-800">
                            Quản lý lô hàng
                        </h1>

                        <div
                            class="flex flex-col md:flex-row items-center space-y-3 md:space-y-0 md:space-x-4 w-full md:w-auto">
                            <div class="relative w-full md:w-64">
                                <input type="text" v-model="searchTerm" @input="resetPagination"
                                    placeholder="Tìm kiếm mã lô, NCC, hóa đơn..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out shadow-sm" />
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                    <Search class="h-5 w-5 text-gray-400" />
                                </span>
                            </div>

                            <select v-model="filterStatus" @change="resetPagination"
                                class="w-full md:w-48 py-2 px-3 border border-gray-300 bg-white rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                                <option value="">Tất cả trạng thái</option>
                                <option value="active">Còn hàng</option>
                                <option value="low_stock">Sắp hết hàng</option>
                                <option value="out_of_stock">Hết hàng</option>
                                <option value="expired">Hết hạn</option>
                            </select>

                            <button @click="exportBatchesToExcel"
                                class="inline-flex items-center justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition duration-150 ease-in-out hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none shadow-sm w-full md:w-auto">
                                <FileDown class="h-5 w-5 mr-2" />
                                Xuất Excel
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg shadow-md border border-gray-200">
                        <table class="w-full text-left bg-white">
                            <thead class="bg-gray-100 border-b border-gray-200">
                                <tr>
                                    <th class="w-[15%] p-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer select-none"
                                        @click="sortBy('batch_number')">
                                        Mã lô hàng
                                        <span v-if="sortKey === 'batch_number'" class="inline-flex align-middle ml-2">
                                            <ArrowUpNarrowWide v-if="sortOrder === 'asc'"
                                                class="h-4 w-4 text-gray-500" />
                                            <ArrowDownWideNarrow v-else class="h-4 w-4 text-gray-500" />
                                        </span>
                                    </th>
                                    <th class="w-[25%] p-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer select-none"
                                        @click="sortBy('supplier')">
                                        Nhà cung cấp
                                        <span v-if="sortKey === 'supplier'" class="inline-flex align-middle ml-2">
                                            <ArrowUpNarrowWide v-if="sortOrder === 'asc'"
                                                class="h-4 w-4 text-gray-500" />
                                            <ArrowDownWideNarrow v-else class="h-4 w-4 text-gray-500" />
                                        </span>
                                    </th>
                                    <th class="w-[15%] p-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer select-none"
                                        @click="sortBy('received_date')">
                                        Ngày nhận hàng
                                        <span v-if="sortKey === 'received_date'" class="inline-flex align-middle ml-2">
                                            <ArrowUpNarrowWide v-if="sortOrder === 'asc'"
                                                class="h-4 w-4 text-gray-500" />
                                            <ArrowDownWideNarrow v-else class="h-4 w-4 text-gray-500" />
                                        </span>
                                    </th>
                                    <th class="w-[15%] p-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer select-none"
                                        @click="sortBy('status')">
                                        Trạng thái
                                        <span v-if="sortKey === 'status'" class="inline-flex align-middle ml-2">
                                            <ArrowUpNarrowWide v-if="sortOrder === 'asc'"
                                                class="h-4 w-4 text-gray-500" />
                                            <ArrowDownWideNarrow v-else class="h-4 w-4 text-gray-500" />
                                        </span>
                                    </th>
                                    <th
                                        class="w-[10%] p-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(batch) in paginatedBatches" :key="batch.id"
                                    class="border-b border-gray-100 hover:bg-gray-50 transition duration-100 ease-in-out">
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
                                        <span :class="{
                                            'bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold shadow-sm': batch.status === 'active',
                                            'bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold shadow-sm': batch.status === 'low_stock',
                                            'bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold shadow-sm': batch.status === 'out_of_stock' || batch.status === 'expired',
                                        }">
                                            {{ getStatusDisplayName(batch.status) }}
                                        </span>
                                    </td>
                                    <td class="w-[10%] p-4 text-center text-sm">
                                        <button
                                            class="inline-flex items-center justify-center rounded-md bg-blue-600 p-2 text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none shadow-sm"
                                            @click="goToBatchDetails(batch.id)" title="Xem chi tiết">
                                            Chi tiết
                                        </button>

                                    </td>
                                </tr>
                                <tr v-if="paginatedBatches.length === 0">
                                    <td colspan="6" class="p-4 text-center text-sm text-gray-500">Không có dữ liệu phù
                                        hợp với tiêu chí tìm kiếm.</td>
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
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700"
                                :disabled="currentPage === 1" @click="prevPage">
                                &larr; Trang trước
                            </button>
                            <template v-for="page in totalPages" :key="page">
                                <button class="rounded px-3 py-1 text-sm"
                                    :class="page === currentPage ? 'bg-gray-200 font-bold' : 'text-gray-500 hover:text-gray-700'"
                                    @click="goToPage(page)">
                                    {{ page }}
                                </button>
                            </template>
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700"
                                :disabled="currentPage === totalPages" @click="nextPage">
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