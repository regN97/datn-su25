<script setup lang="ts">
import DeleteModal from '@/components/DeleteModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PackagePlus, Pencil, Trash2, Eye, EyeOff, MoveRight,  } from 'lucide-vue-next'; // Thêm Eye, EyeOff

import { computed, ref, watch } from 'vue';

// --- Định nghĩa các Type bắt đầu từ đây ---

// Kiểu cho PO Status
type POStatus = {
    id: number;
    name: string;
    code: string; // Ví dụ: 'pending', 'approved', 'rejected', 'cancelled'
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

// Kiểu cho PurchaseOrder, bao gồm các mối quan hệ đã được tải
type PurchaseOrder = {
    id: number;
    po_number: string;
    supplier_id: number;
    supplier?: Supplier;
    status_id: number;
    status?: POStatus; // Mối quan hệ tới POStatus
    order_date: string;
    expected_delivery_date: string;
    actual_delivery_date: string | null;
    subtotal_amount: number;
    tax_amount: number;
    discount_amount: number;
    shipping_cost: number;
    total_amount: number;
    payment_status: 'unpaid' | 'partially_paid' | 'paid' | 'overdue';
    payment_terms: string;
    payment_method: 'cash' | 'bank_transfer' | 'credit' | 'check' | string;
    payment_due_date: string | null;
    amount_paid: number;
    balance_due: number;
    received_status: 'pending' | 'partial' | 'fully';
    created_by: number;
    creator?: User;
    approved_by: number | null;
    approver?: User;
    approved_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
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
const selectedOrderStatus = ref(''); // Filter cho trạng thái PO
const selectedPaymentStatus = ref(''); // Filter cho trạng thái thanh toán
const selectedReceivedStatus = ref(''); // Filter cho trạng thái nhận hàng

// Danh sách các tùy chọn cho bộ lọc trạng thái (thêm vào script setup)
const poStatusOptions: POStatus[] = [
    { id: 1, name: 'Đang chờ', code: 'pending' },
    { id: 2, name: 'Đã duyệt', code: 'approved' },
    { id: 3, name: 'Đã gửi', code: 'sent' }, // Ví dụ
    { id: 4, name: 'Đã hủy', code: 'cancelled' }, // Ví dụ
    { id: 5, name: 'Từ chối', code: 'rejected' }, // Ví dụ
    // Thêm các trạng thái PO khác nếu có từ database của bạn
];

const paymentStatusOptions = [
    { name: 'Chưa thanh toán', code: 'unpaid' },
    { name: 'Đã thanh toán một phần', code: 'partially_paid' },
    { name: 'Đã thanh toán đủ', code: 'paid' },
    { name: 'Quá hạn thanh toán', code: 'overdue' },
];

const receivedStatusOptions = [
    { name: 'Đang chờ nhận', code: 'pending' },
    { name: 'Đã nhận một phần', code: 'partial' },
    { name: 'Đã nhận đủ', code: 'fully' },
];


// --- Cấu hình phân trang ---
const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

// --- Computed cho danh sách đơn hàng đã lọc và phân trang ---
const filteredPurchaseOrders = computed(() => {
    let filtered = allPurchaseOrders.value;

    // Lọc theo từ khóa tìm kiếm (Mã PO, NCC, Hóa đơn)
    if (searchTerm.value) {
        const lowerSearchTerm = searchTerm.value.toLowerCase();
        filtered = filtered.filter(order =>
            order.po_number.toLowerCase().includes(lowerSearchTerm) ||
            (order.supplier?.name && order.supplier.name.toLowerCase().includes(lowerSearchTerm)) ||
            (order.notes && order.notes.toLowerCase().includes(lowerSearchTerm)) // Giả sử "Hóa đơn" có thể liên quan đến ghi chú hoặc một trường khác
        );
    }

    // Lọc theo trạng thái PO
    if (selectedOrderStatus.value) {
        filtered = filtered.filter(order =>
            order.status?.code?.toLowerCase() === selectedOrderStatus.value.toLowerCase() // So sánh không phân biệt chữ hoa/thường
        );
    }

    // Lọc theo trạng thái thanh toán
    if (selectedPaymentStatus.value) {
        filtered = filtered.filter(order =>
            order.payment_status?.toLowerCase() === selectedPaymentStatus.value.toLowerCase()
        );
    }

    // Lọc theo trạng thái nhận hàng
    if (selectedReceivedStatus.value) {
        filtered = filtered.filter(order =>
            order.received_status?.toLowerCase() === selectedReceivedStatus.value.toLowerCase()
        );
    }

    // Reset current page to 1 whenever filters change
    // This is handled by watchers below for better UX
    return filtered;
});

// Watchers để reset currentPage về 1 khi bất kỳ filter nào thay đổi
watch([searchTerm, selectedOrderStatus, selectedPaymentStatus, selectedReceivedStatus], () => {
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
                // Sau khi xóa thành công, không cần splice nữa vì `allPurchaseOrders` sẽ tự update thông qua Inertia props
                // router.reload({ preserveState: true }); // Có thể reload page hoặc dựa vào Inertia prop update
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

// --- HÀM HỖ TRỢ DỊCH ENUM SANG TIẾNG VIỆT VÀ TRẢ VỀ CLASS CSS ---

function translatePaymentStatus(status: PurchaseOrder['payment_status']): string {
    switch (status) {
        case 'unpaid': return 'Chưa thanh toán';
        case 'partially_paid': return 'Đã thanh toán một phần';
        case 'paid': return 'Đã thanh toán đủ';
        case 'overdue': return 'Quá hạn thanh toán';
        default: return status;
    }
}

function getPaymentStatusClass(status: PurchaseOrder['payment_status']): string {
    switch (status) {
        case 'unpaid':
        case 'overdue': return 'bg-red-100 text-red-800';
        case 'partially_paid': return 'bg-yellow-100 text-yellow-800';
        case 'paid': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function translatePaymentMethod(method: PurchaseOrder['payment_method']): string {
    switch (method) {
        case 'cash': return 'Tiền mặt';
        case 'bank_transfer': return 'Chuyển khoản ngân hàng';
        case 'credit': return 'Thẻ tín dụng';
        case 'check': return 'Séc';
        default: return method;
    }
}

function translateReceivedStatus(status: PurchaseOrder['received_status']): string {
    switch (status) {
        case 'pending': return 'Đang chờ nhận';
        case 'partial': return 'Đã nhận một phần';
        case 'fully': return 'Đã nhận đủ';
        default: return status;
    }
}

function getReceivedStatusClass(status: PurchaseOrder['received_status']): string {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'partial': return 'bg-blue-100 text-blue-800';
        case 'fully': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getOrderStatusClass(statusCode: string | undefined): string {
    if (!statusCode) return 'bg-gray-100 text-gray-800';

    switch (statusCode.toLowerCase()) {
        case 'pending':
        case 'draft': return 'bg-yellow-100 text-yellow-800';
        case 'approved': return 'bg-green-100 text-green-800';
        case 'sent': return 'bg-indigo-100 text-indigo-800'; // Thêm màu cho 'sent'
        case 'rejected':
        case 'cancelled': return 'bg-red-100 text-red-800';
        case 'processing': return 'bg-blue-100 text-blue-800';
        case 'completed': return 'bg-purple-100 text-purple-800';
        default: return 'bg-gray-100 text-gray-800';
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
        console.error("Lỗi định dạng ngày:", e);
        return dateString;
    }
}

function truncateText(text: string, maxLength: number): string {
  if (!text) return '';
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

</script>

<template>
    <Head title="Purchase Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Quản lý đơn đặt hàng</h1>
                        <button @click="goToCreatePage"
                                class="inline-flex items-center rounded-3xl bg-green-500 px-4 py-2 text-white hover:bg-green-600">
                            <PackagePlus class="h-5 w-5" />
                            <span class="ml-2 hidden md:inline">Tạo Đơn Hàng Mới</span>
                        </button>
                    </div>

                    <div class="mb-6 flex flex-wrap items-center gap-4">
                        <div class="relative flex-1 min-w-[250px]">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchTerm"
                                placeholder="Tìm kiếm mã PO, NCC, ghi chú..."
                                class="w-full rounded-md border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div class="min-w-[150px]">
                            <label for="po-status-filter" class="sr-only">Lọc theo trạng thái PO</label>
                            <select
                                id="po-status-filter"
                                v-model="selectedOrderStatus"
                                class="w-full rounded-md border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Tất cả trạng thái PO</option>
                                <option v-for="option in poStatusOptions" :key="option.code" :value="option.code">
                                    {{ option.name }}
                                </option>
                            </select>
                        </div>

                        <div class="min-w-[150px]">
                            <label for="payment-status-filter" class="sr-only">Lọc theo trạng thái TT</label>
                            <select
                                id="payment-status-filter"
                                v-model="selectedPaymentStatus"
                                class="w-full rounded-md border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Tất cả trạng thái TT</option>
                                <option v-for="option in paymentStatusOptions" :key="option.code" :value="option.code">
                                    {{ option.name }}
                                </option>
                            </select>
                        </div>

                        <div class="min-w-[150px]">
                            <label for="received-status-filter" class="sr-only">Lọc theo trạng thái nhận</label>
                            <select
                                id="received-status-filter"
                                v-model="selectedReceivedStatus"
                                class="w-full rounded-md border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Tất cả trạng thái nhận</option>
                                <option v-for="option in receivedStatusOptions" :key="option.code" :value="option.code">
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
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Trạng thái TT</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Trạng thái nhận</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Trạng thái</th>
                                    <th class="w-[5%] p-3 text-center text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                                <tbody>
                                <template v-for="order in paginatedPurchaseOrders" :key="order.id">
                                    <tr class="border-t">
                                        <td class="w-[10%] p-3 text-left text-sm font-medium text-gray-900 truncate-column">
                                            {{ order.po_number }}
                                        </td>
                                        <td class="w-[15%] p-3 text-left text-sm text-gray-500 supplier-column">
                                            {{ order.supplier ? truncateText(order.supplier.name, 20) : 'N/A' }}
                                        </td>
                                        <td class="w-[10%] p-3 text-left text-sm text-gray-500 truncate-column">
                                            {{ formatDate(order.order_date) }}
                                        </td>
                                        <td class="w-[10%] p-3 text-left text-sm text-gray-500 truncate-column">
                                            {{ formatDate(order.expected_delivery_date) }}
                                        </td>
                                        <td class="w-[10%] p-3 text-left text-sm">
                                            <span
                                                :class="['px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full', getPaymentStatusClass(order.payment_status)]"
                                            >
                                                {{ translatePaymentStatus(order.payment_status) }}
                                            </span>
                                        </td>
                                        <td class="w-[10%] p-3 text-left text-sm">
                                            <span
                                                :class="['px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full', getReceivedStatusClass(order.received_status)]"
                                                >
                                                {{ translateReceivedStatus(order.received_status) }}
                                            </span>
                                        </td>
                                        <td class="w-[10%] p-3 text-left text-sm">
                                            <span
                                                :class="['px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full', getOrderStatusClass(order.status?.code)]"
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
                                        <td :colspan="9" class="border-t border-b border-gray-200 bg-gray-50 p-4">
                                            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                                                <h4 class="mb-4 text-xl font-bold text-gray-800">
                                                    <p class="cursor-pointer inline-block text-gray-800 hover:text-indigo-600 transition-colors duration-300" @click="goToShowPage(order.id)">
                                                        📝 Thông tin đơn hàng - {{ order.po_number || 'Không có' }}
                                                    </p>
                                                </h4>
                                                <div class="grid grid-cols-1 gap-6 text-sm text-gray-700 md:grid-cols-2">
                                                    <div class="space-y-3">
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Ngày giao thực tế:</span>
                                                            {{ formatDate(order.actual_delivery_date) || 'Chưa giao' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Tổng phụ:</span>
                                                            {{ order.subtotal_amount ? order.subtotal_amount.toLocaleString('vi-VN') + '₫' : '0₫' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Thuế:</span>
                                                            {{ order.tax_amount ? order.tax_amount.toLocaleString('vi-VN') + '₫' : '0₫' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Chiết khấu:</span>
                                                            {{ order.discount_amount ? order.discount_amount.toLocaleString('vi-VN') + '₫' : '0₫' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Chi phí vận chuyển:</span>
                                                            {{ order.shipping_cost ? order.shipping_cost.toLocaleString('vi-VN') + '₫' : '0₫' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Tổng tiền:</span>
                                                            {{ order.total_amount ? order.total_amount.toLocaleString('vi-VN') + '₫' : '0₫' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Số tiền đã trả:</span>
                                                            {{ order.amount_paid ? order.amount_paid.toLocaleString('vi-VN') + '₫' : '0₫' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Số tiền còn lại:</span>
                                                            {{ order.balance_due ? order.balance_due.toLocaleString('vi-VN') + '₫' : '0₫' }}
                                                        </div>
                                                    </div>

                                                    <div class="space-y-3">
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Điều khoản thanh toán:</span>
                                                            {{ order.payment_terms || 'Không có' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Phương thức thanh toán:</span>
                                                            {{ translatePaymentMethod(order.payment_method) }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Ngày đáo hạn thanh toán:</span>
                                                            {{ formatDate(order.payment_due_date) || 'N/A' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Người tạo:</span>
                                                            {{ order.creator ? order.creator.name : 'N/A' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Người duyệt:</span>
                                                            {{ order.approver ? order.approver.name : 'Chưa duyệt' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Thời gian duyệt:</span>
                                                            {{ formatDate(order.approved_at) || 'Chưa duyệt' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">Ghi chú:</span>
                                                            {{ order.notes || 'Không có' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>                                </template>
                                <tr v-if="paginatedPurchaseOrders.length === 0">
                                    <td colspan="11" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        Không có đơn hàng nào được tìm thấy.
                                    </td>
                                </tr>
                            </tbody>                        </table>
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

        <!-- <DeleteModal :show="showDeleteModal"
                     title="Xóa đơn đặt hàng"
                     message="Bạn có chắc chắn muốn xóa đơn đặt hàng này? Hành động này không thể hoàn tác."
                     @confirm="handleDeletePurchaseOrder"
                     @close="cancelDelete" /> -->

    </AppLayout>
</template>

<style lang="css" scoped>
/* Định nghĩa chiều rộng cột rõ ràng để tránh tràn */
table th, table td {
    white-space: nowrap; /* Ngăn chặn chữ bị xuống dòng trong ô */
    overflow: hidden;    /* Ẩn nội dung tràn ra ngoài */
    text-overflow: ellipsis; /* Hiển thị dấu ba chấm cho nội dung tràn */
}
</style>