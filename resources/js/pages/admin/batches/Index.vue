<script setup lang="ts">
import Pagination from "@/components/Pagination.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { type BreadcrumbItem, type SharedData } from "@/types";
import { Head, router, usePage } from "@inertiajs/vue3";
import { Eye, EyeOff, FileDown, PackagePlus, Search } from "lucide-vue-next";
import { computed, ref, watch } from "vue";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Quản lý đơn nhập hàng",
        href: "/admin/batches",
    },
];

type Supplier = {
    id: number;
    name: string;
};

type User = {
    id: number;
    name: string;
    email: string;
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
    payment_status: "unpaid" | "partially_paid" | "paid";
    paid_amount: number;
    receipt_status: "partially_received" | "completed" | "cancelled";
    notes: string | null;
    created_by: number;
    creator?: User;
    updated_by: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    batchItems?: BatchItem[];
};

type BatchItem = {
    id: number;
    batch_id: number;
    product_id: number;
    purchase_order_item_id: number;
    product_name: string;
    product_sku: string;
    ordered_quantity: number;
    received_quantity: number;
    rejected_quantity: number;
    remaining_quantity: number;
    current_quantity: number;
    purchase_price: number;
    total_amount: number;
    manufacturing_date: string | null;
    expiry_date: string | null;
    inventory_status: "active" | "low_stock" | "out_of_stock" | "expired" | "damaged";
    product?: {
        name: string;
        sku: string;
        unit?: {
            name: string;
        };
    };
};

const page = usePage<SharedData>();
const batches = ref([...(page.props.batches as Batch[])]);
const isLoading = ref(false);

const perPage = ref(10);
const currentPage = ref(1);
const perPageOptions = [10, 20, 50, 100];

const searchTerm = ref("");
const filterStatus = ref("");
const filterPaymentStatus = ref("");
const filterReceiptStatus = ref("");
const filterStartDate = ref("");
const filterEndDate = ref("");
const filterInvoiceNumber = ref("");
const isFiltersCollapsed = ref(true);

const showDeleteModal = ref(false);
const batchToDelete = ref<number | null>(null);
const openBatchDetailsId = ref<number | null>(null);

const filteredAndSortedBatches = computed(() => {
    let currentBatches = batches.value;

    const trimmedSearch = searchTerm.value.trim().toLowerCase();
    if (trimmedSearch) {
        currentBatches = currentBatches.filter(
            (batch) =>
                batch.batch_number.toLowerCase().includes(trimmedSearch) ||
                (batch.supplier?.name?.toLowerCase().includes(trimmedSearch) ?? false) ||
                (batch.notes?.toLowerCase().includes(trimmedSearch) ?? false)
        );
    }

    const trimmedInvoiceSearch = filterInvoiceNumber.value.trim().toLowerCase();
    if (trimmedInvoiceSearch) {
        currentBatches = currentBatches.filter(
            (batch) =>
                batch.invoice_number?.toLowerCase().includes(trimmedInvoiceSearch) ?? false
        );
    }

    if (filterPaymentStatus.value) {
        currentBatches = currentBatches.filter(
            (batch) => batch.payment_status === filterPaymentStatus.value
        );
    }

    if (filterReceiptStatus.value) {
        currentBatches = currentBatches.filter(
            (batch) => batch.receipt_status === filterReceiptStatus.value
        );
    }

    if (filterStartDate.value) {
        const start = new Date(filterStartDate.value + "T00:00:00");
        currentBatches = currentBatches.filter((batch) => {
            const batchDate = new Date(batch.received_date);
            return batchDate >= start;
        });
    }
    if (filterEndDate.value) {
        const end = new Date(filterEndDate.value + "T23:59:59");
        currentBatches = currentBatches.filter((batch) => {
            const batchDate = new Date(batch.received_date);
            return batchDate <= end;
        });
    }

    return currentBatches;
});

const total = computed(() => filteredAndSortedBatches.value.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedBatches = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredAndSortedBatches.value.slice(start, start + perPage.value);
});

function prevPage() {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
}

function nextPage() {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
}

function goToPage(page: number) {
    currentPage.value = page;
}

function changePerPage() {
    currentPage.value = 1;
}

