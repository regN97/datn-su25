<script setup lang="ts">
import DeleteModal from '@/components/DeleteModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, PackagePlus, Pencil, Trash2 } from 'lucide-vue-next';

import { computed, ref, watch } from 'vue';

// --- Định nghĩa các Type bắt đầu từ đây ---

// Kiểu cho PO Status
type POStatus = {
    id: number;
    name: string;
    code: string;
};

// Kiểu cho User (người tạo, người duyệt)
type User = {
    id: number;
    name: string;
    email: string;
};

// Kiểu cho Supplier
type Supplier = {
    id: number;
    name: string;
    contact_person: string;
    email: string;
    phone: string;
    address: string | null;
};

// Kiểu mới cho ProductUnit
type ProductUnit = {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
};

// Kiểu mới cho Product
type Product = {
    id: number;
    name: string;
    sku: string;
    barcode: string | null;
    description: string | null;
    category_id: number;
    unit_id: number;
    unit?: ProductUnit;
    purchase_price: number;
    selling_price: number;
    image_url: string | null;
    min_stock_level: number;
    max_stock_level: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
};

// Kiểu mới cho PurchaseOrderItem (các mặt hàng trong đơn đặt hàng)
type PurchaseOrderItem = {
    id: number;
    purchase_order_id: number;
    product_id: number;
    product?: Product; // Mối quan hệ product, bao gồm cả unit
    product_name: string;
    product_sku: string;
    ordered_quantity: number;
    received_quantity: number;
    quantity_returned: number; // Đã thêm
    unit_cost: number;
    subtotal: number;
    discount_amount: number | null; // Có thể null
    discount_type: 'percent' | 'amount' | null; // Có thể null
    notes: string | null; // Đã thêm
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
};

// Kiểu cho PurchaseOrder, đã cập nhật theo cấu trúc database mới
type PurchaseOrder = {
    id: number;
    po_number: string;
    supplier_id: number;
    supplier?: Supplier;
    status_id: number;
    status?: POStatus;
    order_date: string;
    expected_delivery_date: string | null;
    actual_delivery_date: string | null;
    discount_amount: number | null;
    discount_type: 'percent' | 'amount' | null;
    total_amount: number;
    created_by: number;
    creator?: User;
    approved_by: number | null;
    approver?: User;
    approved_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    items?: PurchaseOrderItem[]; // Mảng các mặt hàng trong đơn đặt hàng
};

// --- Định nghĩa các Type kết thúc ở đây ---

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý đơn đặt hàng',
        href: '/admin/purchase-orders',
    },
];

const page = usePage<SharedData & { purchaseOrders: PurchaseOrder[] }>();
const allPurchaseOrders = computed(() => page.props.purchaseOrders); // Đổi tên để phân biệt với filtered list

// --- State cho tìm kiếm và bộ lọc ---
const searchTerm = ref('');
const selectedOrderStatus = ref('');

// Danh sách các tùy chọn cho bộ lọc trạng thái (thêm vào script setup)
const poStatusOptions: POStatus[] = [
    { id: 1, name: 'Đang chờ', code: 'pending' },
    { id: 2, name: 'Đã duyệt', code: 'approved' },
    { id: 3, name: 'Đã gửi', code: 'sent' },
    { id: 4, name: 'Đã hủy', code: 'cancelled' },
    { id: 5, name: 'Từ chối', code: 'rejected' },
];

// --- Cấu hình phân trang ---
const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

// --- Computed cho danh sách đơn hàng đã lọc và phân trang ---
const filteredPurchaseOrders = computed(() => {
    let filtered = allPurchaseOrders.value;

    // Lọc theo từ khóa tìm kiếm (Mã PO, NCC, Ghi chú)
    if (searchTerm.value) {
        const lowerSearchTerm = searchTerm.value.toLowerCase();
        filtered = filtered.filter(
            (order) =>
                order.po_number.toLowerCase().includes(lowerSearchTerm) ||
                (order.supplier?.name && order.supplier.name.toLowerCase().includes(lowerSearchTerm)) ||
                (order.notes && order.notes.toLowerCase().includes(lowerSearchTerm)), // Giả sử "Hóa đơn" có thể liên quan đến ghi chú hoặc một trường khác
        );
    }

    // Lọc theo trạng thái PO
    if (selectedOrderStatus.value) {
        filtered = filtered.filter(
            (order) => order.status?.code?.toLowerCase() === selectedOrderStatus.value.toLowerCase(), // So sánh không phân biệt chữ hoa/thường
        );
    }

    return filtered;
});

