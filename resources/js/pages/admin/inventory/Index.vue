<script setup lang="ts">
import DeleteModal from '@/components/DeleteModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { PackagePlus, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ProductUnit {
    name: string;
}

interface Product {
    id: number;
    name: string;
    sku: string;
    barcode: string;
    selling_price: number;
    unit: ProductUnit;
    image_url?: string;
}

interface Batch {
    batch_number: string;
}

interface BatchItem {
    id: number;
    product: Product;
    batch: Batch;
    purchase_order_item_id: number | null;
    ordered_quantity: number;
    received_quantity: number;
    rejected_quantity: number;
    remaining_quantity: number;
    current_quantity: number;
    purchase_price: number;
    total_amount: number;
    inventory_status: string;
    created_by: number | null;
    updated_by: number | null;
    created_at: string | null;
    updated_at: string | null;
}

const props = defineProps<{
    inventory: BatchItem[];
    units: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quản lý tồn kho', href: '/admin/inventory' },
];

const items = ref<BatchItem[]>([...props.inventory]);

const filterName = ref('');
const filterUnit = ref<string | null>(null);
const filterMinPrice = ref<number | null>(null);
const filterMaxPrice = ref<number | null>(null);
const filterMinStock = ref<number | null>(null);
const filterMaxStock = ref<number | null>(null);
const filterStatus = ref<'all' | 'in_stock' | 'low_stock' | 'out_of_stock'>('all');
const lowStockThreshold = 5;

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filterName.value.trim()) count++;
    if (filterUnit.value !== null) count++;
    if (filterMinPrice.value !== null) count++;
    if (filterMaxPrice.value !== null) count++;
    if (filterMinStock.value !== null) count++;
    if (filterMaxStock.value !== null) count++;
    if (filterStatus.value !== 'all') count++;
    return count;
});

const filteredProducts = computed(() => {
    return items.value.filter((item) => {
        const product = item.product;
        const matchesName = product.name.toLowerCase().includes(filterName.value.toLowerCase().trim());
        const matchesUnit = filterUnit.value === null || product.unit?.name === filterUnit.value;
        const matchesMinPrice = filterMinPrice.value === null || item.purchase_price >= filterMinPrice.value;
        const matchesMaxPrice = filterMaxPrice.value === null || item.purchase_price <= filterMaxPrice.value;
        const matchesMinStock = filterMinStock.value === null || item.current_quantity >= filterMinStock.value;
        const matchesMaxStock = filterMaxStock.value === null || item.current_quantity <= filterMaxStock.value;

        let matchesStatus = true;
        if (filterStatus.value === 'in_stock') {
            matchesStatus = item.current_quantity > lowStockThreshold;
        } else if (filterStatus.value === 'low_stock') {
            matchesStatus = item.current_quantity > 0 && item.current_quantity <= lowStockThreshold;
        } else if (filterStatus.value === 'out_of_stock') {
            matchesStatus = item.current_quantity === 0;
        }

        return matchesName && matchesUnit && matchesMinPrice && matchesMaxPrice && matchesMinStock && matchesMaxStock && matchesStatus;
    });
});

const uniqueUnits = computed(() => props.units);

const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);
const total = computed(() => filteredProducts.value.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));
const paginatedProducts = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredProducts.value.slice(start, start + perPage.value);
});

function goToPage(page: number) {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page;
}

function prevPage() {
    if (currentPage.value > 1) currentPage.value--;
}

function nextPage() {
    if (currentPage.value < totalPages.value) currentPage.value++;
}

function changePerPage(event: Event) {
    perPage.value = Number((event.target as HTMLSelectElement).value);
    currentPage.value = 1;
}

function resetFilters() {
    filterName.value = '';
    filterUnit.value = null;
    filterMinPrice.value = null;
    filterMaxPrice.value = null;
    filterMinStock.value = null;
    filterMaxStock.value = null;
    filterStatus.value = 'all';
    currentPage.value = 1;
}

const isSidebarOpen = ref(false);
function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value;
}

function goToEditInventory(id: number) {
    router.visit(`/admin/inventory/${id}/edit`);
}

function goToCreatePage() {
    router.visit('/admin/inventory/create');
}

function goToTrashedPage() {
    router.visit('/admin/inventory/trashed');
}

const showDeleteModal = ref(false);
const itemToDelete = ref<number | null>(null);

function confirmDelete(id: number) {
    itemToDelete.value = id;
    showDeleteModal.value = true;
}

function handleDeleteInventory() {
    if (!itemToDelete.value) return;

    router.delete(`/admin/inventory/${itemToDelete.value}`, {
        onSuccess: () => {
            const idx = items.value.findIndex((item) => item.id === itemToDelete.value);
            if (idx !== -1) items.value.splice(idx, 1);
            showDeleteModal.value = false;
            itemToDelete.value = null;
        },
        preserveState: true,
    });
}