function goToShowPage(id: number) {
    router.visit(route("admin.batches.show", id));
}

function toggleBatchDetails(id: number) {
    openBatchDetailsId.value = openBatchDetailsId.value === id ? null : id;
}

function resetPagination() {
    currentPage.value = 1;
}

function resetAllFilters() {
    searchTerm.value = "";
    filterPaymentStatus.value = "";
    filterReceiptStatus.value = "";
    filterStartDate.value = "";
    filterEndDate.value = "";
    filterInvoiceNumber.value = "";
    resetPagination();
}

function exportBatchesToExcel() {
    isLoading.value = true;
    window.location.href = "/admin/batches/export";
    setTimeout(() => {
        isLoading.value = false;
    }, 1000); // Giả lập thời gian tải
}

function deleteBatch() {
    if (batchToDelete.value) {
        router.delete(route("admin.batches.destroy", batchToDelete.value), {
            onSuccess: () => {
                showDeleteModal.value = false;
                batchToDelete.value = null;
                batches.value = batches.value.filter(
                    (batch) => batch.id !== batchToDelete.value
                );
            },
        });
    }
}

function getPaymentStatusDisplayName(
    status: "unpaid" | "partially_paid" | "paid"
) {
    switch (status) {
        case "unpaid":
            return "Chưa thanh toán";
        case "partially_paid":
            return "Đã thanh toán một phần";
        case "paid":
            return "Đã thanh toán";
        default:
            return "N/A";
    }
}

function getReceiptStatusDisplayName(
    status: "partially_received" | "completed" | "cancelled"
) {
    switch (status) {
        case "partially_received":
            return "Đã nhận một phần";
        case "completed":
            return "Đã nhận đủ";
        case "cancelled":
            return "Đã hủy";
        default:
            return "N/A";
    }
}

function getInventoryStatusDisplayName(
    status: "active" | "low_stock" | "out_of_stock" | "expired" | "damaged"
) {
    switch (status) {
        case "active":
            return "Hoạt động";
        case "low_stock":
            return "Sắp hết hàng";
        case "out_of_stock":
            return "Hết hàng";
        case "expired":
            return "Hết hạn";
        case "damaged":
            return "Hư hỏng";
        default:
            return "N/A";
    }
}

function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return "—";
    const date = new Date(dateStr);
    return date.toLocaleString("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
        minimumFractionDigits: 0,
    }).format(amount);
}

function formatDiscountDisplay(amount: number, type: string | null): string {
    if (!amount || !type) return "—";
    return type === "percent" ? `${amount}%` : formatCurrency(amount);
}

function calculateItemsSubtotalFromData(items: BatchItem[] | undefined): number {
    if (!items || items.length === 0) return 0;
    return items.reduce((sum, item) => sum + item.total_amount, 0);
}

// Đồng bộ batches khi props thay đổi
watch(
    () => page.props.batches,
    (newBatches) => {
        batches.value = (newBatches as any[]).map((b: any) => ({
            ...b,
            batchItems: b.batch_items,
        }));
    },
    { immediate: true }
);
</script>

