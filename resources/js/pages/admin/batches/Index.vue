<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, FileDown, PackagePlus, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import * as XLSX from 'xlsx';

// Loại bỏ import * as XLSX từ "xlsx"; vì backend sẽ xử lý

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

type User = {
    id: number;
    name: string;
    email: string;
};

type Batch = {
    id: number;
    batch_number: string;
    purchase_order_id: number;
    purchase_order_number?: string;
    supplier_id: number | null;
    supplier_name?: string;
    received_date: string;
    invoice_number: string | null;
    total_amount: number;
    payment_status: 'unpaid' | 'partially_paid' | 'paid';
    paid_amount: number;
    receipt_status: 'partially_received' | 'completed' | 'cancelled';
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
    inventory_status: 'active' | 'low_stock' | 'out_of_stock' | 'expired' | 'damaged';
    product?: {
        name: string;
        sku: string;
        unit?: {
            name: string;
        };
    };
};

const page = usePage<SharedData & { importMessage?: string; importStatus?: 'success' | 'error' }>();
const batches = ref([...(page.props.batches as Batch[])]);
const suppliers = (page.props.suppliers as Supplier[]) || [];
const purchaseOrders = (page.props.purchaseOrders as any[]) || []; // Bạn cần truyền danh sách này từ backend
const isLoading = ref(false);
const importMessage = ref<string | null>(page.props.importMessage || null);
const importStatus = ref<'success' | 'error' | null>(page.props.importStatus || null);

// Loại bỏ showPreviewModal và previewData vì frontend không còn xử lý preview
// const showPreviewModal = ref(false);
// const previewData = ref<any[][]>([]);
// Loại bỏ batchesToImport vì frontend không còn xử lý dữ liệu trước khi gửi
// const batchesToImport = ref<Batch[]>([]);

const perPage = ref(10);
const currentPage = ref(1);
const perPageOptions = [10, 20, 50, 100];
const searchTerm = ref('');
const filterStatus = ref('');
const filterPaymentStatus = ref('');
const filterReceiptStatus = ref('');
const filterStartDate = ref('');
const filterEndDate = ref('');
const filterInvoiceNumber = ref('');
const isFiltersCollapsed = ref(true);
const showDeleteModal = ref(false);
const batchToDelete = ref<number | null>(null);
const openBatchDetailsId = ref<number | null>(null);

// Loại bỏ expectedHeaders vì backend sẽ kiểm tra
// const expectedHeaders = [
//     'Mã phiếu nhập',
//     'Nhà cung cấp',
//     'Ngày nhận hàng',
//     'Số hóa đơn',
//     'Ghi chú',
//     'Trạng thái thanh toán',
//     'Trạng thái nhận hàng',
//     'Tổng tiền',
//     'Số tiền đã thanh toán',
//     'Tên sản phẩm',
//     'Mã SKU',
//     'Số lượng đặt',
//     'Số lượng nhận',
//     'Số lượng từ chối',
//     'Số lượng còn lại',
//     'Số lượng nhập',
//     'Đơn giá',
//     'Tổng giá',
//     'Ngày sản xuất',
//     'Ngày hết hạn',
//     'Trạng thái tồn kho',
//     'Đơn vị'
// ];