// Watchers để reset currentPage về 1 khi bất kỳ filter nào thay đổi
watch([searchTerm, selectedOrderStatus], () => {
    currentPage.value = 1;
});

const total = computed(() => filteredPurchaseOrders.value.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedPurchaseOrders = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredPurchaseOrders.value.slice(start, start + perPage.value);
});

function goToPage(page: number) {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
}

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

function changePerPage(event: Event) {
    const value = +(event.target as HTMLSelectElement).value;
    perPage.value = value;
    currentPage.value = 1;
}

function goToShowPage(id: number) {
    router.visit(route('admin.purchase-orders.show', id));
}

function goToCreatePage() {
    router.visit(route('admin.purchase-orders.create'));
}

function goToEditPage(id: number) {
    router.visit(route('admin.purchase-orders.edit', id));
}

const openPurchaseOrderDetailsId = ref<number | null>(null);

function toggleDetails(orderId: number) {
    if (openPurchaseOrderDetailsId.value === orderId) {
        openPurchaseOrderDetailsId.value = null;
    } else {
        openPurchaseOrderDetailsId.value = orderId;
    }
}

const showDeleteModal = ref(false);
const purchaseOrderToDelete = ref<number | null>(null);

function confirmDelete(id: number) {
    purchaseOrderToDelete.value = id;
    showDeleteModal.value = true;
}

function handleDeletePurchaseOrder() {
    if (purchaseOrderToDelete.value !== null) {
        router.delete(route('admin.purchase-orders.destroy', purchaseOrderToDelete.value), {
            onSuccess: () => {
                showDeleteModal.value = false;
                purchaseOrderToDelete.value = null;
            },
            onError: () => {
                showDeleteModal.value = false;
            },
            preserveState: true,
        });
    }
}

function cancelDelete() {
    showDeleteModal.value = false;
    purchaseOrderToDelete.value = null;
}
// Hàm formatCurrency được định nghĩa đúng kiểu PurchaseOrderItem subtotal và unit_cost
const formatCurrency = (amount: number | null | undefined): string => {
    if (amount === null || amount === undefined) return '0₫';
    return amount.toLocaleString('vi-VN') + '₫';
};

/**
 * Định dạng hiển thị số tiền giảm giá kèm theo loại giảm giá.
 * @param {number | null | undefined} amount - Số tiền hoặc phần trăm giảm giá.
 * @param {'percent' | 'amount' | null | undefined} type - Loại giảm giá ('percent' hoặc 'amount').
 * @returns {string} Chuỗi hiển thị giảm giá.
 */
const formatDiscountDisplay = (amount: number | null | undefined, type: 'percent' | 'amount' | null | undefined): string => {
    if (amount === null || amount === undefined || amount === 0) return '0₫';

    if (type === 'percent') {
        // Giảm giá theo phần trăm
        return `${amount}%`;
    } else if (type === 'amount') {
        // Giảm giá số tiền cố định
        return formatCurrency(amount);
    }
    return '0₫'; // Trường hợp không xác định
};

// Hàm này sẽ tính tổng phụ từ các item đã có trường subtotal từ backend
const calculateItemsSubtotalFromData = (items: PurchaseOrderItem[] | undefined): number => {
    if (!items || items.length === 0) return 0;
    const total = items.reduce((sum, item) => sum + (item.subtotal || 0), 0);
    return total;
};

// --- HÀM HỖ TRỢ DỊCH ENUM SANG TIẾNG VIỆT VÀ TRẢ VỀ CLASS CSS ---