function cancelDelete() {
    showDeleteModal.value = false;
    itemToDelete.value = null;
}
</script>

<template>
    <Head title="Quản lý tồn kho" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <!-- Header with title and action buttons -->
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Quản lý tồn kho</h1>
                        <div class="flex gap-2">
                            <button @click="goToCreatePage" class="rounded-3xl bg-green-500 px-8 py-2 text-white hover:bg-green-600">
                                <PackagePlus />
                            </button>
                            <button @click="goToTrashedPage" class="rounded-3xl bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
                                Thùng rác
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="mb-4 flex flex-wrap items-center gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                        <div class="flex space-x-2">
                            <button
                                class="px-3 py-1 text-xs font-medium"
                                :class="
                                    filterStatus === 'all'
                                        ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'
                                "
                                @click="filterStatus = 'all'"
                            >
                                Tất cả
                            </button>
                            <button
                                class="px-3 py-1 text-xs font-medium"
                                :class="
                                    filterStatus === 'in_stock'
                                        ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'
                                "
                                @click="filterStatus = 'in_stock'"
                            >
                                Còn hàng
                            </button>
                            <button
                                class="px-3 py-1 text-xs font-medium"
                                :class="
                                    filterStatus === 'low_stock'
                                        ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'
                                "
                                @click="filterStatus = 'low_stock'"
                            >
                                Sắp hết hàng
                            </button>
                            <button
                                class="px-3 py-1 text-xs font-medium"
                                :class="
                                    filterStatus === 'out_of_stock'
                                        ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'
                                "
                                @click="filterStatus = 'out_of_stock'"
                            >
                                Hết hàng
                            </button>
                        </div>
                        <div class="flex-1">
                            <input
                                v-model="filterName"
                                type="text"
                                placeholder="Nhập tên sản phẩm..."
                                class="focus:ring-opacity-50 w-full rounded-md border-gray-300 p-2 text-sm focus:border-blue-500 focus:ring focus:ring-blue-500 sm:w-64 dark:border-gray-600 dark:bg-gray-700 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                            />
                        </div>
                        <div class="relative">
                            <button
                                @click="toggleSidebar"
                                class="focus:ring-opacity-50 relative flex items-center rounded-md bg-blue-500 px-4 py-2 text-white transition-colors duration-200 hover:bg-blue-600 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-400"
                                :class="{ 'bg-blue-700 dark:bg-blue-800': isSidebarOpen }"
                                :aria-expanded="isSidebarOpen"
                                aria-controls="filter-sidebar"
                                title="Mở hoặc đóng bộ lọc nâng cao"
                            >
                                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                    />
                                </svg>
                                Bộ lọc
                                <span
                                    v-if="activeFiltersCount"
                                    class="absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white"
                                >
                                    {{ activeFiltersCount }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Filter Sidebar -->
                    <div
                        :class="[
                            'fixed inset-y-0 right-0 z-50 w-full transform bg-white p-6 shadow-xl transition-transform duration-300 ease-in-out sm:w-80 md:w-96 dark:bg-gray-800',
                            isSidebarOpen ? 'translate-x-0' : 'translate-x-full',
                        ]"
                        id="filter-sidebar"
                    >
                        <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bộ lọc nâng cao</h2>
                            <button @click="toggleSidebar" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="mt-4 space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tên sản phẩm</label>
                                <input
                                    v-model="filterName"
                                    type="text"
                                    placeholder="Nhập tên sản phẩm..."
                                    class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Đơn vị tính</label>
                                <select
                                    v-model="filterUnit"
                                    class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                >
                                    <option :value="null">Tất cả đơn vị</option>
                                    <option v-for="unit in uniqueUnits" :key="unit" :value="unit">{{ unit }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Giá mua (VNĐ)</label>
                                <div class="flex gap-2">
                                    <input
                                        v-model.number="filterMinPrice"
                                        type="number"
                                        placeholder="Từ"
                                        class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                    />
                                    <input
                                        v-model.number="filterMaxPrice"
                                        type="number"
                                        placeholder="Đến"
                                        class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tồn kho</label>
                                <div class="flex gap-2">
                                    <input
                                        v-model.number="filterMinStock"
                                        type="number"
                                        placeholder="Từ"
                                        class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                    />
                                    <input
                                        v-model.number="filterMaxStock"
                                        type="number"
                                        placeholder="Đến"
                                        class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                    />
                                </div>
                            </div>
                            <div class="flex justify-between gap-2">
                                <button
                                    @click="resetFilters"
                                    class="flex-1 rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
                                >
                                    Xóa bộ lọc
                                </button>
                                <button
                                    @click="toggleSidebar"
                                    class="flex-1 rounded-md bg-blue-500 px-4 py-2 text-sm text-white transition-colors hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700"
                                >
                                    Áp dụng
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="isSidebarOpen" class="bg-opacity-50 fixed inset-0 z-40 bg-black sm:hidden" @click="toggleSidebar"></div>

                    <!-- Inventory Table -->
                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md dark:bg-gray-800">
                        <table class="w-full text-left">
                            <thead class="bg-gray-200 dark:bg-gray-700">
                                <tr>
                                    <th class="w-[25%] p-3 text-left text-sm font-semibold">Tên sản phẩm</th>
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">SKU</th>
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">Barcode</th>
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">Đơn vị tính</th>
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">Số lượng đặt</th>
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">Tồn kho hiện tại</th>
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">Giá bán</th>
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">Giá mua</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, idx) in paginatedProducts"
                                    :key="item.id"
                                    class="border-t border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    <td class="w-[25%] p-3 text-left text-sm">
                                        <Link :href="route('admin.inventory.show', item.product.id)" class="group flex items-center gap-2">
                                            <img
                                                :src="item.product.image_url || 'https://via.placeholder.com/30'"
                                                alt="Product"
                                                class="h-8 w-8 rounded-full object-cover shadow-sm"
                                            />
                                            <span
                                                class="text-sm font-medium text-gray-800 group-hover:text-blue-600 dark:text-gray-200 dark:group-hover:text-blue-400"
                                            >
                                                {{ item.product.name }}
                                            </span>
                                        </Link>
                                    </td>
                                    <td class="w-[10%] p-3 text-center text-sm">{{ item.product.sku }}</td>
                                    <td class="w-[10%] p-3 text-center text-sm">{{ item.product.barcode }}</td>
                                    <td class="w-[10%] p-3 text-center text-sm">{{ item.product.unit?.name || 'N/A' }}</td>
                                    <td class="w-[10%] p-3 text-center text-sm">{{ item.ordered_quantity }}</td>
                                    <td class="w-[10%] p-3 text-center text-sm">{{ item.current_quantity }}</td>
                                    <td class="w-[10%] p-3 text-center text-sm">{{ (item.product.selling_price ?? 0).toLocaleString('vi-VN') }}đ</td>
                                    <td class="w-[10%] p-3 text-center text-sm">{{ (item.purchase_price ?? 0).toLocaleString('vi-VN') }}đ</td>
                                   
                                </tr>
                                <tr v-if="!paginatedProducts.length">
                                    <td colspan="10" class="p-3 text-center text-sm text-gray-500 dark:text-gray-400">Không có dữ liệu</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm">
                            Hiển thị kết quả từ
                            <span class="font-semibold">{{ (currentPage - 1) * perPage + 1 }}</span>
                            -
                            <span class="font-semibold">{{ Math.min(currentPage * perPage, total) }}</span>
                            trên tổng <span class="font-semibold">{{ total }}</span>
                        </p>
                        <div class="flex items-center space-x-2">
                            <button
                                class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                :disabled="currentPage === 1"
                                @click="prevPage"
                            >
                                ← Trang trước
                            </button>
                            <button
                                v-for="page in totalPages"
                                :key="page"
                                class="rounded px-3 py-1 text-sm"
                                :class="page === currentPage ? 'bg-gray-200 font-bold' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                @click="goToPage(page)"
                            >
                                {{ page }}
                            </button>
                            <button
                                class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                :disabled="currentPage === totalPages"
                                @click="nextPage"
                            >
                                Trang sau →
                            </button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <p class="text-sm">Hiển thị</p>
                            <select
                                class="rounded border p-1 text-sm focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-400"
                                v-model="perPage"
                                @change="changePerPage"
                            >
                                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <p class="text-sm">kết quả</p>
                        </div>
                    </div>
                </div>
            </div>

            <DeleteModal
                :is-open="showDeleteModal"
                title="Xóa mục tồn kho"
                message="Bạn có chắc chắn muốn xóa mục tồn kho này?"
                @confirm="handleDeleteInventory"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>

<style lang="css" scoped>
.table-wrapper table {
    min-width: 100%;
    table-layout: fixed;
}

.dark .text-gray-600 {
    color: var(--gray-300);
}

.dark .border-gray-100 {
    border-color: var(--gray-700);
}

.dark .bg-gray-200 {
    background-color: var(--gray-700);
}

.dark .hover\:bg-gray-50:hover {
    background-color: var(--gray-700);
}
</style>