<template>

    <Head>
        <title>Quản lý đơn nhập hàng</title>
        <meta name="description"
            content="Quản lý danh sách phiếu nhập hàng, theo dõi trạng thái thanh toán và nhận hàng." />
    </Head>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <div class="mb-4 flex items-center gap-4">
                        <h1 class="text-2xl font-bold">Quản lý đơn nhập hàng</h1>
                        <div class="ml-auto flex gap-4">
                            <button @click="exportBatchesToExcel"
                                class="inline-flex items-center rounded-3xl bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                                :disabled="isLoading">
                                <FileDown class="h-5 w-5" />
                                <span class="ml-2 hidden md:inline">{{ isLoading ? "Đang xuất..." : "Xuất Excel"
                                }}</span>
                            </button>
                            <button
                                class="inline-flex items-center rounded-3xl bg-green-500 px-4 py-2 text-white hover:bg-green-600">
                                <PackagePlus class="h-5 w-5" />
                                <span class="ml-2 hidden md:inline">Tạo Đơn nhập Hàng Mới</span>
                            </button>
                        </div>
                    </div>

                    <div class="mb-6 flex flex-wrap items-center gap-4">
                        <div class="relative min-w-[250px] flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <Search class="h-5 w-5 text-gray-400" />
                            </div>
                            <input type="text" v-model="searchTerm"
                                placeholder="Tìm kiếm mã phiếu nhập, NCC, ghi chú..."
                                class="w-full rounded-md border-gray-300 py-2 pr-4 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @input="resetPagination" />
                        </div>
                        <button @click="isFiltersCollapsed = !isFiltersCollapsed"
                            class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-sm hover:bg-gray-300">
                            Bộ lọc nâng cao
                            <svg class="ml-2 h-4 w-4" :class="{ 'rotate-180': !isFiltersCollapsed }" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

                    <!-- Bộ lọc nâng cao -->
                    <div v-if="!isFiltersCollapsed"
                        class="mb-6 grid grid-cols-1 gap-4 rounded-lg bg-gray-50 p-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Trạng thái thanh toán</label>
                            <select v-model="filterPaymentStatus"
                                class="mt-1 w-full rounded-md border-gray-300 py-2 text-sm" @change="resetPagination">
                                <option value="">Tất cả</option>
                                <option value="unpaid">Chưa thanh toán</option>
                                <option value="partially_paid">Đã thanh toán một phần</option>
                                <option value="paid">Đã thanh toán</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Trạng thái nhận hàng</label>
                            <select v-model="filterReceiptStatus"
                                class="mt-1 w-full rounded-md border-gray-300 py-2 text-sm" @change="resetPagination">
                                <option value="">Tất cả</option>
                                <option value="partially_received">Đã nhận một phần</option>
                                <option value="completed">Đã nhận đủ</option>
                                <option value="cancelled">Đã hủy</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Số hóa đơn</label>
                            <input type="text" v-model="filterInvoiceNumber" placeholder="Tìm kiếm số hóa đơn..."
                                class="mt-1 w-full rounded-md border-gray-300 py-2 text-sm" @input="resetPagination" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Từ ngày</label>
                            <input type="date" v-model="filterStartDate"
                                class="mt-1 w-full rounded-md border-gray-300 py-2 text-sm" @change="resetPagination" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Đến ngày</label>
                            <input type="date" v-model="filterEndDate"
                                class="mt-1 w-full rounded-md border-gray-300 py-2 text-sm" @change="resetPagination" />
                        </div>
                        <div class="col-span-3 flex justify-end">
                            <button @click="resetAllFilters"
                                class="inline-flex items-center rounded-md bg-red-500 px-4 py-2 text-sm text-white hover:bg-red-600">
                                Xóa bộ lọc
                            </button>
                        </div>
                    </div>

                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="w-[5%] p-3 text-left text-sm font-semibold">Mã phiếu nhập</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Nhà cung cấp</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Ngày nhận hàng</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Tổng tiền</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Trạng thái thanh toán</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Trạng thái nhận hàng</th>
                                    <th class="w-[5%] p-3 text-center text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="batch in paginatedBatches" :key="batch.id">
                                    <tr class="border-t">
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm font-medium">
                                            <button @click="goToShowPage(batch.id)"
                                                class="cursor-pointer hover:text-blue-500">
                                                {{ batch.batch_number }}
                                            </button>
                                        </td>
                                        <td class="supplier-column w-[15%] p-3 text-left text-sm">
                                            {{ batch.supplier ? batch.supplier.name : "N/A" }}
                                        </td>
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm">
                                            {{ formatDateTime(batch.received_date) }}
                                        </td>
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm">
                                            {{ formatCurrency(batch.total_amount) }}
                                        </td>
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm">
                                            <span :class="{
                                                'rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 shadow-sm': batch.payment_status === 'paid',
                                                'rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800 shadow-sm': batch.payment_status === 'partially_paid',
                                                'rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 shadow-sm': batch.payment_status === 'unpaid',
                                            }" :title="getPaymentStatusDisplayName(batch.payment_status)">
                                                {{ getPaymentStatusDisplayName(batch.payment_status) }}
                                            </span>
                                        </td>
                                        <td class="w-[10%] p-3 text-left text-sm">
                                            <span :class="{
                                                'rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 shadow-sm': batch.receipt_status === 'completed',
                                                'rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800 shadow-sm': batch.receipt_status === 'partially_received',
                                                'rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 shadow-sm': batch.receipt_status === 'cancelled',
                                            }" :title="getReceiptStatusDisplayName(batch.receipt_status)">
                                                {{ getReceiptStatusDisplayName(batch.receipt_status) }}
                                            </span>
                                        </td>
                                        <td class="w-[5%] p-3 text-center text-sm">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button @click="toggleBatchDetails(batch.id)"
                                                    class="flex items-center gap-1 rounded-md bg-gray-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none">
                                                    <component :is="openBatchDetailsId === batch.id ? EyeOff : Eye"
                                                        class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="openBatchDetailsId === batch.id">
                                        <td :colspan="7" class="border-t border-b border-gray-200 bg-gray-50 p-4">
                                            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                                                <h4 class="mb-4 text-xl font-bold text-gray-800">
                                                    Thông tin chi tiết phiếu nhập - {{ batch.batch_number }}
                                                </h4>
                                                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                                                    <div
                                                        class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4 shadow-sm">
                                                        <p><strong>Mã phiếu nhập:</strong> {{ batch.batch_number ||
                                                            "Không có" }}</p>
                                                        <p><strong>Nhà cung cấp:</strong> {{ batch.supplier ?
                                                            batch.supplier.name : "N/A" }}</p>
                                                        <p><strong>Ngày nhận hàng:</strong> {{
                                                            formatDateTime(batch.received_date) || "N/A" }}</p>
                                                        <p><strong>Số hóa đơn:</strong> {{ batch.invoice_number ||
                                                            "Không có" }}</p>
                                                        <p><strong>Ghi chú:</strong> {{ batch.notes || "Không có" }}</p>
                                                    </div>
                                                    <div
                                                        class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4 shadow-sm">
                                                        <p><strong>Trạng thái thanh toán:</strong> {{
                                                            getPaymentStatusDisplayName(batch.payment_status) }}</p>
                                                        <p><strong>Trạng thái nhận hàng:</strong> {{
                                                            getReceiptStatusDisplayName(batch.receipt_status) }}</p>
                                                        <p><strong>Tổng tiền:</strong> {{
                                                            formatCurrency(batch.total_amount) }}</p>
                                                        <p><strong>Số tiền đã thanh toán:</strong> {{
                                                            formatCurrency(batch.paid_amount) }}</p>
                                                        <p><strong>Người tạo:</strong> {{ batch.creator ?
                                                            batch.creator.name : 'N/A' }}</p>
                                                    </div>
                                                </div>

                                                <h5 class="mt-6 mb-3 text-base font-semibold text-gray-800">Sản phẩm
                                                    trong phiếu nhập:</h5>
                                                <div class="overflow-x-auto max-h-[400px]">
                                                    <table
                                                        class="min-w-full table-fixed border-collapse overflow-hidden rounded-lg border border-gray-200 text-sm">
                                                        <thead class="bg-blue-50 font-semibold text-gray-700 uppercase">
                                                            <tr>
                                                                <th class="w-[9%] p-2 text-left whitespace-normal">Tên
                                                                    sản phẩm</th>
                                                                <th class="w-[7%] p-2 text-center">Mã SKU</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL
                                                                    đặt</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL
                                                                    trả</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL
                                                                    nhận</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL
                                                                    từ chối</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL
                                                                    nhập</th>
                                                                <th class="w-[5%] p-2 text-left">Đơn vị</th>
                                                                <th class="w-[7%] p-2 text-right">Đơn giá</th>
                                                                <th class="w-[7%] p-2 text-right">Tổng giá</th>
                                                                <th class="w-[10%] p-2 text-center">Ngày sản xuất</th>
                                                                <th class="w-[9%] p-2 text-center">Ngày hết hạn</th>
                                                                <th class="w-[15%] p-2 text-center">Trạng thái tồn kho
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template
                                                                v-if="batch.batchItems && batch.batchItems.length > 0">
                                                                <tr v-for="item in batch.batchItems" :key="item.id"
                                                                    class="border-t">
                                                                    <td
                                                                        class="w-[9%] p-2 break-words whitespace-normal">
                                                                        {{ item.product?.name ?? item.product_name }}
                                                                    </td>
                                                                    <td class="w-[7%] text-center">{{ item.product?.sku
                                                                        ?? item.product_sku }}</td>
                                                                    <td class="w-[7%] text-center">{{
                                                                        item.ordered_quantity }}</td>
                                                                    <td class="w-[7%] text-center">{{
                                                                        item.rejected_quantity }}</td>
                                                                    <td class="w-[7%] text-center">{{
                                                                        item.received_quantity }}</td>
                                                                    <td class="w-[7%] text-center">{{
                                                                        item.remaining_quantity }}</td>
                                                                    <td class="w-[7%] text-center">{{
                                                                        item.current_quantity }}</td>
                                                                    <td class="w-[5%] text-left">{{
                                                                        item.product?.unit?.name ?? "N/A" }}</td>
                                                                    <td class="w-[7%] text-right">{{
                                                                        formatCurrency(item.purchase_price) }}</td>
                                                                    <td class="w-[7%] text-right">{{
                                                                        formatCurrency(item.total_amount) }}</td>
                                                                    <td class="w-[10%] text-center">{{
                                                                        formatDateTime(item.manufacturing_date) }}</td>
                                                                    <td class="w-[9%] text-center">{{
                                                                        formatDateTime(item.expiry_date) }}</td>
                                                                    <td class="w-[15%] text-center">
                                                                        <span :class="{
                                                                            'rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 shadow-sm': item.inventory_status === 'active',
                                                                            'rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800 shadow-sm': item.inventory_status === 'low_stock',
                                                                            'rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 shadow-sm': item.inventory_status === 'out_of_stock' || item.inventory_status === 'expired',
                                                                            'rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800 shadow-sm': item.inventory_status === 'damaged',
                                                                        }"
                                                                            :title="getInventoryStatusDisplayName(item.inventory_status)">
                                                                            {{
                                                                                getInventoryStatusDisplayName(item.inventory_status)
                                                                            }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                            <tr v-else>
                                                                <td :colspan="16"
                                                                    class="py-4 text-center text-gray-500">
                                                                    Không có sản phẩm nào trong phiếu nhập này.
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="mt-6 mb-5 flex flex-col items-end gap-2 text-gray-800">
                                                        <h2
                                                            class="mb-3 w-full text-right text-lg font-semibold text-gray-800">
                                                            Tổng kết phiếu nhập</h2>
                                                        <div class="mt-4 grid gap-x-5 gap-y-2"
                                                            style="grid-template-columns: max-content auto">
                                                            <div class="text-left"><strong>Tổng phụ (theo SP):</strong>
                                                            </div>
                                                            <div class="text-right">{{
                                                                formatCurrency(calculateItemsSubtotalFromData(batch.batchItems))
                                                            }}</div>
                                                            <div class="text-left"><strong>Tổng tiền:</strong></div>
                                                            <div class="text-right text-xl font-bold">{{
                                                                formatCurrency(batch.total_amount) }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="paginatedBatches.length === 0">
                                    <td colspan="7" class="p-4 text-center text-sm text-gray-500">
                                        Không có dữ liệu phù hợp với tiêu chí tìm kiếm.
                                    </td>
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
                                ← Trang trước
                            </button>
                            <template v-for="pageNumber in totalPages" :key="pageNumber">
                                <button class="rounded px-3 py-1 text-sm"
                                    :class="pageNumber === currentPage ? 'bg-gray-200 font-bold' : 'text-gray-500 hover:text-gray-700'"
                                    @click="goToPage(pageNumber)">
                                    {{ pageNumber }}
                                </button>
                            </template>
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700"
                                :disabled="currentPage === totalPages" @click="nextPage">
                                Trang sau →
                            </button>
                            <select v-model="perPage" @change="changePerPage"
                                class="rounded-md border-gray-300 py-1 pr-7 pl-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="option in perPageOptions" :key="option" :value="option">
                                    {{ option }} / trang
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.truncate-column {
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.supplier-column {
    max-width: 200px;
    white-space: normal;
    word-wrap: break-word;
}
</style>