const filteredAndSortedBatches = computed(() => {
    let currentBatches = batches.value;

    const trimmedSearch = searchTerm.value.trim().toLowerCase();
    if (trimmedSearch) {
        currentBatches = currentBatches.filter(
            (batch) =>
                batch.batch_number.toLowerCase().includes(trimmedSearch) ||
                (batch.supplier_name?.toLowerCase().includes(trimmedSearch) ?? false) ||
                (batch.notes?.toLowerCase().includes(trimmedSearch) ?? false),
        );
    }

    const trimmedInvoiceSearch = filterInvoiceNumber.value.trim().toLowerCase();
    if (trimmedInvoiceSearch) {
        currentBatches = currentBatches.filter((batch) => batch.invoice_number?.toLowerCase().includes(trimmedInvoiceSearch) ?? false);
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
    router.visit(route('admin.batches.show', id));
}

function toggleBatchDetails(id: number) {
    openBatchDetailsId.value = openBatchDetailsId.value === id ? null : id;
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
    filterInvoiceNumber.value = '';
    resetPagination();
}

function exportBatchesToExcel() {
    isLoading.value = true;
    try {
        const exportData = batches.value.flatMap((batch) => {
            if (batch.batchItems?.length) {
                return batch.batchItems.map((item) => ({
                    'Mã phiếu nhập': batch.batch_number || 'N/A',
                    'Mã đơn đặt hàng': batch.purchase_order_number || 'N/A',
                    'Nhà cung cấp': batch.supplier_name || 'N/A',
                    'Ngày nhận hàng': formatDateTime(batch.received_date),
                    'Số hóa đơn': batch.invoice_number || 'N/A',
                    'Ghi chú': batch.notes || 'N/A',
                    'Trạng thái thanh toán': getPaymentStatusDisplayName(batch.payment_status),
                    'Trạng thái nhận hàng': getReceiptStatusDisplayName(batch.receipt_status),
                    'Tổng tiền': batch.total_amount || 0,
                    'Số tiền đã thanh toán': batch.paid_amount || 0,
                    'Người tạo': batch.creator?.name || 'N/A',
                    'Ngày tạo': formatDateTime(batch.created_at),
                    'Ngày cập nhật': formatDateTime(batch.updated_at),
                    'Tên sản phẩm': item.product?.name ?? item.product_name ?? 'N/A',
                    'Mã SKU': item.product?.sku ?? item.product_sku ?? 'N/A',
                    'Số lượng đặt': item.ordered_quantity ?? 0,
                    'Số lượng nhận': item.received_quantity ?? 0,
                    'Số lượng từ chối': item.rejected_quantity ?? 0,
                    'Số lượng còn lại': item.remaining_quantity ?? 0,
                    'Số lượng nhập': item.current_quantity ?? 0,
                    'Đơn giá': item.purchase_price ?? 0,
                    'Tổng giá': item.total_amount ?? 0,
                    'Ngày sản xuất': formatDateTime(item.manufacturing_date),
                    'Ngày hết hạn': formatDateTime(item.expiry_date),
                    'Trạng thái tồn kho': getInventoryStatusDisplayName(item.inventory_status),
                    'Đơn vị': item.product?.unit?.name ?? 'N/A',
                }));
            }
            return [
                {
                    'Mã phiếu nhập': batch.batch_number || 'N/A',
                    'Mã đơn đặt hàng': batch.purchase_order_number || 'N/A',
                    'Nhà cung cấp': batch.supplier_name || 'N/A',
                    'Ngày nhận hàng': formatDateTime(batch.received_date),
                    'Số hóa đơn': batch.invoice_number || 'N/A',
                    'Ghi chú': batch.notes || 'N/A',
                    'Trạng thái thanh toán': getPaymentStatusDisplayName(batch.payment_status),
                    'Trạng thái nhận hàng': getReceiptStatusDisplayName(batch.receipt_status),
                    'Tổng tiền': batch.total_amount || 0,
                    'Số tiền đã thanh toán': batch.paid_amount || 0,
                    'Người tạo': batch.creator?.name || 'N/A',
                    'Ngày tạo': formatDateTime(batch.created_at),
                    'Ngày cập nhật': formatDateTime(batch.updated_at),
                    'Tên sản phẩm': '',
                    'Mã SKU': '',
                    'Số lượng đặt': '',
                    'Số lượng nhận': '',
                    'Số lượng từ chối': '',
                    'Số lượng còn lại': '',
                    'Số lượng nhập': '',
                    'Đơn giá': '',
                    'Tổng giá': '',
                    'Ngày sản xuất': '',
                    'Ngày hết hạn': '',
                    'Trạng thái tồn kho': '',
                    'Đơn vị': '',
                },
            ];
        });

        const worksheet = XLSX.utils.json_to_sheet(exportData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Danh sách phiếu nhập');

        const now = new Date();
        const formattedDate = now
            .toLocaleString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            })
            .replace(/[, ]/g, '-')
            .replace(/\//g, '-');
        XLSX.writeFile(workbook, `DanhSachPhieuNhapHang_${formattedDate}.xlsx`);
    } catch (error) {
        console.error('Lỗi khi xuất file:', error);
        importMessage.value = 'Có lỗi xảy ra khi xuất file Excel. Vui lòng thử lại!';
        importStatus.value = 'error';
    } finally {
        isLoading.value = false;
    }
}
function goToCreatePage() {
router.visit(route("admin.batches.create"));
    
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

function getReceiptStatusDisplayName(status: 'partially_received' | 'completed' | 'cancelled') {
    switch (status) {
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

function getInventoryStatusDisplayName(status: 'active' | 'low_stock' | 'out_of_stock' | 'expired' | 'damaged') {
    switch (status) {
        case 'active':
            return 'Hoạt động';
        case 'low_stock':
            return 'Sắp hết hàng';
        case 'out_of_stock':
            return 'Hết hàng';
        case 'expired':
            return 'Hết hạn';
        case 'damaged':
            return 'Hư hỏng';
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

function calculateItemsSubtotalFromData(items: BatchItem[] | undefined): number {
    if (!items || items.length === 0) return 0;
    return items.reduce((sum, item) => sum + item.total_amount, 0);
}

watch(
    () => page.props.batches,
    (newBatches) => {
        batches.value = (newBatches as any[]).map((b: any) => ({
            ...b,
            batchItems: b.batch_items,
            supplier_name: b.supplier?.name,
        }));
    },
    { immediate: true },
);

watch(
    () => page.props.importMessage,
    (newMessage) => {
        importMessage.value = newMessage || null;
    },
);

watch(
    () => page.props.importStatus,
    (newStatus) => {
        importStatus.value = newStatus || null;
    },
);

// Loại bỏ validateBatchData vì backend sẽ kiểm tra
// function validateBatchData(batches: Batch[]): string[] { ... }

// Loại bỏ parseExcelDate vì backend sẽ xử lý
// function parseExcelDate(value: any): string | null { ... }

// Hàm mới để xử lý việc chọn file và gửi đi
async function importBatchesFromExcel(event: Event) {
    const input = event.target as HTMLInputElement;
    if (!input.files || !input.files[0]) {
        importMessage.value = 'Vui lòng chọn file Excel!';
        importStatus.value = 'error';
        return;
    }

    isLoading.value = true;
    importMessage.value = null; // Xóa thông báo cũ
    importStatus.value = null; // Xóa trạng thái cũ

    const file = input.files[0];

    // Kiểm tra định dạng file
    if (!file.name.endsWith('.xlsx') && !file.name.endsWith('.xls') && !file.name.endsWith('.csv')) {
        importMessage.value = 'Vui lòng chọn file Excel (.xlsx, .xls hoặc .csv)!';
        importStatus.value = 'error';
        isLoading.value = false;
        // Đặt lại giá trị của input file để có thể chọn lại cùng một file
        (event.target as HTMLInputElement).value = '';
        return;
    }

    const formData = new FormData();
    formData.append('excel_file', file); // Tên 'excel_file' phải khớp với tên trong Laravel Request validation

    router.post(route('admin.batches.import'), formData, {
        onSuccess: (page) => {
            importMessage.value = (page.props.importMessage as string) || 'Import thành công!';
            importStatus.value = (page.props.importStatus as 'success') || 'success';
            console.log('Import successful!', page.props);
            // Tải lại dữ liệu batches để cập nhật bảng
            router.reload({ only: ['batches'] });
        },
        onError: (errors) => {
            console.error('Import error:', errors);
            // Lấy thông báo lỗi từ backend nếu có
            if (errors && typeof errors === 'object') {
                if (errors.excel_file) {
                    importMessage.value = errors.excel_file[0];
                } else if (errors.importMessage) {
                    // Lỗi chung từ backend
                    importMessage.value = errors.importMessage;
                } else {
                    importMessage.value = 'Import thất bại! Vui lòng kiểm tra dữ liệu và thử lại.';
                }
            } else {
                importMessage.value = 'Import thất bại! Đã xảy ra lỗi không xác định.';
            }
            importStatus.value = 'error';

            if (errors.status === 419) {
                // CSRF token mismatch
                importMessage.value = 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.';
                router.visit('/login');
            }
        },
        onFinish: () => {
            isLoading.value = false;
            // Đặt lại giá trị của input file để có thể chọn lại cùng một file
            (event.target as HTMLInputElement).value = '';
        },
        // Rất quan trọng: Bắt buộc Inertia gửi FormData đúng cách
        forceFormData: true,
    });
}

// Loại bỏ confirmImport vì không còn cần bước xác nhận frontend sau khi đọc file
// async function confirmImport() { ... }

// Loại bỏ các hàm mapStatus vì backend sẽ xử lý
// function mapPaymentStatus(value: string): "unpaid" | "partially_paid" | "paid" | "" { ... }
// function mapReceiptStatus(value: string): "partially_received" | "completed" | "cancelled" | "" { ... }
// function mapInventoryStatus(value: string): "active" | "low_stock" | "out_of_stock" | "expired" | "damaged" | "" { ... }
</script>

<template>
    <Head>
        <title>Quản lý đơn nhập hàng</title>
        <meta name="description" content="Quản lý danh sách phiếu nhập hàng, theo dõi trạng thái thanh toán và nhận hàng." />
    </Head>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <div class="mb-4 flex items-center gap-4">
                        <h1 class="text-2xl font-bold">Quản lý đơn nhập hàng</h1>
                        <div class="ml-auto flex gap-4">
                            <button
                                @click="exportBatchesToExcel"
                                class="inline-flex items-center rounded-3xl bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                                :disabled="isLoading"
                            >
                                <FileDown class="h-5 w-5" />
                                <span class="ml-2 hidden md:inline">{{ isLoading ? 'Đang xuất...' : 'Xuất Excel' }}</span>
                            </button>
                            <!-- <label
                                class="inline-flex items-center rounded-3xl bg-emerald-500 px-4 py-2 text-white hover:bg-emerald-600 cursor-pointer"
                                :class="{ 'opacity-50 cursor-not-allowed': isLoading }"> -->
                            <!-- Input file đã được liên kết với hàm importBatchesFromExcel -->
                            <!-- <input type="file" accept=".xlsx,.xls,.csv" @change="importBatchesFromExcel"
                                    class="hidden" :disabled="isLoading" />
                                <span class="ml-2 hidden md:inline">{{ isLoading ? "Đang xử lý..." : "Import Excel"
                                    }}</span>
                            </label> -->

                            <button
                            @click="goToCreatePage"
                                class="inline-flex items-center rounded-3xl bg-green-500 px-4 py-2 text-white hover:bg-green-600">
                                <PackagePlus class="h-5 w-5" />
                                <span class="ml-2 hidden md:inline">Tạo Đơn nhập Hàng Mới</span>
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="importMessage"
                        class="mb-4 rounded-lg p-4"
                        :class="{
                            'bg-green-100 text-green-800': importStatus === 'success',
                            'bg-red-100 text-red-800': importStatus === 'error',
                        }"
                    >
                        {{ importMessage }}
                    </div>

                    <!-- Loại bỏ showPreviewModal vì không còn cần bước xem trước ở frontend -->
                    <!-- <div v-if="showPreviewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-lg p-6 max-w-4xl w-full max-h-[80vh] overflow-auto">
                            <h2 class="text-xl font-bold mb-4">Xem trước dữ liệu import</h2>
                            <table class="min-w-full border-collapse border border-gray-200">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th v-for="header in expectedHeaders" :key="header" class="p-2 text-left text-sm font-semibold">{{ header }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in previewData" :key="index" class="border-t">
                                        <td v-for="(cell, cellIndex) in row" :key="cellIndex" class="p-2 text-sm">{{ cell || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="mt-4 flex justify-end gap-4">
                                <button @click="showPreviewModal = false" class="px-4 py-2 bg-gray-300 rounded-md">Hủy</button>
                                <button @click="confirmImport" class="px-4 py-2 bg-emerald-500 text-white rounded-md" :disabled="isLoading">
                                    {{ isLoading ? 'Đang import...' : 'Xác nhận import' }}
                                </button>
                            </div>
                        </div>
                    </div> -->

                    <div class="mb-6 flex flex-wrap items-center gap-4">
                        <div class="relative min-w-[250px] flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <Search class="h-5 w-5 text-gray-400" />
                            </div>
                            <input
                                type="text"
                                v-model="searchTerm"
                                placeholder="Tìm kiếm mã phiếu nhập, NCC, ghi chú..."
                                class="w-full rounded-md border-gray-300 py-2 pr-4 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @input="resetPagination"
                            />
                        </div>
                        <button
                            @click="isFiltersCollapsed = !isFiltersCollapsed"
                            class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-sm hover:bg-gray-300"
                        >
                            Bộ lọc nâng cao
                            <svg
                                class="ml-2 h-4 w-4"
                                :class="{ 'rotate-180': !isFiltersCollapsed }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

                    <div v-if="!isFiltersCollapsed" class="mb-6 grid grid-cols-1 gap-4 rounded-lg bg-gray-50 p-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Trạng thái thanh toán</label>
                            <select
                                v-model="filterPaymentStatus"
                                class="mt-1 w-full rounded-md border-gray-300 py-2 text-sm"
                                @change="resetPagination"
                            >
                                <option value="">Tất cả</option>
                                <option value="unpaid">Chưa thanh toán</option>
                                <option value="partially_paid">Đã thanh toán một phần</option>
                                <option value="paid">Đã thanh toán</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Trạng thái nhận hàng</label>
                            <select
                                v-model="filterReceiptStatus"
                                class="mt-1 w-full rounded-md border-gray-300 py-2 text-sm"
                                @change="resetPagination"
                            >
                                <option value="">Tất cả</option>
                                <option value="partially_received">Đã nhận một phần</option>
                                <option value="completed">Đã nhận đủ</option>
                                <option value="cancelled">Đã hủy</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Số hóa đơn</label>
                            <input
                                type="text"
                                v-model="filterInvoiceNumber"
                                placeholder="Tìm kiếm số hóa đơn..."
                                class="mt-1 w-full rounded-md border-gray-300 py-2 text-sm"
                                @input="resetPagination"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Từ ngày</label>
                            <input
                                type="date"
                                v-model="filterStartDate"
                                class="mt-1 w-full rounded-md border-gray-300 py-2 text-sm"
                                @change="resetPagination"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Đến ngày</label>
                            <input
                                type="date"
                                v-model="filterEndDate"
                                class="mt-1 w-full rounded-md border-gray-300 py-2 text-sm"
                                @change="resetPagination"
                            />
                        </div>
                        <div class="col-span-3 flex justify-end">
                            <button
                                @click="resetAllFilters"
                                class="inline-flex items-center rounded-md bg-red-500 px-4 py-2 text-sm text-white hover:bg-red-600"
                            >
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
                                            <button @click="goToShowPage(batch.id)" class="cursor-pointer hover:text-blue-500">
                                                {{ batch.batch_number }}
                                            </button>
                                        </td>
                                        <td class="supplier-column w-[15%] p-3 text-left text-sm">
                                            {{ batch.supplier_name || 'N/A' }}
                                        </td>
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm">
                                            {{ formatDateTime(batch.received_date) }}
                                        </td>
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm">
                                            {{ formatCurrency(batch.total_amount) }}
                                        </td>
                                        <td class="truncate-column w-[10%] p-3 text-left text-sm">
                                            <span
                                                :class="{
                                                    'rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 shadow-sm':
                                                        batch.payment_status === 'paid',
                                                    'rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800 shadow-sm':
                                                        batch.payment_status === 'partially_paid',
                                                    'rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 shadow-sm':
                                                        batch.payment_status === 'unpaid',
                                                }"
                                                :title="getPaymentStatusDisplayName(batch.payment_status)"
                                            >
                                                {{ getPaymentStatusDisplayName(batch.payment_status) }}
                                            </span>
                                        </td>
                                        <td class="w-[10%] p-3 text-left text-sm">
                                            <span
                                                :class="{
                                                    'rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 shadow-sm':
                                                        batch.receipt_status === 'completed',
                                                    'rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800 shadow-sm':
                                                        batch.receipt_status === 'partially_received',
                                                    'rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 shadow-sm':
                                                        batch.receipt_status === 'cancelled',
                                                }"
                                                :title="getReceiptStatusDisplayName(batch.receipt_status)"
                                            >
                                                {{ getReceiptStatusDisplayName(batch.receipt_status) }}
                                            </span>
                                        </td>
                                        <td class="w-[5%] p-3 text-center text-sm">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button
                                                    @click="toggleBatchDetails(batch.id)"
                                                    class="flex items-center gap-1 rounded-md bg-gray-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
                                                >
                                                    <component :is="openBatchDetailsId === batch.id ? EyeOff : Eye" class="h-4 w-4" />
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
                                                    <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4 shadow-sm">
                                                        <p><strong>Mã phiếu nhập:</strong> {{ batch.batch_number || 'Không có' }}</p>
                                                        <p><strong>Nhà cung cấp:</strong> {{ batch.supplier_name || 'N/A' }}</p>
                                                        <p><strong>Ngày nhận hàng:</strong> {{ formatDateTime(batch.received_date) || 'N/A' }}</p>
                                                        <p><strong>Số hóa đơn:</strong> {{ batch.invoice_number || 'Không có' }}</p>
                                                        <p><strong>Ghi chú:</strong> {{ batch.notes || 'Không có' }}</p>
                                                    </div>
                                                    <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4 shadow-sm">
                                                        <p>
                                                            <strong>Trạng thái thanh toán:</strong>
                                                            {{ getPaymentStatusDisplayName(batch.payment_status) }}
                                                        </p>
                                                        <p>
                                                            <strong>Trạng thái nhận hàng:</strong>
                                                            {{ getReceiptStatusDisplayName(batch.receipt_status) }}
                                                        </p>
                                                        <p><strong>Tổng tiền:</strong> {{ formatCurrency(batch.total_amount) }}</p>
                                                        <p><strong>Số tiền đã thanh toán:</strong> {{ formatCurrency(batch.paid_amount) }}</p>
                                                        <p><strong>Người tạo:</strong> {{ batch.creator ? batch.creator.name : 'N/A' }}</p>
                                                    </div>
                                                </div>

                                                <h5 class="mt-6 mb-3 text-base font-semibold text-gray-800">Sản phẩm trong phiếu nhập:</h5>
                                                <div class="max-h-[400px] overflow-x-auto">
                                                    <table
                                                        class="min-w-full table-fixed border-collapse overflow-hidden rounded-lg border border-gray-200 text-sm"
                                                    >
                                                        <thead class="bg-blue-50 font-semibold text-gray-700 uppercase">
                                                            <tr>
                                                                <th class="w-[9%] p-2 text-left whitespace-normal">Tên sản phẩm</th>
                                                                <th class="w-[7%] p-2 text-center">Mã SKU</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL đặt</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL trả</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL nhận</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL từ chối</th>
                                                                <th class="w-[7%] p-2 text-center whitespace-normal">SL nhập</th>
                                                                <th class="w-[5%] p-2 text-left">Đơn vị</th>
                                                                <th class="w-[7%] p-2 text-right">Đơn giá</th>
                                                                <th class="w-[7%] p-2 text-right">Tổng giá</th>
                                                                <th class="w-[10%] p-2 text-center">Ngày sản xuất</th>
                                                                <th class="w-[9%] p-2 text-center">Ngày hết hạn</th>
                                                                <th class="w-[15%] p-2 text-center">Trạng thái tồn kho</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template v-if="batch.batchItems && batch.batchItems.length > 0">
                                                                <tr v-for="item in batch.batchItems" :key="item.id" class="border-t">
                                                                    <td class="w-[9%] p-2 break-words whitespace-normal">
                                                                        {{ item.product?.name ?? item.product_name }}
                                                                    </td>
                                                                    <td class="w-[7%] text-center">{{ item.product?.sku ?? item.product_sku }}</td>
                                                                    <td class="w-[7%] text-center">{{ item.ordered_quantity }}</td>
                                                                    <td class="w-[7%] text-center">{{ item.rejected_quantity }}</td>
                                                                    <td class="w-[7%] text-center">{{ item.received_quantity }}</td>
                                                                    <td class="w-[7%] text-center">{{ item.remaining_quantity }}</td>
                                                                    <td class="w-[7%] text-center">{{ item.current_quantity }}</td>
                                                                    <td class="w-[5%] text-left">{{ item.product?.unit?.name ?? 'N/A' }}</td>
                                                                    <td class="w-[7%] text-right">{{ formatCurrency(item.purchase_price) }}</td>
                                                                    <td class="w-[7%] text-right">{{ formatCurrency(item.total_amount) }}</td>
                                                                    <td class="w-[10%] text-center">{{ formatDateTime(item.manufacturing_date) }}</td>
                                                                    <td class="w-[9%] text-center">{{ formatDateTime(item.expiry_date) }}</td>
                                                                    <td class="w-[15%] text-center">
                                                                        <span
                                                                            :class="{
                                                                                'rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 shadow-sm':
                                                                                    item.inventory_status === 'active',
                                                                                'rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800 shadow-sm':
                                                                                    item.inventory_status === 'low_stock',
                                                                                'rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 shadow-sm':
                                                                                    item.inventory_status === 'out_of_stock' ||
                                                                                    item.inventory_status === 'expired',
                                                                                'rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800 shadow-sm':
                                                                                    item.inventory_status === 'damaged',
                                                                            }"
                                                                            :title="getInventoryStatusDisplayName(item.inventory_status)"
                                                                        >
                                                                            {{ getInventoryStatusDisplayName(item.inventory_status) }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                            <tr v-else>
                                                                <td :colspan="16" class="py-4 text-center text-gray-500">
                                                                    Không có sản phẩm nào trong phiếu nhập này.
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="mt-6 mb-5 flex flex-col items-end gap-2 text-gray-800">
                                                        <h2 class="mb-3 w-full text-right text-lg font-semibold text-gray-800">
                                                            Tổng kết phiếu nhập
                                                        </h2>
                                                        <div class="mt-4 grid gap-x-5 gap-y-2" style="grid-template-columns: max-content auto">
                                                            <div class="text-left"><strong>Tổng phụ (theo SP):</strong></div>
                                                            <div class="text-right">
                                                                {{ formatCurrency(calculateItemsSubtotalFromData(batch.batchItems)) }}
                                                            </div>
                                                            <div class="text-left"><strong>Tổng tiền:</strong></div>
                                                            <div class="text-right text-xl font-bold">{{ formatCurrency(batch.total_amount) }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="paginatedBatches.length === 0">
                                    <td colspan="7" class="p-4 text-center text-sm text-gray-500">Không có dữ liệu phù hợp với tiêu chí tìm kiếm.</td>
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
                                ← Trang trước
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
                                Trang sau →
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