function getOrderStatusClass(statusCode: string | undefined): string {
    if (!statusCode) return 'bg-gray-100 text-gray-800';

    switch (statusCode.toLowerCase()) {
        case 'pending':
        case 'draft':
            return 'bg-yellow-100 text-yellow-800';
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'sent':
            return 'bg-indigo-100 text-indigo-800';
        case 'rejected':
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        case 'processing':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Hàm định dạng ngày tháng sang dd/mm/yyyy
function formatDate(dateString: string | null): string {
    if (!dateString) return 'N/A';

    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return dateString;
        }
        return date.toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch (e) {
        console.error('Lỗi định dạng ngày:', e);
        return dateString;
    }
}

function goToTrashedPage() {
    router.visit('/admin/purchase-orders/trashed');
}
</script>
<template>
    <Head title="Purchase Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <div class="mb-4 flex items-center gap-4">
                        <h1 class="text-2xl font-bold">Quản lý đơn đặt hàng</h1>
                        <div class="ml-auto flex gap-4">
                            <button
                                @click="goToCreatePage"
                                class="inline-flex items-center rounded-3xl bg-green-500 px-4 py-2 text-white hover:bg-green-600"
                            >
                                <PackagePlus class="h-5 w-5" />
                                <span class="ml-2 hidden md:inline">Tạo Đơn Hàng Mới</span>
                            </button>
                            <button @click="goToTrashedPage" class="rounded-3xl bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">Thùng rác</button>
                        </div>
                    </div>

                    <div class="mb-6 flex flex-wrap items-center gap-4">
                        <div class="relative min-w-[250px] flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchTerm"
                                placeholder="Tìm kiếm mã PO, NCC, ghi chú..."
                                class="w-full rounded-md border-gray-300 py-2 pr-4 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div class="min-w-[150px]">
                            <label for="po-status-filter" class="sr-only">Lọc theo trạng thái PO</label>
                            <select
                                id="po-status-filter"
                                v-model="selectedOrderStatus"
                                class="w-full rounded-md border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Tất cả trạng thái PO</option>
                                <option v-for="option in poStatusOptions" :key="option.code" :value="option.code">
                                    {{ option.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="w-[5%] p-3 text-left text-sm font-semibold">Mã PO</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Nhà cung cấp</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Ngày đặt</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Ngày giao dự kiến</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Ngày giao thực tế</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Trạng thái</th>
                                    <th class="w-[5%] p-3 text-center text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="order in paginatedPurchaseOrders" :key="order.id">
                                    <tr class="border-t">
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm font-medium">
                                            <button @click="goToShowPage(order.id)" class="cursor-pointer hover:text-blue-500">
                                                {{ order.po_number }}
                                            </button>
                                        </td>
                                        <td class="supplier-column w-[15%] p-3 text-left text-sm">
                                            {{ order.supplier ? order.supplier.name : 'N/A' }}
                                        </td>
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm">
                                            {{ formatDate(order.order_date) }}
                                        </td>
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm">
                                            {{ formatDate(order.expected_delivery_date) }}
                                        </td>
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm">
                                            {{ formatDate(order.actual_delivery_date) }}
                                        </td>
                                        <td class="w-[10%] p-3 text-left text-sm">
                                            <span
                                                :class="[
                                                    'inline-flex rounded-full px-2 py-1 text-xs leading-5 font-semibold',
                                                    getOrderStatusClass(order.status?.code),
                                                ]"
                                            >
                                                {{ order.status ? order.status.name : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="w-[5%] p-3 text-center text-sm">
                                            <div class="flex items-center justify-center space-x-2 text-center">
                                                <button
                                                    @click="toggleDetails(order.id)"
                                                    class="flex items-center gap-1 rounded-md bg-gray-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
                                                >
                                                    <component :is="openPurchaseOrderDetailsId === order.id ? EyeOff : Eye" class="h-4 w-4" />
                                                </button>
                                                <button
                                                    class="rounded-md bg-blue-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                                                    @click="goToEditPage(order.id)"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                </button>
                                                <button
                                                    @click="confirmDelete(order.id)"
                                                    class="rounded-md bg-red-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="openPurchaseOrderDetailsId === order.id">
                                        <td :colspan="7" class="border-t border-b border-gray-200 bg-gray-50 p-4">
                                            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                                                <h4 class="mb-4 text-xl font-bold text-gray-800">
                                                    Thông tin chi tiết đơn hàng - {{ order.po_number || 'Không có' }}
                                                </h4>
                                                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                                                    <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4 shadow-sm">
                                                        <p><strong>Mã PO:</strong> {{ order.po_number || 'Không có' }}</p>
                                                        <p><strong>Nhà cung cấp:</strong> {{ order.supplier ? order.supplier.name : 'N/A' }}</p>
                                                        <p><strong>Ngày đặt hàng:</strong> {{ formatDate(order.order_date) || 'N/A' }}</p>
                                                        <p>
                                                            <strong>Ngày giao dự kiến:</strong>
                                                            {{ formatDate(order.expected_delivery_date) || 'N/A' }}
                                                        </p>
                                                        <p>
                                                            <strong>Ngày giao thực tế:</strong>
                                                            {{ formatDate(order.actual_delivery_date) || 'Chưa giao' }}
                                                        </p>
                                                        <p><strong>Ghi chú đơn hàng:</strong> {{ order.notes || 'Không có' }}</p>
                                                    </div>
                                                    <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4 shadow-sm">
                                                        <p>
                                                            <strong>Trạng thái:</strong>
                                                            <span
                                                                class="inline-block rounded px-3 py-1 text-base font-semibold"
                                                                :class="[
                                                                    'inline-flex rounded-full px-2 py-1 text-xs leading-5 font-semibold',
                                                                    getOrderStatusClass(order.status?.code),
                                                                ]"
                                                            >
                                                                {{ order.status ? order.status.name : 'N/A' }}
                                                            </span>
                                                        </p>
                                                        <p><strong>Người tạo:</strong> {{ order.creator ? order.creator.name : 'N/A' }}</p>
                                                        <p><strong>Người duyệt:</strong> {{ order.approver ? order.approver.name : 'Chưa duyệt' }}</p>
                                                        <p><strong>Thời gian duyệt:</strong> {{ formatDate(order.approved_at) || 'Chưa duyệt' }}</p>
                                                    </div>
                                                </div>

                                                <h5 class="mt-6 mb-3 text-base font-semibold text-gray-800">Sản phẩm trong đơn hàng:</h5>
                                                <div class="overflow-x-auto">
                                                    <table
                                                        class="min-w-full table-fixed border-collapse overflow-hidden rounded-lg border border-gray-200 text-sm"
                                                    >
                                                        <thead class="bg-blue-50 font-semibold text-gray-700 uppercase">
                                                            <tr>
                                                                <th class="w-[12%] p-2 text-left whitespace-normal">Tên sản phẩm</th>
                                                                <th class="w-[10%] p-2 text-left">Mã SKU</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL đặt</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL trả</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL nhận</th>
                                                                <th class="w-[5%] p-2 text-left">Đơn vị</th>
                                                                <th class="w-[9%] p-2 text-right">Đơn giá</th>
                                                                <th class="w-[10%] p-2 text-right">Tổng phụ</th>
                                                                <th class="w-[9%] p-2 text-right">Loại giảm giá</th>
                                                                <th class="w-[9%] p-2 text-right">Số tiền giảm giá</th>
                                                                <th class="w-[15%] p-2 text-left break-words whitespace-normal">Ghi chú mục</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template v-if="order.items && order.items.length > 0">
                                                                <tr v-for="item in order.items" :key="item.id" class="border-t">
                                                                    <td class="w-[12%] p-2 break-words whitespace-normal">
                                                                        {{ item.product_name }}
                                                                    </td>
                                                                    <td class="w-[10%] p-2">
                                                                        {{ item.product_sku }}
                                                                    </td>
                                                                    <td class="w-[7%] text-center">{{ item.ordered_quantity }}</td>
                                                                    <td class="w-[7%] text-center">{{ item.quantity_returned }}</td>
                                                                    <td class="w-[7%] text-center">{{ item.received_quantity }}</td>
                                                                    <td class="w-[5%] text-left">
                                                                        {{ item.product && item.product.unit ? item.product.unit.name : 'N/A' }}
                                                                    </td>
                                                                    <td class="w-[9%] text-right">{{ formatCurrency(item.unit_cost) }}</td>
                                                                    <td class="w-[10%] text-right">{{ formatCurrency(item.subtotal) }}</td>
                                                                    <th class="w-[9%] text-right">
                                                                        {{
                                                                            item.discount_type === 'percent'
                                                                                ? 'Phần trăm'
                                                                                : item.discount_type === 'amount'
                                                                                  ? 'Số tiền'
                                                                                  : 'Không'
                                                                        }}
                                                                    </th>
                                                                    <th class="w-[9%] text-right">
                                                                        {{ formatDiscountDisplay(item.discount_amount, item.discount_type) }}
                                                                    </th>
                                                                    <td class="w-[15%] p-2 break-words whitespace-normal">
                                                                        {{ item.notes || '—' }}
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                            <tr v-else>
                                                                <td :colspan="11" class="py-4 text-center text-gray-500">
                                                                    Không có sản phẩm nào trong đơn hàng này.
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="mt-6 mb-5 flex flex-col items-end gap-2 text-gray-800">
                                                        <h2 class="mb-3 w-full text-right text-lg font-semibold text-gray-800">Tổng kết đơn hàng</h2>

                                                        <div class="mt-4 grid gap-x-5 gap-y-2" style="grid-template-columns: max-content auto">
                                                            <div class="text-left"><strong>Tổng phụ (theo SP):</strong></div>
                                                            <div class="text-right">
                                                                {{ formatCurrency(calculateItemsSubtotalFromData(order.items)) }}
                                                            </div>
                                                            <div class="text-left"><strong>Loại giảm giá:</strong></div>
                                                            <div class="text-right">
                                                                {{
                                                                    order.discount_type === 'percent'
                                                                        ? 'Phần trăm'
                                                                        : order.discount_type === 'amount'
                                                                          ? 'Số tiền'
                                                                          : 'Không'
                                                                }}
                                                            </div>
                                                            <div class="text-left"><strong>Số tiền giảm giá:</strong></div>
                                                            <div class="text-right">
                                                                {{ formatDiscountDisplay(order.discount_amount, order.discount_type) }}
                                                            </div>
                                                            <div class="text-left text-xl"><strong>Tổng tiền:</strong></div>
                                                            <div class="text-right text-xl font-bold">{{ formatCurrency(order.total_amount) }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="paginatedPurchaseOrders.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm whitespace-nowrap text-gray-500">
                                        Không có đơn hàng nào được tìm thấy.
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
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700" :disabled="currentPage === 1" @click="prevPage">
                                &larr; Trang trước
                            </button>
                            <template v-for="pageNumber in totalPages" :key="pageNumber">
                                <button
                                    class="rounded px-3 py-1 text-sm"
                                    :class="pageNumber === currentPage ? 'bg-gray-200 font-bold' : 'text-gray-500 hover:text-gray-700'"
                                    @click="goToPage(pageNumber)"
                                >
                                    {{ pageNumber }}
                                </button>
                            </template>
                            <button
                                class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700"
                                :disabled="currentPage === totalPages"
                                @click="nextPage"
                            >
                                Trang sau &rarr;
                            </button>
                            <select
                                v-model="perPage"
                                @change="changePerPage"
                                class="rounded-md border-gray-300 py-1 pr-7 pl-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }} / trang</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DeleteModal
            :is-open="showDeleteModal"
            :title="'Xác nhận xóa'"
            :description="'Bạn có chắc chắn muốn xóa đơn hàng này?'"
            @confirm="handleDeletePurchaseOrder"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>
<style lang="css" scoped>
/* Định nghĩa chiều rộng cột rõ ràng để tránh tràn */
table th,
table td {
    overflow: hidden;
    /* Ẩn nội dung tràn ra ngoài */
    text-overflow: ellipsis;
    /* Hiển thị dấu ba chấm cho nội dung tràn */
}
</style>
